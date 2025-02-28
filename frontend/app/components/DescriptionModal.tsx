import { useState } from "react"
import { motion } from "framer-motion"
import { useTranslation } from "../hooks/useTranslation"
import ActionButton from "./buttons/actionButton"

interface DescriptionModalProps {
    isOpen: boolean
    onClose: () => void
    onSubmit: (description: string) => void
    actionType: "credit" | "debit"
}

export function DescriptionModal({ isOpen, onClose, onSubmit, actionType }: DescriptionModalProps) {
    const [description, setDescription] = useState("")
    const { t } = useTranslation()

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        onSubmit(description.trim())
        setDescription("")

    }

    const isValidDescription = (value: string) => {
        return /^[a-zA-Z\s]{0,35}$/.test(value)
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
                <h2 className="text-xl md:text-2xl font-bold mb-4">{t(`envelopes.${actionType}Description`)}</h2>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-1">
                            {t("envelopes.description")}
                        </label>
                        <input
                            type="text"
                            id="description"
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            className="w-full p-2 neomorphic-input"
                            maxLength={35}
                            placeholder={t("envelopes.descriptionPlaceholder")}
                        />
                        {!isValidDescription(description) && (
                            <p className="text-red-500 text-sm mt-1">{t("envelopes.descriptionError")}</p>
                        )}
                    </div>
                    <div className="flex justify-between">
                        <ActionButton
                            onClick={() => handleSubmit}
                            label={t("envelopes.submit")}
                            className="py-2 px-4 neomorphic-button text-primary"
                        />
                        <ActionButton
                            onClick={onClose}
                            label={t("envelopes.cancel")}
                            className="py-2 px-4 neomorphic-button text-red-500"
                        />
                    </div>
                </form>
            </motion.div>
        </motion.div>
    )
}
