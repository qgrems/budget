"use client"

import { motion } from "framer-motion"
import { useTranslation } from "../../hooks/useTranslation"
import ActionButton from "../buttons/actionButton"

interface DeleteConfirmationModalProps {
    isOpen: boolean
    onClose: () => void
    onConfirm: () => void
    itemName: string
}

export default function DeleteConfirmationModal({
                                                    isOpen,
                                                    onClose,
                                                    onConfirm,
                                                    itemName,
                                                }: DeleteConfirmationModalProps) {
    const { t } = useTranslation()

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
                <h2 className="text-xl md:text-2xl font-bold mb-4">{t("budgetTracker.deleteItem")}</h2>
                <p className="mb-6">
                    {t("budgetTracker.confirmDelete")} <strong>{itemName}</strong>
                </p>

                <div className="flex justify-between">
                    <ActionButton onClick={onConfirm} label={t("budgetTracker.delete")} className="text-red-500" />

                    <ActionButton onClick={onClose} label={t("budgetTracker.cancel")} className="text-primary" />
                </div>
            </motion.div>
        </motion.div>
    )
}
