import { authService } from "../services/auth"

const API_URL = process.env.NEXT_PUBLIC_API_URL

async function fetchWithAuth<T = any>(endpoint: string, options: RequestInit = {}): Promise<T> {
    return authService.withTokenRefresh(async () => {
        const token = authService.getToken()
        const headers = new Headers({
            "Content-Type": "application/json",
            ...(token && { Authorization: `Bearer ${token}` }),
            ...(options.requestId && { "Request-Id": options.requestId }),
        })

        const response = await fetch(`${API_URL}${endpoint}`, {
            ...options,
            headers,
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
            fetchWithAuth(`/envelopes/${envelopeId}/change-targeted-amount`, {
                method: "POST",
                body: JSON.stringify({ targetedAmount, currentAmount }),
                requestId,
            }),
    },

    envelopeQueries: {
        listEnvelopes: () => fetchWithAuth("/envelopes"),
        getEnvelopeDetails: (uuid: string) => fetchWithAuth(`/envelopes/${uuid}`),
    },

    // New budget plan endpoints
    budgetCommands: {
        createBudgetPlan: (payload: any, requestId: string) =>
            fetchWithAuth("/budget-plans-generate", {
                method: "POST",
                body: JSON.stringify(payload),
                requestId,
            }),
        createBudgetPlanFromExisting: (payload: any, requestId: string) =>
            fetchWithAuth("/budget-plans-generate-with-one-that-already-exists", {
                method: "POST",
                body: JSON.stringify(payload),
                requestId,
            }),
        // New budget item management endpoints
        addNeed: (budgetPlanId: string, payload: any, requestId: string) =>
            fetchWithAuth(`/budget-plans/${budgetPlanId}/add-need`, {
                method: "POST",
                body: JSON.stringify(payload),
                requestId,
            }),
        adjustNeed: (budgetPlanId: string, needId: string, payload: any, requestId: string) =>
            fetchWithAuth(`/budget-plans/${budgetPlanId}/adjust-need/${needId}`, {
                method: "POST",
                body: JSON.stringify(payload),
                requestId,
            }),
        removeNeed: (budgetPlanId: string, needId: string, requestId: string) =>
            fetchWithAuth(`/budget-plans/${budgetPlanId}/remove-need/${needId}`, {
                method: "DELETE",
                requestId,
            }),

        // Want management
        addWant: (budgetPlanId: string, payload: any, requestId: string) =>
            fetchWithAuth(`/budget-plans/${budgetPlanId}/add-want`, {
                method: "POST",
                body: JSON.stringify(payload),
                requestId,
            }),
        adjustWant: (budgetPlanId: string, wantId: string, payload: any, requestId: string) =>
            fetchWithAuth(`/budget-plans/${budgetPlanId}/adjust-want/${wantId}`, {
                method: "POST",
                body: JSON.stringify(payload),
                requestId,
            }),
        removeWant: (budgetPlanId: string, wantId: string, requestId: string) =>
            fetchWithAuth(`/budget-plans/${budgetPlanId}/remove-want/${wantId}`, {
                method: "DELETE",
                requestId,
            }),

        // Saving management
        addSaving: (budgetPlanId: string, payload: any, requestId: string) =>
            fetchWithAuth(`/budget-plans/${budgetPlanId}/add-saving`, {
                method: "POST",
                body: JSON.stringify(payload),
                requestId,
            }),
        adjustSaving: (budgetPlanId: string, savingId: string, payload: any, requestId: string) =>
            fetchWithAuth(`/budget-plans/${budgetPlanId}/adjust-saving/${savingId}`, {
                method: "POST",
                body: JSON.stringify(payload),
                requestId,
            }),
        removeSaving: (budgetPlanId: string, savingId: string, requestId: string) =>
            fetchWithAuth(`/budget-plans/${budgetPlanId}/remove-saving/${savingId}`, {
                method: "DELETE",
                requestId,
            }),

        // Income management
        addIncome: (budgetPlanId: string, payload: any, requestId: string) =>
            fetchWithAuth(`/budget-plans/${budgetPlanId}/add-income`, {
                method: "POST",
                body: JSON.stringify(payload),
                requestId,
            }),
        adjustIncome: (budgetPlanId: string, incomeId: string, payload: any, requestId: string) =>
            fetchWithAuth(`/budget-plans/${budgetPlanId}/adjust-income/${incomeId}`, {
                method: "POST",
                body: JSON.stringify(payload),
                requestId,
            }),
        removeIncome: (budgetPlanId: string, incomeId: string, requestId: string) =>
            fetchWithAuth(`/budget-plans/${budgetPlanId}/remove-income/${incomeId}`, {
                method: "DELETE",
                requestId,
            }),
    },

    budgetQueries: {
        getBudgetPlansCalendar: (year: number) => fetchWithAuth(`/budget-plans-yearly-calendar?year=${year}`),
        getBudgetPlan: (uuid: string) => fetchWithAuth(`/budget-plans/${uuid}`),
        getNeedsCategories: () => fetchWithAuth("/needs-categories"),
        getWantsCategories: () => fetchWithAuth("/wants-categories"),
        getSavingsCategories: () => fetchWithAuth("/savings-categories"),
        getIncomesCategories: () => fetchWithAuth("/incomes-categories"),
    },
}
