"use client"

import { useState, useCallback, useEffect, useRef } from "react"
import { useAppContext } from "../../providers"
import { api } from "../../infrastructure/api"
import { v4 as uuidv4 } from "uuid"
import type { Envelope, EnvelopeState } from "./envelopeTypes"
import { useSocket } from "../../hooks/useSocket"
import { useError } from "../../contexts/ErrorContext"
import { useValidMessage } from "../../contexts/ValidContext"
import { useTranslation } from "../../hooks/useTranslation"

export function useEnvelopes() {
  const { state } = useAppContext()
  const { user } = state
  const { setValidMessage } = useValidMessage()
  const { setError } = useError()
  const { t } = useTranslation()
  const [pendingRequests, setPendingRequests] = useState<Set<string>>(new Set());
  const [envelopeState, setEnvelopeState] = useState<EnvelopeState>({
    envelopesData: state.envelopesData,
    loading: false,
    errorEnvelope: null,
  })

  const { socket } = useSocket()
  const userRef = useRef(user)

  useEffect(() => {
    userRef.current = user
  }, [user])

  const setLoading = (loading: boolean) => setEnvelopeState(prev => ({ ...prev, loading }))
  const setErrors = (errorEnvelope: string | null) => setEnvelopeState(prev => ({ ...prev, errorEnvelope }))

  const updateEnvelopeState = useCallback((updatedEnvelope: Envelope) => {
    setEnvelopeState(prev => ({
      ...prev,
      envelopesData: {
        ...prev.envelopesData,
        envelopes: prev.envelopesData?.envelopes.map(env =>
          env.uuid === updatedEnvelope.uuid ? updatedEnvelope : env
        ) || [],
      },
    }))
  }, [])

  const refreshEnvelopes = useCallback(async (force = false) => {

    console.log(envelopeState)
    if (!force && (envelopeState.loading || envelopeState.envelopesData)) return

    setLoading(true)
    setErrors(null)

    try {
      const updatedEnvelopes = await api.envelopeQueries.listEnvelopes()
      setEnvelopeState(prev => ({
        ...prev,
        envelopesData: updatedEnvelopes,
        loading: false
      }))
    } catch (err) {
      console.error("Refresh error:", err)
      setErrors("Failed to refresh envelopes")
      setLoading(false)
    }
  }, [envelopeState.loading, envelopeState.envelopesData, setLoading, setErrors])

  useEffect(() => {
    refreshEnvelopes()
  }, [refreshEnvelopes])

  const eventTypes = [
    'BudgetEnvelopeAdded',
    'BudgetEnvelopeCredited',
    'BudgetEnvelopeDebited',
    'BudgetEnvelopeDeleted',
    'BudgetEnvelopeRenamed',
    'BudgetEnvelopeTargetedAmountChanged'
  ];

  useEffect(() => {
    if (!socket) return;

    const handleEnvelopeEvent = (event: {
      aggregateId: string
      userId: string
      requestId: string
      type: string
    }) => {
      const pendingEnvelope = envelopeState.envelopesData?.envelopes
        .find(env => env.uuid === event.aggregateId);
      if (pendingEnvelope) {
        setEnvelopeState(prev => ({
          ...prev,
          envelopesData: {
            ...prev.envelopesData,
            envelopes: prev.envelopesData?.envelopes
              .filter(env => env.uuid !== event.aggregateId) || []
          }
        }));
      }

      if (event.userId === userRef.current?.uuid) {
        console.log(event.type);
        refreshEnvelopes(true);

        const validationMessages: { [key: string]: string } = {
          'BudgetEnvelopeCredited': t('envelopes.validationSuccess.creditEnvelope'),
          'BudgetEnvelopeDebited': t('envelopes.validationSuccess.debitEnvelope'),
          'BudgetEnvelopeRenamed': t('envelopes.validationSuccess.name'),
          'BudgetEnvelopeAdded': t('envelopes.validationSuccess.createNewEnvelope'),
          'BudgetEnvelopeDeleted': t('envelopes.validationSuccess.deleteEnvelope'),
        };

        const message = validationMessages[event.type];
        if (message) {
          setValidMessage(message);
        }
      }
    }


    // Cleanup function
    const cleanup = () => {
      eventTypes.forEach(eventType => {
        socket.off(eventType, handleEnvelopeEvent);
      });
    };

    // Register listeners
    eventTypes.forEach(eventType => {
      socket.on(eventType, handleEnvelopeEvent);
    });

    return cleanup;
  }, [socket, refreshEnvelopes, envelopeState.envelopesData?.envelopes]);

  const createEnvelope = async (name: string, targetBudget: string, setErrors: any, setValidMessage: any) => {
    setLoading(true)
    const requestId = uuidv4()
    setPendingRequests(prev => new Set([...prev, requestId]));
    const newEnvelope: Envelope = {
      uuid: requestId,
      name,
      targetedAmount: targetBudget,
      currentAmount: "0",
      updatedAt: new Date().toISOString(),
      userUuid: "",
      createdAt: new Date().toISOString(),
      deleted: false,
      pending: true,
    }

    setEnvelopeState((prev) => ({
      ...prev,
      envelopesData: {
        ...prev.envelopesData,
        envelopes: [...(prev.envelopesData?.envelopes || []), newEnvelope],
      },
    }))

    try {
      await api.envelopeCommands.createEnvelope(newEnvelope, requestId)
    } catch (err: any) {
      console.log(err)
      setPendingRequests(prev => {
        const newSet = new Set(prev);
        newSet.delete(requestId);
        return newSet;
      });
      setErrors(err.message)
      setEnvelopeState((prev) => ({
        ...prev,
        envelopesData: {
          ...prev.envelopesData,
          envelopes: prev.envelopesData?.envelopes.filter((env) => env.uuid !== requestId) || [],
        },
      }))
      setLoading(false)
    }
  }

  const deleteEnvelope = async (envelopeId: string, setErrors: any) => {
    setLoading(true)
    const requestId = uuidv4()
    setPendingRequests(prev => new Set([...prev, requestId]));

    setEnvelopeState((prev) => ({
      ...prev,
      envelopesData: {
        ...prev.envelopesData,
        envelopes:
          prev.envelopesData?.envelopes.map((env) =>
            env.uuid === envelopeId ? { ...env, pending: true, deleted: true } : env,
          ) || [],
      },
    }))

    try {
      await api.envelopeCommands.deleteEnvelope(envelopeId, requestId)
    } catch (err: any) {
      setPendingRequests(prev => {
        const newSet = new Set(prev);
        newSet.delete(requestId);
        return newSet;
      });
      setErrors(err.message)
      setEnvelopeState((prev) => ({
        ...prev,
        envelopesData: {
          ...prev.envelopesData,
          envelopes:
            prev.envelopesData?.envelopes.map((env) =>
              env.uuid === envelopeId ? { ...env, pending: false, deleted: false } : env,
            ) || [],
        },
      }))
      setLoading(false)
    }
  }

  const creditEnvelope = async (
    envelopeId: string,
    amount: string,
    description: string,
    setErrors: any,
    setValidMessage: any,
  ) => {
    setLoading(true)
    const requestId = uuidv4()
    setPendingRequests(prev => new Set([...prev, requestId]));
    const updatedEnvelope = envelopeState.envelopesData?.envelopes.find((env) => env.uuid === envelopeId)
    if (updatedEnvelope) {
      const newBudget = (Number.parseFloat(updatedEnvelope.currentAmount) + Number.parseFloat(amount)).toString()
      updateEnvelopeState({ ...updatedEnvelope, currentAmount: newBudget, pending: true })
    }
    try {
      await api.envelopeCommands.creditEnvelope(envelopeId, amount, description, requestId)
    } catch (err: any) {
      setErrors('test')
      setPendingRequests(prev => {
        const newSet = new Set(prev);
        newSet.delete(requestId);
        return newSet;
      });
      if (updatedEnvelope) {
        updateEnvelopeState({ ...updatedEnvelope, pending: false })
      }
      setLoading(false)
    }
  }

  const debitEnvelope = async (
    envelopeId: string,
    amount: string,
    description: string,
    setErrors: any,
    setValidMessage: any,
  ) => {
    setLoading(true)
    const requestId = uuidv4()
    setPendingRequests(prev => new Set([...prev, requestId]));
    const updatedEnvelope = envelopeState.envelopesData?.envelopes.find((env) => env.uuid === envelopeId)
    if (updatedEnvelope) {
      const newBudget = (Number.parseFloat(updatedEnvelope.currentAmount) - Number.parseFloat(amount)).toString()
      updateEnvelopeState({ ...updatedEnvelope, currentAmount: newBudget, pending: true })
    }
    try {
      await api.envelopeCommands.debitEnvelope(envelopeId, amount, description, requestId)
    } catch (err: any) {
      setPendingRequests(prev => {
        const newSet = new Set(prev);
        newSet.delete(requestId);
        return newSet;
      });
      setErrors(err.message)
      if (updatedEnvelope) {
        updateEnvelopeState({ ...updatedEnvelope, pending: false })
      }
      setLoading(false)
    }
  }

  const updateEnvelopeName = async (envelopeId: string, name: string, setErrors: any) => {
    setLoading(true)
    const requestId = uuidv4()
    setPendingRequests(prev => new Set([...prev, requestId]));
    const updatedEnvelope = envelopeState.envelopesData?.envelopes.find((env) => env.uuid === envelopeId)
    if (updatedEnvelope) {
      updateEnvelopeState({ ...updatedEnvelope, name, pending: true })
    }
    try {
      await api.envelopeCommands.nameEnvelope(envelopeId, name, requestId)
    } catch (err: any) {
      setPendingRequests(prev => {
        const newSet = new Set(prev);
        newSet.delete(requestId);
        return newSet;
      });
      setErrors(err.message)
      if (updatedEnvelope) {
        updateEnvelopeState({ ...updatedEnvelope, pending: false })
      }
      setLoading(false)
    }
  }

  return {
    ...envelopeState,
    refreshEnvelopes,
    createEnvelope,
    deleteEnvelope,
    creditEnvelope,
    debitEnvelope,
    updateEnvelopeName,
  }
}
