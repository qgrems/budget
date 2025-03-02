"use client"

import { ChevronLeft, ChevronRight } from "lucide-react"
import { useTranslation } from "../../hooks/useTranslation"
import type { BudgetPlansCalendar } from "../../domain/budget/budgetTypes"

interface BudgetCalendarProps {
    budgetPlansCalendar: BudgetPlansCalendar | null
    onMonthClick: (year: number, month: number) => void
    currentYear: number
    onYearChange: (year: number) => void
}

export default function BudgetCalendar({
                                           budgetPlansCalendar,
                                           onMonthClick,
                                           currentYear,
                                           onYearChange,
                                       }: BudgetCalendarProps) {
    const { t, language } = useTranslation()

    const months =
        language === "fr"
            ? ["Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Août", "Sep", "Oct", "Nov", "Déc"]
            : ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]

    const hasBudgetPlan = (year: number, month: number): boolean => {
        return (
            budgetPlansCalendar?.[year]?.[month]?.uuid !== null && budgetPlansCalendar?.[year]?.[month]?.uuid !== undefined
        )
    }

    const handlePreviousYear = () => {
        onYearChange(currentYear - 1)
    }

    const handleNextYear = () => {
        onYearChange(currentYear + 1)
    }

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center">
                <button
                    onClick={handlePreviousYear}
                    className="p-2 neomorphic-button text-primary"
                    aria-label={t("budgetTracker.previousYear")}
                >
                    <ChevronLeft className="h-4 w-4" />
                </button>
                <h3 className="text-lg font-semibold">{currentYear}</h3>
                <button
                    onClick={handleNextYear}
                    className="p-2 neomorphic-button text-primary"
                    aria-label={t("budgetTracker.nextYear")}
                >
                    <ChevronRight className="h-4 w-4" />
                </button>
            </div>

            <div className="grid grid-cols-3 gap-2">
                {months.map((month, index) => {
                    const hasData = hasBudgetPlan(currentYear, index + 1)
                    const isCurrentMonth = currentYear === new Date().getFullYear() && index === new Date().getMonth()

                    return (
                        <button
                            key={month}
                            onClick={() => onMonthClick(currentYear, index + 1)}
                            className={`
                p-3 text-center rounded-md transition-colors
                ${
                                hasData
                                    ? "neomorphic-button text-primary hover:text-primary-dark"
                                    : "neomorphic-inset text-muted-foreground hover:text-foreground"
                            }
                ${isCurrentMonth ? "border-2 border-primary" : ""}
              `}
                        >
                            {month}
                        </button>
                    )
                })}
            </div>
        </div>
    )
}
