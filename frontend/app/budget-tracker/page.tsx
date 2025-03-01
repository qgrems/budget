"use client"

import { useEffect, useState } from "react"
import { useRouter } from "next/navigation"
import { useUser } from "../domain/user/userHooks"
import { useTranslation } from "../hooks/useTranslation"
import { useBudgetPlans } from "../domain/budget/budgetHooks"
import BudgetCalendar from "../components/budget/BudgetCalendar"
import BudgetPlanDetails from "../components/budget/BudgetPlanDetails"
import CreateBudgetPlanModal from "../components/budget/CreateBudgetPlanModal"
import CreateFromExistingModal from "../components/budget/CreateFromExistingModal"
import { useError } from "../contexts/ErrorContext"
import { useValidMessage } from "../contexts/ValidContext"
import { PieChart, Calculator, Calendar } from "lucide-react"
import { api } from "../infrastructure/api"
import type { Category } from "../domain/budget/budgetTypes"

export default function BudgetTrackerPage() {
    const router = useRouter()
    const { user, loading: userLoading } = useUser()
    const { t } = useTranslation()
    const { error, setError } = useError()
    const { setValidMessage } = useValidMessage()
    const {
        budgetPlansCalendar,
        selectedBudgetPlan,
        loading,
        fetchBudgetPlansCalendar,
        fetchBudgetPlan,
        clearSelectedBudgetPlan,
        createBudgetPlan,
        newlyCreatedBudgetPlanId,
    } = useBudgetPlans()

    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false)
    const [isCreateFromExistingModalOpen, setIsCreateFromExistingModalOpen] = useState(false)
    const [selectedDate, setSelectedDate] = useState<{ year: number; month: number } | null>(null)
    const [categories, setCategories] = useState<{
        needs: Category[]
        wants: Category[]
        savings: Category[]
        incomes: Category[]
    }>({
        needs: [],
        wants: [],
        savings: [],
        incomes: [],
    })

    // Initialize currentYear state
    const [currentYear, setCurrentYear] = useState(() => {
        if (typeof window !== "undefined") {
            const storedYear = localStorage.getItem("selectedYear")
            return storedYear ? Number.parseInt(storedYear, 10) : new Date().getFullYear()
        }
        return new Date().getFullYear()
    })

    useEffect(() => {
        if (!userLoading && !user) {
            router.push("/signin")
        } else if (user) {
            fetchBudgetPlansCalendar()
            fetchCategories()
        }
    }, [user, userLoading, router, fetchBudgetPlansCalendar])

    useEffect(() => {
        if (newlyCreatedBudgetPlanId) {
            router.push(`/budget-tracker/${newlyCreatedBudgetPlanId}`)
        }
    }, [newlyCreatedBudgetPlanId, router])

    // Update local storage when currentYear changes
    useEffect(() => {
        localStorage.setItem("selectedYear", currentYear.toString())
    }, [currentYear])

    const fetchCategories = async () => {
        try {
            const [needs, wants, savings, incomes] = await Promise.all([
                api.budgetQueries.getNeedsCategories(),
                api.budgetQueries.getWantsCategories(),
                api.budgetQueries.getSavingsCategories(),
                api.budgetQueries.getIncomesCategories(),
            ])
            setCategories({ needs, wants, savings, incomes })
        } catch (err) {
            console.error("Failed to fetch categories:", err)
            setError("Failed to fetch categories")
        }
    }

    const handleMonthClick = async (year: number, month: number) => {
        setSelectedDate({ year, month })

        // Check if there's a budget plan for this month
        const budgetPlanId = budgetPlansCalendar?.[year]?.[month]

        if (budgetPlanId) {
            router.push(`/budget-tracker/${budgetPlanId}`)
        } else {
            // Ask if user wants to create a new budget plan
            clearSelectedBudgetPlan()
            setIsCreateModalOpen(true)
        }
    }

    const handleCreateFromExisting = () => {
        if (!selectedDate) return
        setIsCreateModalOpen(false)
        setIsCreateFromExistingModalOpen(true)
    }

    const handleCloseModals = () => {
        setIsCreateModalOpen(false)
        setIsCreateFromExistingModalOpen(false)
    }

    const handleCreateNewBudgetPlan = async (currency: string, incomes: { name: string; amount: number }[]) => {
        if (!selectedDate) return

        await createBudgetPlan(new Date(selectedDate.year, selectedDate.month - 1), currency, incomes)
        handleCloseModals()
    }

    const handleYearChange = (newYear: number) => {
        setCurrentYear(newYear)
    }

    if (userLoading || loading) {
        return (
            <div className="flex justify-center items-center h-screen">
                <div className="animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-primary"></div>
            </div>
        )
    }

    if (!user) return null

    return (
        <div className="container mx-auto px-4 py-6 sm:py-8">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <div>
                    <h1 className="text-2xl sm:text-3xl font-bold mb-2">{t("budgetTracker.title")}</h1>
                    <p className="text-sm sm:text-base text-muted-foreground mb-4 md:mb-0">{t("budgetTracker.subtitle")}</p>
                </div>
                <button
                    onClick={() => setIsCreateModalOpen(true)}
                    className="p-3 neomorphic-button text-primary hover:text-primary-dark transition-colors rounded-full"
                    aria-label={t("budgetTracker.createNew")}
                >
                    <Calculator className="h-6 w-6" />
                </button>
            </div>

            <div className="grid gap-6 md:grid-cols-12">
                <div className="md:col-span-5 lg:col-span-4">
                    <div className="neomorphic p-4 rounded-lg">
                        <h2 className="text-xl font-semibold mb-4 flex items-center">
                            <Calendar className="mr-2 h-5 w-5 text-primary" />
                            {t("budgetTracker.calendar")}
                        </h2>
                        <BudgetCalendar
                            budgetPlansCalendar={budgetPlansCalendar}
                            onMonthClick={handleMonthClick}
                            currentYear={currentYear}
                            onYearChange={handleYearChange}
                        />
                    </div>
                </div>

                <div className="md:col-span-7 lg:col-span-8">
                    {selectedBudgetPlan ? (
                        <BudgetPlanDetails budgetPlan={selectedBudgetPlan} categories={categories} />
                    ) : (
                        <div className="neomorphic p-6 rounded-lg h-full flex flex-col items-center justify-center text-center">
                            <PieChart className="h-16 w-16 text-primary mb-4" />
                            <h2 className="text-xl font-semibold mb-2">{t("budgetTracker.noBudgetSelected")}</h2>
                            <p className="text-muted-foreground mb-4">{t("budgetTracker.selectMonthOrCreate")}</p>
                            <button
                                onClick={() => setIsCreateModalOpen(true)}
                                className="py-2 px-4 neomorphic-button text-primary hover:text-primary-dark transition-colors"
                            >
                                {t("budgetTracker.createNew")}
                            </button>
                        </div>
                    )}
                </div>
            </div>

            {isCreateModalOpen && selectedDate && (
                <CreateBudgetPlanModal
                    isOpen={isCreateModalOpen}
                    onClose={handleCloseModals}
                    onCreateFromExisting={handleCreateFromExisting}
                    onCreateNew={handleCreateNewBudgetPlan}
                    selectedDate={selectedDate}
                    categories={categories}
                />
            )}

            {isCreateFromExistingModalOpen && selectedDate && (
                <CreateFromExistingModal
                    isOpen={isCreateFromExistingModalOpen}
                    onClose={handleCloseModals}
                    selectedDate={selectedDate}
                    budgetPlansCalendar={budgetPlansCalendar}
                />
            )}
        </div>
    )
}
