"use client"

import { useState, useCallback, useEffect } from "react"
import { api } from "../../infrastructure/api"
import { useError } from "../../contexts/ErrorContext"
import type { Envelope } from "./envelopeTypes"

export function useEnvelopeDetails(envelopeId: string) {
    const { setError } = useError()

    const [details, setDetails] = useState<Envelope | null>(null)
    const [loading, setLoading] = useState(false)
    const [error, setLocalError] = useState<string | null>(null)

    // Fonction pour mettre à jour localement les détails de l'enveloppe
    const updateEnvelopeDetails = useCallback((updatedEnvelope: Envelope) => {
        if (updatedEnvelope.uuid === envelopeId) {
            setDetails(updatedEnvelope)
        }
    }, [envelopeId])

    // Fonction pour actualiser les détails de l'enveloppe depuis l'API
    const refreshEnvelopeDetails = useCallback(async (force = false) => {
        if (!envelopeId || (!force && details)) return

        setLoading(true)
        setLocalError(null)

        try {
            const response = await api.envelopeQueries.getEnvelopeDetails(envelopeId)
            setDetails(response)
        } catch (err) {
            console.error("Failed to fetch envelope details", err)
            const errorMessage = "Failed to fetch envelope details"
            setLocalError(errorMessage)
            setError(errorMessage)
        } finally {
            setLoading(false)
        }
    }, [envelopeId, details, setError])

    useEffect(() => {
        refreshEnvelopeDetails()
    }, [refreshEnvelopeDetails])

    return { details, loading, error, refreshEnvelopeDetails, updateEnvelopeDetails }
}
