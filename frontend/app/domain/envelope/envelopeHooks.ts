'use client'

import { useState, useCallback, useEffect } from 'react'
import { useAppContext } from '../../providers'
import { api } from '../../infrastructure/api'
import { v4 as uuidv4 } from 'uuid'
import { Envelope, EnvelopeState } from './envelopeTypes'

const RETRY_INTERVAL = 2000 // 2 seconds
const MAX_RETRIES = 10

export function useEnvelopes() {
  const { state, setState } = useAppContext()
  const [envelopeState, setEnvelopeState] = useState<EnvelopeState>({
    envelopesData: state.envelopesData,
    loading: false,
    errorEnvelope: null,
  })

  const setLoading = (loading: boolean) => setEnvelopeState(prev => ({ ...prev, loading }))
  const setError = (errorEnvelope: string | null) => setEnvelopeState(prev => ({ ...prev, errorEnvelope }))

  const updateEnvelopeState = useCallback((updatedEnvelope: Envelope) => {
    setEnvelopeState(prev => ({
      ...prev,
      envelopesData: {
        ...prev.envelopesData,
        envelopes: prev.envelopesData?.envelopes.map(env => env.uuid === updatedEnvelope.uuid ? updatedEnvelope : env) || []
      }
    }))
  }, [setEnvelopeState])

  const refreshEnvelopes = useCallback(async (force = false) => {
    if (!force && (envelopeState.loading || envelopeState.envelopesData)) return
    setLoading(true)
    setError(null)
    try {
      const updatedEnvelopes = await api.envelopeQueries.listEnvelopes()
      setEnvelopeState(prev => ({ ...prev, envelopesData: updatedEnvelopes, loading: false }))
    } catch (err) {
      setError('Failed to refresh envelopes')
      setLoading(false)
    }
  }, [envelopeState.loading, envelopeState.envelopesData])

  useEffect(() => {
    refreshEnvelopes()
  }, [refreshEnvelopes])

  const pollForChanges = async (setValidMessage,envelopeId: string, action: string, expectedChange: (envelope: Envelope | undefined) => boolean) => {
    let retries = 0
    while (retries < MAX_RETRIES) {
      await new Promise(resolve => setTimeout(resolve, RETRY_INTERVAL))
      try {
        const updatedEnvelopes = await api.envelopeQueries.listEnvelopes()
        const updatedEnvelope = updatedEnvelopes.envelopes.find(env => env.uuid === envelopeId)
        if (expectedChange(updatedEnvelope)) {
          setEnvelopeState(prev => ({ ...prev, envelopesData: updatedEnvelopes, loading: false }))
          setValidMessage(`Envelope ${action} confirmed`)
          return
        }
      } catch (err) {
      }
      retries++
    }
    setError(`Failed to confirm ${action}. Please refresh.`)
    setLoading(false)
  }

  const createEnvelope = async (name: string, targetBudget: string, setError,setValidMessage) => {
    setLoading(true);
    const tempId = uuidv4();
    const newEnvelope: Envelope = {
      uuid: tempId,
      name,
      targetBudget,
      currentBudget: '0',
      updatedAt: new Date().toISOString(),
      userUuid: '',
      createdAt: new Date().toISOString(),
      deleted: false,
      pending: true,
    };

    setEnvelopeState((prev) => ({
      ...prev,
      envelopesData: {
        ...prev.envelopesData,
        envelopes: [...(prev.envelopesData?.envelopes || []), newEnvelope],
      },
    }));

    try {
      await api.envelopeCommands.createEnvelope(newEnvelope);
      pollForChanges(setValidMessage,tempId, 'creation', (env) => env?.uuid === tempId && !env?.pending);
    } catch (err: any) {
      console.log(err);
      setError(err.message);
      setEnvelopeState((prev) => ({
        ...prev,
        envelopesData: {
          ...prev.envelopesData,
          envelopes: prev.envelopesData?.envelopes.filter((env) => env.uuid !== tempId) || [],
        },
      }));
      setLoading(false);
    }
  };

  const deleteEnvelope = async (envelopeId: string,setError,setValidMessage) => {
    setLoading(true)

    setEnvelopeState(prev => ({
      ...prev,
      envelopesData: {
        ...prev.envelopesData,
        envelopes: prev.envelopesData?.envelopes.map(env =>
            env.uuid === envelopeId ? { ...env, pending: true, deleted: true } : env
        ) || []
      }
    }))

    try {
      await api.envelopeCommands.deleteEnvelope(envelopeId)
      pollForChanges(setValidMessage, envelopeId, 'deletion', (env) => env === undefined)
    } catch (err) {
      setError(err.message);
      setEnvelopeState(prev => ({
        ...prev,
        envelopesData: {
          ...prev.envelopesData,
          envelopes: prev.envelopesData?.envelopes.map(env =>
              env.uuid === envelopeId ? { ...env, pending: false, deleted: false } : env
          ) || []
        }
      }))
      setLoading(false)
    }
  }

  const creditEnvelope = async (envelopeId: string, amount: string,setError,setValidMessage ) => {
    setLoading(true)
    const updatedEnvelope = envelopeState.envelopesData?.envelopes.find(env => env.uuid === envelopeId)
    if (updatedEnvelope) {
      const newBudget = (parseFloat(updatedEnvelope.currentBudget) + parseFloat(amount)).toString()
      updateEnvelopeState({ ...updatedEnvelope, currentBudget: newBudget, pending: true })
    }
    try {
      await api.envelopeCommands.creditEnvelope(envelopeId, amount)
      pollForChanges(setValidMessage, envelopeId, 'credit', (env) =>
          parseFloat(env?.currentBudget || '0') >= parseFloat(updatedEnvelope?.currentBudget || '0') + parseFloat(amount)
      )
    } catch (err) {
      setError(err.message);
      if (updatedEnvelope) {
        updateEnvelopeState({ ...updatedEnvelope, pending: false })
      }
      setLoading(false)
    }
  }

  const debitEnvelope = async (envelopeId: string, amount: string, setError, setValidMessage) => {
    setLoading(true)
    const updatedEnvelope = envelopeState.envelopesData?.envelopes.find(env => env.uuid === envelopeId)
    if (updatedEnvelope) {
      const newBudget = (parseFloat(updatedEnvelope.currentBudget) - parseFloat(amount)).toString()
      updateEnvelopeState({ ...updatedEnvelope, currentBudget: newBudget, pending: true })
    }
    try {
      await api.envelopeCommands.debitEnvelope(envelopeId, amount)
      pollForChanges(setValidMessage, envelopeId, 'debit', (env) =>
          parseFloat(env?.currentBudget || '0') <= parseFloat(updatedEnvelope?.currentBudget || '0') - parseFloat(amount)
      )
    } catch (err) {
      setError(err.message);
      if (updatedEnvelope) {
        updateEnvelopeState({ ...updatedEnvelope, pending: false })
      }
      setLoading(false)
    }
  }

  const updateEnvelopeName = async (envelopeId: string, name: string,setError,setValidMessage) => {
    setLoading(true)
    const updatedEnvelope = envelopeState.envelopesData?.envelopes.find(env => env.uuid === envelopeId)
    if (updatedEnvelope) {
      updateEnvelopeState({ ...updatedEnvelope, name, pending: true })
    }
    try {
      await api.envelopeCommands.nameEnvelope(envelopeId, name)
      pollForChanges(setValidMessage,envelopeId, 'name update', (env) => env?.name === name)
    } catch (err) {
      setError(err.message);
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
