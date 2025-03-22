"use client"

import { useState } from "react"
import { motion } from "framer-motion"
import { useTranslation } from "../../hooks/useTranslation"
import InputText from "../inputs/envelopeInput/textInput"
import InputNumber from "../inputs/inputNumber"
import ActionButton from "../buttons/actionButton"
import type { Category } from "../../domain/budget/budgetTypes"

interface BudgetItemModalProps {
    isOpen: boolean
    onClose: () => void
    onSubmit: (name: string, amount: string, category: string) => void
    title: string
    initialName?: string
    initialAmount?: string
    initialCategory?: string
    isEdit?: boolean
    itemType: "need" | "want" | "saving" | "income"
    categories: {
        needs: Category[]
        wants: Category[]
        savings: Category[]
        incomes: Category[]
    }
}

export default function BudgetItemModal({
    isOpen,
    onClose,
    onSubmit,
    title,
    initialName = "",
    initialAmount = "",
    initialCategory = "",
    isEdit = false,
    itemType,
    categories,
}: BudgetItemModalProps) {
    const { t } = useTranslation()
    const [name, setName] = useState(initialName)
    const [amount, setAmount] = useState(initialAmount)
    const [category, setCategory] = useState(initialCategory)

    const itemCategories =
        categories[
        itemType === "need" ? "needs" : itemType === "want" ? "wants" : itemType === "saving" ? "savings" : "incomes"
        ]

    const handleAmountChange = (value: string) => {
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

        setAmount(value)
    }

    const handleSubmit = () => {
        // Format amount to have two decimal places
        let formattedAmount = amount
        if (formattedAmount.includes(".")) {
            const [integerPart, decimalPart] = formattedAmount.split(".")
            formattedAmount = `${integerPart}.${(decimalPart || "").padEnd(2, "0").slice(0, 2)}`
        } else {
            formattedAmount = `${formattedAmount}.00`
        }

        onSubmit(name, formattedAmount, category)
        onClose()
    }

    const isValid = name.trim() !== "" && amount.trim() !== "" && /^\d+(\.\d{0,2})?$/.test(amount) && category !== ""

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
                <h2 className="text-xl md:text-2xl font-bold mb-4">{title}</h2>

                <div className="mb-4">
                    <label className="block text-sm font-medium mb-1">{t("budgetTracker.itemName")}</label>
                    <InputText value={name} onChange={setName} placeholder={t("budgetTracker.itemName")} className="mb-0" />
                </div>

                <div className="mb-4">
                    <label className="block text-sm font-medium mb-1">{t("budgetTracker.itemAmount")}</label>
                    <InputNumber value={amount} onChange={handleAmountChange} placeholder="0.00" className="w-full mb-0" />
                </div>

                <div className="mb-6">
                    <label className="block text-sm font-medium mb-1">{t("budgetTracker.itemCategory")}</label>
                    <select
                        value={category}
                        onChange={(e) => setCategory(e.target.value)}
                        className="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                        <option value="">{t("budgetTracker.selectCategory")}</option>
                        {itemCategories.map((category: Category) => (
                            <option key={category.id} value={category.id}>
                                {category.name}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="flex justify-between">
                    <ActionButton
                        onClick={handleSubmit}
                        label={t("budgetTracker.save")}
                        disabled={!isValid}
                        className="text-primary"
                    />

                    <ActionButton onClick={onClose} label={t("budgetTracker.cancel")} className="text-red-500" />
                </div>
            </motion.div>
        </motion.div>
    )
}
