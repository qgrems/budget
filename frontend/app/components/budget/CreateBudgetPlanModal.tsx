"use client"

import type React from "react"

import { useState, useEffect } from "react"
import { motion } from "framer-motion"
import { useTranslation } from "../../hooks/useTranslation"
import { useBudgetPlans } from "../../domain/budget/budgetHooks"
import { currencyOptions } from "../../constants/currencyOption"
import { Plus, Trash2 } from "lucide-react"
import ActionButton from "../buttons/actionButton"
import InputText from "../inputs/inputText"
import InputNumber from "../inputs/inputNumber"
import CurrencySelect from "../inputs/currencySelect"
import type { Income, Category } from "../../domain/budget/budgetTypes"

interface CreateBudgetPlanModalProps {
    isOpen: boolean
    onClose: () => void
    onCreateFromExisting: () => void
    selectedDate: { year: number; month: number }
    categories: {
        needs: Category[]
        wants: Category[]
        savings: Category[]
        incomes: Category[]
    }
}

export default function CreateBudgetPlanModal({
                                                  isOpen,
                                                  onClose,
                                                  onCreateFromExisting,
                                                  selectedDate,
                                                  categories,
                                              }: CreateBudgetPlanModalProps) {
    const { t } = useTranslation()
    const { createBudgetPlan, loading, redirectToBudgetPlanId } = useBudgetPlans(categories)

    const [currency, setCurrency] = useState("USD")
    const [incomes, setIncomes] = useState<Income[]>([{ name: t("budgetTracker.salary"), amount: "", category: "" }])

    useEffect(() => {
        console.log("CreateBudgetPlanModal rendered. isOpen:", isOpen)
    }, [isOpen])

    const handleCurrencyChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        setCurrency(e.target.value)
        console.log("Currency changed to:", e.target.value)
    }

    const handleAddIncome = () => {
        setIncomes([...incomes, { name: "", amount: "", category: "" }])
        console.log("Income added. New incomes:", incomes)
    }

    const handleRemoveIncome = (index: number) => {
        const newIncomes = incomes.filter((_, i) => i !== index)
        setIncomes(newIncomes)
        console.log("Income removed. New incomes:", newIncomes)
    }

    const handleIncomeNameChange = (index: number, value: string) => {
        const newIncomes = [...incomes]
        newIncomes[index].name = value
        setIncomes(newIncomes)
        console.log("Income name changed. New incomes:", newIncomes)
    }

    const handleIncomeAmountChange = (index: number, value: string) => {
        // Remove any non-digit and non-dot characters
        value = value.replace(/[^\d.]/g, "")

        // Handle cases where the decimal point might be the first character
        if (value.startsWith(".")) {
            value = "0" + value
        }

        // Ensure only one decimal point
        const parts = value.split(".")
        if (parts.length > 2) {
            parts.pop()
            value = parts.join(".")
        }

        // Enforce character limits
        if (value.includes(".")) {
            // With decimal: limit to 13 characters (10 before decimal, 1 decimal point, 2 after decimal)
            const [integerPart, decimalPart] = value.split(".")
            value = `${integerPart.slice(0, 10)}.${decimalPart.slice(0, 2)}`
        } else {
            // Without decimal: limit to 10 characters
            value = value.slice(0, 10)
        }

        // Remove leading zeros, except if it's "0." or "0"
        if (value.length > 1 && value.startsWith("0") && !value.startsWith("0.")) {
            value = value.replace(/^0+/, "")
        }

        const newIncomes = [...incomes]
        newIncomes[index].amount = value
        setIncomes(newIncomes)
        console.log("Income amount changed. New incomes:", newIncomes)
    }

    const handleIncomeCategoryChange = (index: number, value: string) => {
        const newIncomes = [...incomes]
        newIncomes[index].category = value
        setIncomes(newIncomes)
        console.log("Income category changed. New incomes:", newIncomes)
    }

    const isFormValid = () => {
        const valid =
            currency &&
            incomes.length > 0 &&
            incomes.every((income) => income.name.trim() && income.amount.trim() && income.category.trim())
        console.log("Form validation result:", valid)
        return valid
    }

    const handleSubmit = async () => {
        if (!isFormValid()) {
            console.log("Form is not valid. Submission prevented.")
            return
        }

        console.log("Submitting form with data:", { selectedDate, currency, incomes })

        // Create a Date object for the first day of the selected month in UTC
        const date = new Date(Date.UTC(selectedDate.year, selectedDate.month - 1, 1))

        try {
            const newBudgetPlanId = await createBudgetPlan(date, currency, incomes)
            console.log("Budget plan creation initiated with ID:", newBudgetPlanId)
        } catch (error) {
            console.error("Error creating budget plan:", error)
        }
    }

    // Add a useEffect to handle redirection
    useEffect(() => {
        if (redirectToBudgetPlanId) {
            console.log("Redirecting to newly created budget plan:", redirectToBudgetPlanId)
            onClose()
            // You might want to use router.push here to navigate to the new budget plan page
            // router.push(`/budget-tracker/${redirectToBudgetPlanId}`)
        }
    }, [redirectToBudgetPlanId, onClose])

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
                <h2 className="text-xl md:text-2xl font-bold mb-4">{t("budgetTracker.createBudgetPlan")}</h2>
                <p className="text-sm text-muted-foreground mb-4">{t("budgetTracker.createBudgetPlanDescription")}</p>

                <div className="mb-4">
                    <label className="block text-sm font-medium mb-1">{t("budgetTracker.month")}</label>
                    <div className="neomorphic-inset p-3 rounded-md">
                        {new Date(selectedDate.year, selectedDate.month - 1, 1).toLocaleDateString(undefined, {
                            year: "numeric",
                            month: "long",
                        })}
                    </div>
                </div>

                <div className="mb-4">
                    <label className="block text-sm font-medium mb-1">{t("budgetTracker.currency")}</label>
                    <CurrencySelect
                        options={currencyOptions}
                        onChange={handleCurrencyChange}
                        value={currency}
                        className="w-full"
                    />
                </div>

                <div className="mb-4">
                    <div className="flex justify-between items-center mb-2">
                        <label className="block text-sm font-medium">{t("budgetTracker.incomes")}</label>
                        <button
                            onClick={handleAddIncome}
                            className="p-1 neomorphic-button text-primary"
                            aria-label={t("budgetTracker.addIncome")}
                        >
                            <Plus className="h-4 w-4" />
                        </button>
                    </div>

                    {incomes.map((income, index) => (
                        <div key={index} className="flex items-center space-x-2 mb-2">
                            <div className="flex-1">
                                <InputText
                                    value={income.name}
                                    onChange={(value) => handleIncomeNameChange(index, value)}
                                    placeholder={t("budgetTracker.incomeName")}
                                    className="mb-0"
                                />
                            </div>
                            <div className="w-1/3">
                                <InputNumber
                                    value={income.amount}
                                    onChange={(value) => handleIncomeAmountChange(index, value)}
                                    placeholder="0.00"
                                    className="mb-0 w-full"
                                />
                            </div>
                            <div className="w-1/3">
                                <select
                                    value={income.category}
                                    onChange={(e) => handleIncomeCategoryChange(index, e.target.value)}
                                    className="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                                >
                                    <option value="">{t("budgetTracker.selectCategory")}</option>
                                    {categories.incomes &&
                                        categories.incomes.map((category: Category) => (
                                            <option key={category.id} value={category.id}>
                                                {category.name}
                                            </option>
                                        ))}
                                </select>
                            </div>
                            {incomes.length > 1 && (
                                <button
                                    onClick={() => handleRemoveIncome(index)}
                                    className="p-1 neomorphic-button text-red-500"
                                    aria-label={t("budgetTracker.removeIncome")}
                                >
                                    <Trash2 className="h-4 w-4" />
                                </button>
                            )}
                        </div>
                    ))}
                </div>

                <div className="flex justify-between">
                    <ActionButton
                        onClick={handleSubmit}
                        label={t("budgetTracker.createPlan")}
                        disabled={loading || !isFormValid()}
                        className="text-primary"
                    />

                    <ActionButton
                        onClick={onCreateFromExisting}
                        label={t("budgetTracker.createFromExisting")}
                        className="text-blue-500"
                    />

                    <ActionButton onClick={onClose} label={t("budgetTracker.cancel")} className="text-red-500" />
                </div>
            </motion.div>
        </motion.div>
    )
}
