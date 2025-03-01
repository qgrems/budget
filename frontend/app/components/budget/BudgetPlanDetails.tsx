"use client"

import { useState, useCallback, useEffect } from "react"
import { PieChart, Pie, Cell, ResponsiveContainer, Legend, Tooltip } from "recharts"
import { useTranslation } from "../../hooks/useTranslation"
import { formatCurrency } from "../../utils/envelope/currencyUtils"
import { useBudgetPlans } from "../../domain/budget/budgetHooks"
import type { BudgetPlan } from "../../domain/budget/budgetTypes"
import { Calculator, DollarSign, PiggyBank, ShoppingBag, Plus, Edit2, Trash2, Loader2 } from "lucide-react"
import BudgetItemModal from "./BudgetItemModal"
import DeleteConfirmationModal from "./DeleteConfirmationModal"

interface BudgetPlanDetailsProps {
    budgetPlan: BudgetPlan
}

export default function BudgetPlanDetails({ budgetPlan }: BudgetPlanDetailsProps) {
    const { t, language } = useTranslation()
    const {
        addBudgetItem,
        adjustBudgetItem,
        removeBudgetItem,
        loading,
        fetchBudgetPlan,
        selectedBudgetPlan,
        setSelectedBudgetPlan,
    } = useBudgetPlans()
    const [activeTab, setActiveTab] = useState<"overview" | "needs" | "wants" | "savings" | "incomes">("overview")

    // Modal states
    const [isAddModalOpen, setIsAddModalOpen] = useState(false)
    const [isEditModalOpen, setIsEditModalOpen] = useState(false)
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false)
    const [currentItemType, setCurrentItemType] = useState<"need" | "want" | "saving" | "income">("need")
    const [currentItem, setCurrentItem] = useState<{ id: string; name: string; amount: string } | null>(null)

    useEffect(() => {
        setSelectedBudgetPlan(budgetPlan)
    }, [budgetPlan, setSelectedBudgetPlan])

    const { budgetPlan: planDetails, needs, wants, savings, incomes } = selectedBudgetPlan || {}

    // Calculate totals
    const totalIncome = incomes?.reduce((sum, income) => sum + Number.parseFloat(income.incomeAmount), 0) || 0
    const totalNeeds = needs?.reduce((sum, need) => sum + Number.parseFloat(need.needAmount), 0) || 0
    const totalWants = wants?.reduce((sum, want) => sum + Number.parseFloat(want.wantAmount), 0) || 0
    const totalSavings = savings?.reduce((sum, saving) => sum + Number.parseFloat(saving.savingAmount), 0) || 0

    // Calculate percentages
    const needsPercentage = totalIncome > 0 ? (totalNeeds / totalIncome) * 100 : 0
    const wantsPercentage = totalIncome > 0 ? (totalWants / totalIncome) * 100 : 0
    const savingsPercentage = totalIncome > 0 ? (totalSavings / totalIncome) * 100 : 0

    // Format date
    const formatDate = (dateString: string) => {
        const date = new Date(dateString)
        return language === "fr"
            ? date.toLocaleDateString("fr-FR", { year: "numeric", month: "long" })
            : date.toLocaleDateString("en-US", { year: "numeric", month: "long" })
    }

    // Prepare chart data
    const chartData = [
        { name: t("budgetTracker.needs"), value: totalNeeds, color: "#4CAF50" },
        { name: t("budgetTracker.wants"), value: totalWants, color: "#2196F3" },
        { name: t("budgetTracker.savings"), value: totalSavings, color: "#FFC107" },
    ]

    // Handle opening add modal
    const handleOpenAddModal = (type: "need" | "want" | "saving" | "income") => {
        setCurrentItemType(type)
        setIsAddModalOpen(true)
    }

    // Handle opening edit modal
    const handleOpenEditModal = (
        type: "need" | "want" | "saving" | "income",
        id: string,
        name: string,
        amount: string,
    ) => {
        setCurrentItemType(type)
        setCurrentItem({ id, name, amount })
        setIsEditModalOpen(true)
    }

    // Handle opening delete modal
    const handleOpenDeleteModal = (type: "need" | "want" | "saving" | "income", id: string, name: string) => {
        setCurrentItemType(type)
        setCurrentItem({ id, name, amount: "" })
        setIsDeleteModalOpen(true)
    }

    // Handle add item
    const handleAddItem = useCallback(
        async (name: string, amount: string) => {
            if (await addBudgetItem(currentItemType, name, amount)) {
                setIsAddModalOpen(false)
                if (planDetails) {
                    await fetchBudgetPlan(planDetails.uuid)
                }
            }
        },
        [addBudgetItem, currentItemType, fetchBudgetPlan, planDetails],
    )

    // Handle edit item
    const handleEditItem = useCallback(
        async (name: string, amount: string) => {
            if (currentItem && planDetails) {
                if (await adjustBudgetItem(currentItemType, currentItem.id, name, amount)) {
                    setIsEditModalOpen(false)
                    await fetchBudgetPlan(planDetails.uuid)
                }
            }
        },
        [adjustBudgetItem, currentItem, currentItemType, fetchBudgetPlan, planDetails],
    )

    // Handle delete item
    const handleDeleteItem = useCallback(async () => {
        if (currentItem && planDetails) {
            if (await removeBudgetItem(currentItemType, currentItem.id)) {
                setIsDeleteModalOpen(false)
                await fetchBudgetPlan(planDetails.uuid)
            }
        }
    }, [removeBudgetItem, currentItem, currentItemType, fetchBudgetPlan, planDetails])

    // Get the appropriate field names based on item type
    const getFieldNames = (type: "need" | "want" | "saving" | "income") => {
        switch (type) {
            case "need":
                return { name: "needName", amount: "needAmount" }
            case "want":
                return { name: "wantName", amount: "wantAmount" }
            case "saving":
                return { name: "savingName", amount: "savingAmount" }
            case "income":
                return { name: "incomeName", amount: "incomeAmount" }
        }
    }

    // Render item list based on type
    const renderItemList = (type: "need" | "want" | "saving" | "income", items: any[]) => {
        const fields = getFieldNames(type)

        return (
            <div className="neomorphic-inset p-4 rounded-lg">
                <div className="flex justify-between items-center mb-3">
                    <h3 className="font-semibold flex items-center text-lg">
                        {type === "need" && <DollarSign className="h-5 w-5 mr-1 text-green-600" />}
                        {type === "want" && <ShoppingBag className="h-5 w-5 mr-1 text-blue-600" />}
                        {type === "saving" && <PiggyBank className="h-5 w-5 mr-1 text-amber-600" />}
                        {type === "income" && <Calculator className="h-5 w-5 mr-1 text-purple-600" />}
                        {t(`budgetTracker.${type}s`)}
                        {type !== "income" &&
                            ` (${(type === "need" ? needsPercentage : type === "want" ? wantsPercentage : savingsPercentage).toFixed(0)}%)`}
                    </h3>
                    <button
                        onClick={() => handleOpenAddModal(type)}
                        className="p-2 neomorphic-button text-primary rounded-full"
                        aria-label={t("budgetTracker.addItem")}
                        disabled={loading}
                    >
                        {loading ? <Loader2 className="h-5 w-5 animate-spin" /> : <Plus className="h-5 w-5" />}
                    </button>
                </div>

                <p className="text-sm text-muted-foreground mb-4">{t(`budgetTracker.${type}Description`)}</p>

                <ul className="space-y-3">
                    {items?.map((item) => (
                        <li key={item.uuid} className="flex justify-between items-center p-3 hover:bg-accent rounded-md group">
                            <span className="font-medium">{item[fields.name]}</span>
                            <div className="flex items-center">
                                <span className="font-semibold mr-3">{formatCurrency(item[fields.amount], planDetails?.currency)}</span>
                                <div className="flex space-x-2">
                                    <button
                                        onClick={() => handleOpenEditModal(type, item.uuid, item[fields.name], item[fields.amount])}
                                        className="p-1 text-blue-500 hover:text-blue-700"
                                        disabled={loading}
                                    >
                                        <Edit2 className="h-4 w-4" />
                                    </button>
                                    <button
                                        onClick={() => handleOpenDeleteModal(type, item.uuid, item[fields.name])}
                                        className="p-1 text-red-500 hover:text-red-700"
                                        disabled={loading}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </li>
                    ))}
                    {items?.length === 0 && (
                        <li className="text-center text-muted-foreground py-3">
                            {t(`budgetTracker.no${type.charAt(0).toUpperCase() + type.slice(1)}s`)}
                        </li>
                    )}
                </ul>

                <div className="mt-4 pt-3 border-t border-border">
                    <div className="flex justify-between font-semibold text-lg">
                        <span>{t(`budgetTracker.total${type.charAt(0).toUpperCase() + type.slice(1)}s`)}</span>
                        <span>
              {formatCurrency(
                  type === "need"
                      ? totalNeeds
                      : type === "want"
                          ? totalWants
                          : type === "saving"
                              ? totalSavings
                              : totalIncome,
                  planDetails?.currency,
              )}
            </span>
                    </div>
                </div>
            </div>
        )
    }

    if (!selectedBudgetPlan) {
        return <div className="text-center py-8">Loading budget plan...</div>
    }

    return (
        <div className="neomorphic p-4 rounded-lg">
            <div className="flex flex-col mb-6">
                <h2 className="text-2xl font-semibold mb-2">{planDetails?.date ? formatDate(planDetails.date) : "No Date"}</h2>
                <div className="text-xl font-semibold text-primary">{formatCurrency(totalIncome, planDetails?.currency)}</div>
            </div>

            <div className="mb-6">
                <div className="flex flex-wrap gap-2 mb-4">
                    {["overview", "needs", "wants", "savings", "incomes"].map((tab) => (
                        <button
                            key={tab}
                            onClick={() => setActiveTab(tab as any)}
                            className={`py-2 px-3 rounded-md text-sm ${
                                activeTab === tab
                                    ? "neomorphic-inset text-primary font-semibold"
                                    : "neomorphic-button text-muted-foreground"
                            }`}
                        >
                            {tab === "overview" && <Calculator className="h-4 w-4 inline mr-1" />}
                            {tab === "needs" && <DollarSign className="h-4 w-4 inline mr-1" />}
                            {tab === "wants" && <ShoppingBag className="h-4 w-4 inline mr-1" />}
                            {tab === "savings" && <PiggyBank className="h-4 w-4 inline mr-1" />}
                            {tab === "incomes" && <Calculator className="h-4 w-4 inline mr-1" />}
                            {t(`budgetTracker.${tab}`)}
                        </button>
                    ))}
                </div>

                {activeTab === "overview" && (
                    <div className="space-y-6">
                        <div className="h-64 md:h-80">
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={chartData}
                                        cx="50%"
                                        cy="50%"
                                        innerRadius="40%"
                                        outerRadius="70%"
                                        fill="#8884d8"
                                        paddingAngle={5}
                                        dataKey="value"
                                        label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                                    >
                                        {chartData.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={entry.color} />
                                        ))}
                                    </Pie>
                                    <Tooltip formatter={(value) => formatCurrency(value as number, planDetails?.currency)} />
                                    <Legend />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>

                        <div className="space-y-4">
                            <div className="neomorphic-inset p-4 rounded-lg">
                                <h3 className="font-semibold mb-2 text-lg">{t("budgetTracker.incomes")}</h3>
                                <ul className="space-y-2">
                                    {incomes?.map((income) => (
                                        <li key={income.uuid} className="flex justify-between">
                                            <span>{income.incomeName}</span>
                                            <span className="font-semibold">
                        {formatCurrency(income.incomeAmount, planDetails?.currency)}
                      </span>
                                        </li>
                                    ))}
                                </ul>
                                <div className="mt-3 pt-3 border-t border-border">
                                    <div className="flex justify-between font-semibold text-lg">
                                        <span>{t("budgetTracker.totalIncome")}</span>
                                        <span>{formatCurrency(totalIncome, planDetails?.currency)}</span>
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {[
                                    {
                                        title: t("budgetTracker.needs"),
                                        total: totalNeeds,
                                        percentage: needsPercentage,
                                        color: "text-green-600",
                                    },
                                    {
                                        title: t("budgetTracker.wants"),
                                        total: totalWants,
                                        percentage: wantsPercentage,
                                        color: "text-blue-600",
                                    },
                                    {
                                        title: t("budgetTracker.savings"),
                                        total: totalSavings,
                                        percentage: savingsPercentage,
                                        color: "text-amber-600",
                                    },
                                ].map((item, index) => (
                                    <div key={index} className="neomorphic-inset p-4 rounded-lg">
                                        <h3 className={`font-semibold mb-1 text-lg ${item.color}`}>{item.title}</h3>
                                        <div className="text-xl font-semibold">{formatCurrency(item.total, planDetails?.currency)}</div>
                                        <div className="text-sm text-muted-foreground">
                                            {item.percentage.toFixed(1)}% {t("budgetTracker.ofIncome")}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}

                {activeTab === "needs" && renderItemList("need", needs)}
                {activeTab === "wants" && renderItemList("want", wants)}
                {activeTab === "savings" && renderItemList("saving", savings)}
                {activeTab === "incomes" && renderItemList("income", incomes)}
            </div>

            {/* Add Item Modal */}
            <BudgetItemModal
                isOpen={isAddModalOpen}
                onClose={() => setIsAddModalOpen(false)}
                onSubmit={handleAddItem}
                title={t(`budgetTracker.add${currentItemType.charAt(0).toUpperCase() + currentItemType.slice(1)}`)}
            />

            {/* Edit Item Modal */}
            <BudgetItemModal
                isOpen={isEditModalOpen}
                onClose={() => setIsEditModalOpen(false)}
                onSubmit={handleEditItem}
                title={t(`budgetTracker.edit${currentItemType.charAt(0).toUpperCase() + currentItemType.slice(1)}`)}
                initialName={currentItem?.name || ""}
                initialAmount={currentItem?.amount || ""}
                isEdit={true}
            />

            {/* Delete Confirmation Modal */}
            <DeleteConfirmationModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteItem}
                itemName={currentItem?.name || ""}
            />
        </div>
    )
}
