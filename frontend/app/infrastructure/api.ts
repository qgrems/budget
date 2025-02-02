import { authService } from "../services/auth"

const API_URL = process.env.NEXT_PUBLIC_API_URL

async function fetchWithAuth<T = any>(
    endpoint: string,
    options: RequestInit = {},
): Promise<T> {
    return authService.withTokenRefresh(async () => {
        const token = authService.getToken()
        const headers = new Headers({
            "Content-Type": "application/json",
            ...(token && { Authorization: `Bearer ${token}` }),
            ...(options.requestId && { "Request-Id": options.requestId })
        })

        const response = await fetch(`${API_URL}${endpoint}`, {
            ...options,
            headers
        })

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            const errorMessage = errorData.error || "Request failed"
            const error = new Error(errorMessage)
            error.name = `API_${response.status}`
            throw error
        }

        return response.status === 204 ? null : response.json()
    })
}

export const api = {
    commands: {
        createUser: (userData: any) =>
            fetchWithAuth("/users/new", {
                method: "POST",
                body: JSON.stringify({
                    uuid: userData.uuid,
                    firstname: userData.firstname,
                    lastname: userData.lastname,
                    email: userData.email,
                    password: userData.password,
                    languagePreference: userData.languagePreference,
                    consentGiven: userData.consentGiven,
                }),
            }),
        updateFirstname: (firstname: string) =>
            fetchWithAuth("/users/firstname", { method: "POST", body: JSON.stringify({ firstname }) }),
        updateLastname: (lastname: string) =>
            fetchWithAuth("/users/lastname", { method: "POST", body: JSON.stringify({ lastname }) }),
        changePassword: (oldPassword: string, newPassword: string) =>
            fetchWithAuth("/users/change-password", { method: "POST", body: JSON.stringify({ oldPassword, newPassword }) }),
    },
    queries: {
        getCurrentUser: () => fetchWithAuth("/users/me"),
    },

    envelopeCommands: {
        createEnvelope: (envelopeData: any, requestId: string) =>
            fetchWithAuth("/envelopes/add", { method: "POST", body: JSON.stringify(envelopeData), requestId }),
        creditEnvelope: (envelopeId: string, amount: string, description: string, requestId: string) =>
            fetchWithAuth(`/envelopes/${envelopeId}/credit`, {
                method: "POST",
                body: JSON.stringify({ creditMoney: amount, description }),
                requestId,
            }),
        debitEnvelope: (envelopeId: string, amount: string, description: string, requestId: string) =>
            fetchWithAuth(`/envelopes/${envelopeId}/debit`, {
                method: "POST",
                body: JSON.stringify({ debitMoney: amount, description }),
                requestId,
            }),
        deleteEnvelope: (envelopeId: string, requestId: string) =>
            fetchWithAuth(`/envelopes/${envelopeId}`, { method: "DELETE", requestId }),
        nameEnvelope: (envelopeId: string, name: string, requestId: string) =>
            fetchWithAuth(`/envelopes/${envelopeId}/name`, { method: "POST", body: JSON.stringify({ name }), requestId }),
        updateTargetBudget: (envelopeId: string, targetedAmount: string, currentAmount: string, requestId: string) =>
            fetchWithAuth(`/envelopes/${envelopeId}/change-target-budget`, {
                method: "POST",
                body: JSON.stringify({ targetedAmount, currentAmount }),
                requestId,
            }),
    },

  envelopeQueries: {
    listEnvelopes: () => fetchWithAuth("/envelopes"),
    getEnvelopeDetails: (uuid: string) => fetchWithAuth(`/envelopes/${uuid}`),
  }
}
