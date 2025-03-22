"use client"

import { useState } from "react"
import { motion } from "framer-motion"
import { useTranslation } from "../../hooks/useTranslation"
import { useBudgetPlans } from "../../domain/budget/budgetHooks"
import ActionButton from "../buttons/actionButton"
import type { BudgetPlansCalendar } from "../../domain/budget/budgetTypes"

interface CreateFromExistingModalProps {
    isOpen: boolean
    onClose: () => void
    selectedDate: { year: number; month: number }
    budgetPlansCalendar: BudgetPlansCalendar | null
}

export default function CreateFromExistingModal({
    isOpen,
    onClose,
    selectedDate,
    budgetPlansCalendar,
}: CreateFromExistingModalProps) {
    const { t, language } = useTranslation()
    const { createBudgetPlanFromExisting, loading } = useBudgetPlans()

    const [selectedPlanId, setSelectedPlanId] = useState<string | null>(null)

    // Format date for display
    const formatDate = (year: number, month: number) => {
        const date = new Date(year, month - 1, 1)
        return language === "fr"
            ? date.toLocaleDateString("fr-FR", { year: "numeric", month: "long" })
            : date.toLocaleDateString("en-US", { year: "numeric", month: "long" })
    }

    // Get all available budget plans
    const getAvailablePlans = () => {
        if (!budgetPlansCalendar) return []

        const plans: Array<{ year: number; month: number; id: string }> = []
        console.log(budgetPlansCalendar)
        Object.entries(budgetPlansCalendar).forEach(([year, months]) => {
            Object.entries(months).forEach(([month, id]) => {
                // Don't include the current selected date
                if (!(Number.parseInt(year) === selectedDate.year && Number.parseInt(month) === selectedDate.month)) {
                    plans.push({
                        year: Number.parseInt(year),
                        month: Number.parseInt(month),
                        id,
                    })
                }
            })
        })

        // Sort by date (newest first)
        return plans.sort((a, b) => {
            if (a.year !== b.year) return b.year - a.year
            return b.month - a.month
        })
    }

    const availablePlans = getAvailablePlans()

    const handleSubmit = async () => {
        if (!selectedPlanId) return

        // Create a Date object for the first day of the selected month in UTC
        const date = new Date(Date.UTC(selectedDate.year, selectedDate.month - 1, 1))

        const success = await createBudgetPlanFromExisting(date, selectedPlanId)

        if (success) {
            onClose()
        }
    }

    if (!isOpen) return null

    return (
        <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        >
            <motion.div
                initial={{ scale: 0.8, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                exit={{ scale: 0.8, opacity: 0 }}
                className="neomorphic p-4 md:p-6 w-full max-w-md bg-white rounded-lg"
            >
                <h2 className="text-xl md:text-2xl font-bold mb-4">{t("budgetTracker.createFromExisting")}</h2>
                <p className="text-sm text-muted-foreground mb-4">{t("budgetTracker.createFromExistingDescription")}</p>

                <div className="mb-4">
                    <label className="block text-sm font-medium mb-1">{t("budgetTracker.targetMonth")}</label>
                    <div className="neomorphic-inset p-3 rounded-md">{formatDate(selectedDate.year, selectedDate.month)}</div>
                </div>

                <div className="mb-6">
                    <label className="block text-sm font-medium mb-2">{t("budgetTracker.selectExistingPlan")}</label>

                    {availablePlans.length === 0 ? (
                        <div className="neomorphic-inset p-3 rounded-md text-muted-foreground">
                            {t("budgetTracker.noExistingPlans")}
                        </div>
                    ) : (
                        <div className="space-y-2 max-h-60 overflow-y-auto neomorphic-inset p-2 rounded-md">
                            {availablePlans.map((plan) => (
                                <button
                                    key={plan.uuid}
                                    onClick={() => setSelectedPlanId(plan.id)}
                                    className={`w-full text-left p-3 rounded-md transition-colors ${selectedPlanId === plan.id ? "neomorphic-button bg-primary/10 text-primary" : "hover:bg-accent"
                                        }`}
                                >
                                    {formatDate(plan.year, plan.month)}
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                <div className="flex justify-between">
                    <ActionButton
                        onClick={handleSubmit}
                        label={t("budgetTracker.createPlan")}
                        disabled={loading || !selectedPlanId}
                        className="text-primary"
                    />

                    <ActionButton onClick={onClose} label={t("budgetTracker.cancel")} className="text-red-500" />
                </div>
            </motion.div>
        </motion.div>
    )
}
