"use client"

import React, { useEffect } from "react"
import { useRouter } from "next/navigation"
import { useUser } from "../../domain/user/userHooks"
import { useBudgetPlans } from "../../domain/budget/budgetHooks"
import BudgetPlanDetails from "../../components/budget/BudgetPlanDetails"
import { useTranslation } from "../../hooks/useTranslation"

export default function BudgetPlanPage({ params }: { params: Promise<{ uuid: string }> }) {
    const router = useRouter()
    const { user, loading: userLoading } = useUser()
    const { t } = useTranslation()
    const { selectedBudgetPlan, loading, fetchBudgetPlan } = useBudgetPlans()

    // Unwrap the params object
    const { uuid } = React.use(params)

    useEffect(() => {
        if (!userLoading && !user) {
            router.push("/signin")
        } else if (user && uuid) {
            fetchBudgetPlan(uuid)
        }
    }, [user, userLoading, router, uuid, fetchBudgetPlan])

    if (userLoading || loading) {
        return (
            <div className="flex justify-center items-center h-screen">
                <div className="animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-primary"></div>
            </div>
        )
    }

    if (!user) return null

    return (
        <div className="container mx-auto px-4 py-6 sm:py=8">
            <h1 className="text-2xl sm:text-3xl font-bold mb-6">{t("budgetTracker.planDetails")}</h1>
            {selectedBudgetPlan ? (
                <BudgetPlanDetails budgetPlan={selectedBudgetPlan} />
            ) : (
                <div className="text-center">
                    <p>{t("budgetTracker.planNotFound")}</p>
                    <button
                        onClick={() => router.push("/budget-tracker")}
                        className="mt-4 py-2 px-4 neomorphic-button text-primary hover:text-primary-dark transition-colors"
                    >
                        {t("budgetTracker.backToCalendar")}
                    </button>
                </div>
            )}
        </div>
    )
}
