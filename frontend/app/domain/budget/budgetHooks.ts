"use client"

import { useState, useCallback, useEffect } from "react"
import { v4 as uuidv4 } from "uuid"
import { api } from "../../infrastructure/api"
import { useSocket } from "../../hooks/useSocket"
import { useError } from "../../contexts/ErrorContext"
import { useValidMessage } from "../../contexts/ValidContext"
import { useTranslation } from "../../hooks/useTranslation"
import type {
    BudgetPlansCalendar,
    BudgetPlan,
    Income,
    CreateBudgetPlanPayload,
    CreateFromExistingPayload,
    Category,
} from "./budgetTypes"

export function useBudgetPlans() {
    const [budgetPlansCalendar, setBudgetPlansCalendar] = useState<BudgetPlansCalendar | null>(null)
    const [selectedBudgetPlan, setSelectedBudgetPlan] = useState<BudgetPlan | null>(null)
    const [loading, setLoading] = useState(false)
    const [newlyCreatedBudgetPlanId, setNewlyCreatedBudgetPlanId] = useState<string | null>(null)
    const [needsCategories, setNeedsCategories] = useState<Category[]>([])
    const [wantsCategories, setWantsCategories] = useState<Category[]>([])
    const [savingsCategories, setSavingsCategories] = useState<Category[]>([])
    const [incomesCategories, setIncomesCategories] = useState<Category[]>([])
    const { socket } = useSocket()
    const { setError } = useError()
    const { setValidMessage } = useValidMessage()
    const { t } = useTranslation()

    const fetchCategories = useCallback(async () => {
        try {
            const [needs, wants, savings, incomes] = await Promise.all([
                api.budgetQueries.getNeedsCategories(),
                api.budgetQueries.getWantsCategories(),
                api.budgetQueries.getSavingsCategories(),
                api.budgetQueries.getIncomesCategories(),
            ])
            setNeedsCategories(needs)
            setWantsCategories(wants)
            setSavingsCategories(savings)
            setIncomesCategories(incomes)
        } catch (err) {
            console.error("Failed to fetch categories:", err)
            setError("budgetTracker.fetchCategoriesError")
        }
    }, [setError])

    useEffect(() => {
        fetchCategories()
    }, [fetchCategories])

    const fetchBudgetPlansCalendar = useCallback(
        async (year: number) => {
            setLoading(true)
            try {
                const data = await api.budgetQueries.getBudgetPlansCalendar(year)
                setBudgetPlansCalendar(data)
            } catch (err) {
                console.error("Failed to fetch budget plans calendar:", err)
                setError("budgetTracker.fetchCalendarError")
            } finally {
                setLoading(false)
            }
        },
        [setError],
    )

    const fetchBudgetPlan = useCallback(
        async (budgetPlanId: string) => {
            setLoading(true)
            try {
                const data = await api.budgetQueries.getBudgetPlan(budgetPlanId)
                setSelectedBudgetPlan(data)
            } catch (err) {
                console.error("Failed to fetch budget plan:", err)
                setError("budgetTracker.fetchPlanError")
            } finally {
                setLoading(false)
            }
        },
        [setError],
    )

    const clearSelectedBudgetPlan = useCallback(() => {
        setSelectedBudgetPlan(null)
    }, [])

    const createBudgetPlan = useCallback(
        async (date: Date | string, currency: string, incomes: Income[]) => {
            setLoading(true)
            const requestId = uuidv4()

            try {
                const formattedDate = typeof date === "string" ? date : date.toISOString()

                const payload: CreateBudgetPlanPayload = {
                    uuid: requestId,
                    currency,
                    date: formattedDate,
                    incomes: incomes.map((income) => ({
                        uuid: uuidv4(),
                        incomeName: income.name,
                        amount: income.amount,
                        category: income.category,
                    })),
                }

                await api.budgetCommands.createBudgetPlan(payload, requestId)
                setValidMessage("budgetTracker.createSuccess")
                setNewlyCreatedBudgetPlanId(requestId)
                return requestId
            } catch (err) {
                console.error("Failed to create budget plan:", err)
                setError("budgetTracker.createError")
                return null
            } finally {
                setLoading(false)
            }
        },
        [setError, setValidMessage],
    )

    const createBudgetPlanFromExisting = useCallback(
        async (date: Date, existingBudgetPlanId: string) => {
            setLoading(true)
            const requestId = uuidv4()

            try {
                const payload: CreateFromExistingPayload = {
                    uuid: requestId,
                    budgetPlanUuidThatAlreadyExists: existingBudgetPlanId,
                    date: date.toISOString(),
                }

                await api.budgetCommands.createBudgetPlanFromExisting(payload, requestId)
                setValidMessage("budgetTracker.createFromExistingSuccess")
                setNewlyCreatedBudgetPlanId(requestId)
                return requestId
            } catch (err) {
                console.error("Failed to create budget plan from existing:", err)
                setError("budgetTracker.createFromExistingError")
                return null
            } finally {
                setLoading(false)
            }
        },
        [setError, setValidMessage],
    )

    // Budget item management functions
    const addBudgetItem = useCallback(
        async (type: "need" | "want" | "saving" | "income", name: string, amount: string, category: string) => {
            if (!selectedBudgetPlan) return false

            setLoading(true)
            const requestId = uuidv4()
            const itemId = uuidv4()

            try {
                const payload = {
                    uuid: itemId,
                    name,
                    amount,
                    category,
                }

                let apiFunction
                switch (type) {
                    case "need":
                        apiFunction = api.budgetCommands.addNeed
                        break
                    case "want":
                        apiFunction = api.budgetCommands.addWant
                        break
                    case "saving":
                        apiFunction = api.budgetCommands.addSaving
                        break
                    case "income":
                        apiFunction = api.budgetCommands.addIncome
                        break
                }

                await apiFunction(selectedBudgetPlan.budgetPlan.uuid, payload, requestId)
                setValidMessage(`budgetTracker.${type}Added`)
                return true
            } catch (err) {
                console.error(`Failed to add ${type}:`, err)
                setError(`budgetTracker.${type}AddError`)
                return false
            } finally {
                setLoading(false)
            }
        },
        [selectedBudgetPlan, setError, setValidMessage],
    )

    const adjustBudgetItem = useCallback(
        async (
            type: "need" | "want" | "saving" | "income",
            itemId: string,
            name: string,
            amount: string,
            category: string,
        ) => {
            if (!selectedBudgetPlan) return false

            setLoading(true)
            const requestId = uuidv4()

            try {
                const payload = {
                    name,
                    amount,
                    category,
                }

                let apiFunction
                switch (type) {
                    case "need":
                        apiFunction = api.budgetCommands.adjustNeed
                        break
                    case "want":
                        apiFunction = api.budgetCommands.adjustWant
                        break
                    case "saving":
                        apiFunction = api.budgetCommands.adjustSaving
                        break
                    case "income":
                        apiFunction = api.budgetCommands.adjustIncome
                        break
                }

                await apiFunction(selectedBudgetPlan.budgetPlan.uuid, itemId, payload, requestId)
                setValidMessage(`budgetTracker.${type}Adjusted`)
                return true
            } catch (err) {
                console.error(`Failed to adjust ${type}:`, err)
                setError(`budgetTracker.${type}AdjustError`)
                return false
            } finally {
                setLoading(false)
            }
        },
        [selectedBudgetPlan, setError, setValidMessage],
    )

    const removeBudgetItem = useCallback(
        async (type: "need" | "want" | "saving" | "income", itemId: string) => {
            if (!selectedBudgetPlan) return false

            setLoading(true)
            const requestId = uuidv4()

            try {
                let apiFunction
                switch (type) {
                    case "need":
                        apiFunction = api.budgetCommands.removeNeed
                        break
                    case "want":
                        apiFunction = api.budgetCommands.removeWant
                        break
                    case "saving":
                        apiFunction = api.budgetCommands.removeSaving
                        break
                    case "income":
                        apiFunction = api.budgetCommands.removeIncome
                        break
                }

                await apiFunction(selectedBudgetPlan.budgetPlan.uuid, itemId, requestId)
                setValidMessage(`budgetTracker.${type}Removed`)
                return true
            } catch (err) {
                console.error(`Failed to remove ${type}:`, err)
                setError(`budgetTracker.${type}RemoveError`)
                return false
            } finally {
                setLoading(false)
            }
        },
        [selectedBudgetPlan, setError, setValidMessage],
    )

    // Listen for budget plan events
    useEffect(() => {
        if (!socket) return

        const handleBudgetPlanEvent = (event: {
            aggregateId: string
            type: string
        }) => {
            console.log("Received WebSocket event:", event)

            // Refresh calendar after budget plan events
            if (budgetPlansCalendar) {
                const year = new Date().getFullYear()
                fetchBudgetPlansCalendar(year)
            }

            // If the event is for the currently selected budget plan, refresh it
            if (selectedBudgetPlan && event.aggregateId === selectedBudgetPlan.budgetPlan.uuid) {
                fetchBudgetPlan(event.aggregateId)
            }

            // If this is a newly created budget plan, set it as the selected plan
            if (event.type === "BudgetPlanGenerated" && event.aggregateId === newlyCreatedBudgetPlanId) {
                fetchBudgetPlan(event.aggregateId)
                setNewlyCreatedBudgetPlanId(null)
            }
        }

        const eventTypes = [
            "BudgetPlanCurrencyChanged",
            "BudgetPlanGenerated",
            "BudgetPlanGeneratedWithOneThatAlreadyExists",
            "BudgetPlanIncomeAdded",
            "BudgetPlanIncomeAdjusted",
            "BudgetPlanIncomeRemoved",
            "BudgetPlanNeedAdded",
            "BudgetPlanNeedAdjusted",
            "BudgetPlanNeedRemoved",
            "BudgetPlanRemoved",
            "BudgetPlanSavingAdded",
            "BudgetPlanSavingAdjusted",
            "BudgetPlanSavingRemoved",
            "BudgetPlanWantAdded",
            "BudgetPlanWantAdjusted",
            "BudgetPlanWantRemoved",
        ]

        eventTypes.forEach((eventType) => {
            socket.on(eventType, handleBudgetPlanEvent)
        })

        return () => {
            eventTypes.forEach((eventType) => {
                socket.off(eventType, handleBudgetPlanEvent)
            })
        }
    }, [
        socket,
        fetchBudgetPlansCalendar,
        fetchBudgetPlan,
        selectedBudgetPlan,
        newlyCreatedBudgetPlanId,
        budgetPlansCalendar,
    ])

    return {
        budgetPlansCalendar,
        selectedBudgetPlan,
        setSelectedBudgetPlan,
        loading,
        fetchBudgetPlansCalendar,
        fetchBudgetPlan,
        clearSelectedBudgetPlan,
        createBudgetPlan,
        createBudgetPlanFromExisting,
        addBudgetItem,
        adjustBudgetItem,
        removeBudgetItem,
        newlyCreatedBudgetPlanId,
        needsCategories,
        wantsCategories,
        savingsCategories,
        incomesCategories,
    }
}
