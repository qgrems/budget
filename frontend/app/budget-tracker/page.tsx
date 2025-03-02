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
import { Calculator, Calendar } from "lucide-react"
import { PieChart, Pie, Cell, ResponsiveContainer, Legend, Tooltip } from "recharts"
import { formatCurrency } from "../utils/envelope/currencyUtils"

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
        needsCategories,
        wantsCategories,
        savingsCategories,
        incomesCategories,
    } = useBudgetPlans()

    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false)
    const [isCreateFromExistingModalOpen, setIsCreateFromExistingModalOpen] = useState(false)
    const [selectedDate, setSelectedDate] = useState<{ year: number; month: number } | null>(null)
    const [currentYear, setCurrentYear] = useState(() => new Date().getFullYear())

    useEffect(() => {
        if (!userLoading && !user) {
            router.push("/signin")
        } else if (user) {
            fetchBudgetPlansCalendar(currentYear)
        }
    }, [user, userLoading, router, fetchBudgetPlansCalendar, currentYear])

    useEffect(() => {
        if (newlyCreatedBudgetPlanId) {
            router.push(`/budget-tracker/${newlyCreatedBudgetPlanId}`)
        }
    }, [newlyCreatedBudgetPlanId, router])

    const handleMonthClick = async (year: number, month: number) => {
        setSelectedDate({ year, month })

        const budgetPlanData = budgetPlansCalendar?.[year]?.[month]
        const budgetPlanId = budgetPlanData?.uuid

        if (budgetPlanId && budgetPlanId !== null) {
            router.push(`/budget-tracker/${budgetPlanId}`)
        } else {
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

    const handleCreateNewBudgetPlan = async (
        currency: string,
        incomes: { name: string; amount: number; category: string }[],
    ) => {
        if (!selectedDate) return

        await createBudgetPlan(new Date(selectedDate.year, selectedDate.month - 1), currency, incomes)
        handleCloseModals()
    }

    const handleYearChange = (newYear: number) => {
        setCurrentYear(newYear)
        fetchBudgetPlansCalendar(newYear)
    }

    const renderYearlyCharts = () => {
        if (!budgetPlansCalendar) return null

        const chartTypes = [
            {
                title: "Yearly Income",
                data: budgetPlansCalendar.incomeCategoriesTotal,
                ratio: budgetPlansCalendar.incomeCategoriesRatio,
            },
            {
                title: "Yearly Needs",
                data: budgetPlansCalendar.needCategoriesTotal,
                ratio: budgetPlansCalendar.needCategoriesRatio,
            },
            {
                title: "Yearly Wants",
                data: budgetPlansCalendar.wantCategoriesTotal,
                ratio: budgetPlansCalendar.wantCategoriesRatio,
            },
            {
                title: "Yearly Savings",
                data: budgetPlansCalendar.savingCategoriesTotal,
                ratio: budgetPlansCalendar.savingCategoriesRatio,
            },
        ]

        // Define a color palette
        const colorPalette = [
            "#FF6B6B", // Coral
            "#4ECDC4", // Turquoise
            "#45B7D1", // Sky Blue
            "#FFA07A", // Light Salmon
            "#98D8C8", // Mint
            "#F7DC6F", // Pale Yellow
            "#BB8FCE", // Light Purple
            "#82E0AA", // Light Green
        ]

        return (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                {chartTypes.map((chart, index) => {
                    const chartData = Object.entries(chart.data).map(([name, value]) => ({
                        name,
                        value: Number.parseFloat(value),
                    }))

                    const total = chartData.reduce((sum, item) => sum + item.value, 0)

                    return (
                        <div key={index} className="neomorphic p-4 rounded-lg">
                            <h3 className="text-xl font-semibold mb-4">{chart.title}</h3>
                            <div className="h-64">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <defs>
                                            <filter id={`soft-shadow-${index}`}>
                                                <feDropShadow dx="2" dy="2" stdDeviation="3" floodOpacity="0.2" />
                                                <feDropShadow dx="-2" dy="-2" stdDeviation="3" floodColor="white" floodOpacity="0.4" />
                                            </filter>
                                        </defs>
                                        <Pie
                                            data={chartData}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={60}
                                            outerRadius={80}
                                            paddingAngle={2}
                                            dataKey="value"
                                            filter={`url(#soft-shadow-${index})`}
                                            stroke="hsl(var(--background))"
                                            strokeWidth={2}
                                        >
                                            {chartData.map((entry, i) => (
                                                <Cell
                                                    key={`cell-${i}`}
                                                    fill={colorPalette[i % colorPalette.length]}
                                                    className="drop-shadow-sm"
                                                />
                                            ))}
                                        </Pie>
                                        <Tooltip
                                            formatter={(value, name, props) => [
                                                formatCurrency(value as number, selectedBudgetPlan?.budgetPlan.currency || "USD"),
                                                name,
                                            ]}
                                            contentStyle={{
                                                backgroundColor: "hsl(var(--background))",
                                                border: "none",
                                                borderRadius: "0.5rem",
                                                boxShadow: "var(--neomorphic-shadow)",
                                            }}
                                        />
                                        <Legend
                                            formatter={(value, entry, index) => (
                                                <span style={{ color: colorPalette[index % colorPalette.length] }}>{value}</span>
                                            )}
                                        />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                            <div className="mt-4">
                                <h4 className="font-semibold">Category Ratios:</h4>
                                <ul>
                                    {Object.entries(chart.ratio).map(([category, ratio], i) => (
                                        <li key={category} style={{ color: colorPalette[i % colorPalette.length] }}>
                                            {category}: {ratio}
                                        </li>
                                    ))}
                                </ul>
                                <p className="mt-2 font-semibold">
                                    Total: {formatCurrency(total, selectedBudgetPlan?.budgetPlan.currency || "USD")}
                                </p>
                            </div>
                        </div>
                    )
                })}
            </div>
        )
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
                <div className="flex items-center space-x-4">
                    <select
                        value={currentYear}
                        onChange={(e) => handleYearChange(Number(e.target.value))}
                        className="neomorphic-inset p-2 rounded-md"
                    >
                        {Array.from({ length: 5 }, (_, i) => currentYear - 2 + i).map((year) => (
                            <option key={year} value={year}>
                                {year}
                            </option>
                        ))}
                    </select>
                    <button
                        onClick={() => setIsCreateModalOpen(true)}
                        className="p-3 neomorphic-button text-primary hover:text-primary-dark transition-colors rounded-full"
                        aria-label={t("budgetTracker.createNew")}
                    >
                        <Calculator className="h-6 w-6" />
                    </button>
                </div>
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
                        <BudgetPlanDetails
                            budgetPlan={selectedBudgetPlan}
                            categories={{
                                needs: needsCategories,
                                wants: wantsCategories,
                                savings: savingsCategories,
                                incomes: incomesCategories,
                            }}
                        />
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

            {renderYearlyCharts()}

            {isCreateModalOpen && selectedDate && (
                <CreateBudgetPlanModal
                    isOpen={isCreateModalOpen}
                    onClose={handleCloseModals}
                    onCreateFromExisting={handleCreateFromExisting}
                    onCreateNew={handleCreateNewBudgetPlan}
                    selectedDate={selectedDate}
                    categories={{
                        needs: needsCategories,
                        wants: wantsCategories,
                        savings: savingsCategories,
                        incomes: incomesCategories,
                    }}
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
