"use client"

import { useState, useEffect, useCallback, useMemo } from "react"
import { useEnvelopes } from "../domain/envelope/envelopeHooks"
import { PlusCircle, Trash2, Edit2, Loader2, Check, X } from "lucide-react"
import { PieChart, Pie, Cell, ResponsiveContainer, Label } from "recharts"
import { motion, AnimatePresence } from "framer-motion"
import { DeleteConfirmationModal } from "./DeleteConfirmationModal"
import { useTranslation } from "../hooks/useTranslation"
import { useError } from "../contexts/ErrorContext"
import { useValidMessage } from "../contexts/ValidContext"
import Link from "next/link"
import { DescriptionModal } from "./DescriptionModal"
import type React from "react"
import InputNumber from "./inputs/inputNumber"
import ActionButton from "./buttons/actionButton"
import InputNameEnvelope from "./inputs/inputNameEnvelope"
import InputText from "./inputs/inputText"
import ValidInputButton from "./buttons/validInputButton"
import EnvelopeCard from "./card/EnvelopeCard"
import DeletButton from "./buttons/deletButton"
import cancelEditing from "../utils/form/CancelEditing"
import isInvalidInput from "../utils/validation/IsInvalidValidInput"
import { handleDebitEnvelope } from "../services/envelopeService/debitEnvelope"
import { handleCreditEnvelope } from "../services/envelopeService/creditEnvelope"
import handleNameChange from "../utils/envelope/changeName"

export default function EnvelopeManagement() {
    const {
        envelopesData,
        createEnvelope,
        creditEnvelope,
        debitEnvelope,
        deleteEnvelope,
        updateEnvelopeName,
        loading,
        errorEnvelope,
    } = useEnvelopes()
    const [amounts, setAmounts] = useState<{ [key: string]: string }>({})
    const [isCreating, setIsCreating] = useState(false)
    const [newEnvelopeName, setNewEnvelopeName] = useState("")
    const [newEnvelopeTarget, setNewEnvelopeTarget] = useState("")
    const [pendingActions, setPendingActions] = useState<{ [key: string]: string }>({})
    const [deleteModalOpen, setDeleteModalOpen] = useState(false)
    const [envelopeToDelete, setEnvelopeToDelete] = useState<{ id: string; name: string } | null>(null)
    const [editingName, setEditingName] = useState<{ id: string; name: string } | null>(null)
    const [descriptionModalOpen, setDescriptionModalOpen] = useState(false)
    const [currentAction, setCurrentAction] = useState<{ type: "credit" | "debit"; id: string; amount: string } | null>(
        null,
    )
    const { t } = useTranslation()
    const { error, setError } = useError()
    const { validMessage, setValidMessage } = useValidMessage()
    const [isEmptyEnvelopes, setIsEmptyEnvelopes] = useState(true)
    useEffect(() => {
        console.log(amounts);
    }, [amounts]);

    const handleAmountChange = useCallback((id: string, value: string, isNewEnvelope = false) => {
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

        if (isNewEnvelope) {
            setNewEnvelopeTarget(value)
        } else {
            setAmounts((prev) => ({ ...prev, [id]: value }))
        }
    }, [])

    const formatAmount = useMemo(
        () =>
            (amount: string): string => {
                if (!amount) return ""
                let [integerPart, decimalPart] = amount.split(".")
                integerPart = integerPart || "0"
                decimalPart = decimalPart || "00"
                decimalPart = decimalPart.padEnd(2, "0").slice(0, 2)
                return `${integerPart}.${decimalPart}`
            },
        [],
    )



    const handleDescriptionSubmit = async (description: string) => {
        if (currentAction) {
            const { type, id, amount } = currentAction
            try {
                if (type === "credit") {
                    await creditEnvelope(id, amount, description, setError, setValidMessage)
                } else {
                    await debitEnvelope(id, amount, description, setError, setValidMessage)
                }
                handleAmountChange(id, "")
            } catch (err) {
                console.error("Error in handleDescriptionSubmit:", err)
            }
        }
        setDescriptionModalOpen(false)
        setCurrentAction(null)
    }

    const handleCreateEnvelope = async () => {
        console.log('test1')
        if (newEnvelopeName && newEnvelopeTarget && !isInvalidInput(newEnvelopeTarget)) {
            if (newEnvelopeName.length <= 25) {
                const formattedTarget = formatAmount(newEnvelopeTarget)
                await createEnvelope(newEnvelopeName, formattedTarget, setError, setValidMessage)
                setValidMessage('envelopes.validationSuccess.createNewEnvelope')
                setIsCreating(false)
                setNewEnvelopeName("")
                setNewEnvelopeTarget("")
            } else {
                setError('envelopes.validationError.createNewEnvelopeTooLong')
                setNewEnvelopeName("")
            }
        }
    }

    const handleDeleteEnvelope = async () => {
        if (envelopeToDelete) {
            const { id } = envelopeToDelete
            setDeleteModalOpen(false)
            await deleteEnvelope(id, setError, setValidMessage)
            setEnvelopeToDelete(null)
        }
    }

    const openDeleteModal = (id: string, name: string, e: any) => {
        e.preventDefault()
        setEnvelopeToDelete({ id, name })
        setDeleteModalOpen(true)
    }

    const handleStartEditingName = (id: string, currentName: string) => {
        setEditingName({ id, name: currentName })
    }


    const handleUpdateEnvelopeName = async (e: React.MouseEvent, name: string) => {
        e.preventDefault(); // Prevent the default link behavior

        if (editingName && editingName.name.trim() !== '') {
            const { id, name } = editingName;
            const currentEnvelope = envelopesData?.envelopes.find((env) => env.uuid === id);

            // Vérification : moins de 25 caractères
            if (name.length > 25) {
                console.error('Le nom ne doit pas dépasser 25 caractères.');
                setError('envelopes.validationError.nameTooLong');
                return;
            }
            const nameExists = envelopesData?.envelopes.some(
                (envelope) => envelope.name === name && envelope.uuid !== id
            );
            // Vérification : nom identique

            if (name === currentEnvelope.name) {
                setError('envelopes.validationError.sameName');
                return;
            }

            // Vérification : unicité du nom

            if (nameExists) {
                setError('envelopes.validationError.sameName');
                return;
            }

            setPendingActions((prev) => ({ ...prev, [id]: 'updating' }));

            try {
                await updateEnvelopeName(id, name, setError);
            } catch (error) {
                console.error('Failed to update envelope name:', error);
            } finally {
                setValidMessage('envelopes.validationSuccess.name');

                setPendingActions((prev) => {
                    const newPending = { ...prev };
                    delete newPending[id];
                    return newPending;
                });
                setEditingName(null);
            }
        }

    }
    useEffect(() => {
        if (envelopesData?.envelopes.length === 0) {
            setIsEmptyEnvelopes(false)
        } else setIsEmptyEnvelopes(true)
    }, [envelopesData])

    const validateAmount = useMemo(
        () =>
            (amount: string, currentAmount: string, targetedAmount: string, isCredit: boolean): boolean => {
                const amountFloat = Number.parseFloat(amount)
                const currentAmountFloat = Number.parseFloat(currentAmount)
                const targetedAmountFloat = Number.parseFloat(targetedAmount)

                if (isCredit) {
                    return currentAmountFloat + amountFloat <= targetedAmountFloat
                } else {
                    return currentAmountFloat - amountFloat >= 0
                }
            },
        [],
    )


    const renderedEnvelopes = useMemo(() => {
        return envelopesData?.envelopes.map((envelope) => (
            <Link key={envelope.uuid} href={`/envelopes/${envelope.uuid}`} className="block">
                <motion.div
                    layout
                    initial={{ opacity: 0, scale: 0.8 }}
                    animate={{ opacity: 1, scale: 1 }}
                    exit={{ opacity: 0, scale: 0.8 }}
                    transition={{ duration: 0.3 }}
                    className={`neomorphic p-3 md:p-4 ${envelope.pending ? "opacity-70" : ""} ${envelope.deleted ? "bg-red-100" : ""}`}
                    onClick={(e) => {
                        // Prevent navigation if clicking on interactive elements
                        if (
                            e.target instanceof HTMLButtonElement ||
                            e.target instanceof HTMLInputElement ||
                            (e.target instanceof HTMLElement && e.target.closest("button, input"))
                        ) {
                            e.preventDefault()
                        }
                    }}
                >
                    <EnvelopeCard>
                        {editingName && editingName.id === envelope.uuid ? (
                            <div className="flex items-center flex-grow">
                                <InputNameEnvelope
                                    value={editingName.name}
                                    onChange={(value) => handleNameChange(value, editingName, setEditingName)}
                                    autoFocus
                                    className="custom-input-class"
                                />
                                <ValidInputButton
                                    onClick={(e) => handleUpdateEnvelopeName(e, editingName.name)}
                                    icon={<Check className="h-4 w-4 md:h-5 md:w-5" />}
                                    className=" text-green-500 mr-1 "
                                    disabled={envelope.pending || !!pendingActions[envelope.uuid]}
                                    text=""
                                />

                                {/* Utilisation pour le bouton de l'annulation */}
                                <ValidInputButton
                                    onClick={(e) => {
                                        setEditingName((prev) => ({ ...prev, name: envelope.name }));
                                        cancelEditing({ e, setEditing: setEditingName });
                                    }}
                                    icon={<X className="h-4 w-4 md:h-5 md:w-5" />}
                                    className="text-red-500"
                                    disabled={envelope.pending || !!pendingActions[envelope.uuid]}
                                    text=""
                                />
                            </div>
                        ) : (
                            <>
                                <InputNameEnvelope
                                    value={envelope.name}
                                    onChange={() => handleStartEditingName(envelope.uuid, envelope.name)}
                                    onFocus={() => handleStartEditingName(envelope.uuid, envelope.name)}
                                    className="custom-input-class cursor-pointer"
                                />
                                {/* <button
                                            onClick={(e) => {
                                                e.preventDefault()
                                                handleStartEditingName(envelope.uuid, envelope.name)
                                            }}
                                            className="p-1 neomorphic-button text-primary"
                                            disabled={envelope.pending || !!pendingActions[envelope.uuid]}
                                        >
                                            <Edit2 className="h-4 w-4 md:h-5 md:w-5" />
                                        </button> */}
                            </>
                        )}
                    </EnvelopeCard>
                    <div className="flex items-center">
                        {envelope.pending && <Loader2 className="ml-2 h-4 w-4 animate-spin" />}
                    </div>
                    <div className="flex justify-between items-center mb-4">
                        <div>
                            <p className="text-lg md:text-xl font-semibold">
                                ${Number.parseFloat(envelope.currentAmount).toFixed(2)}
                            </p>
                            <p className="text-xs md:text-sm text-muted-foreground">
                                {t("envelopes.of")} ${Number.parseFloat(envelope.targetedAmount).toFixed(2)}
                            </p>
                        </div>
                        <div className="w-16 h-16 md:w-20 md:h-20 neomorphic-circle flex items-center justify-center">
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={[
                                            { name: "Used", value: Number.parseFloat(envelope.currentAmount) },
                                            {
                                                name: "Remaining",
                                                value: Math.max(0, Number.parseFloat(envelope.targetedAmount) - Number.parseFloat(envelope.currentAmount)),
                                            },
                                        ]}
                                        cx="50%"
                                        cy="50%"
                                        innerRadius={20}
                                        outerRadius={30}
                                        fill="#8884d8"
                                        dataKey="value"
                                        strokeWidth={0}
                                    >
                                        <Cell key="cell-0" fill="#4CAF50" />
                                        <Cell key="cell-1" fill="#E0E0E0" />

                                        {/* ✅ Ajout du pourcentage au centre */}
                                        <Label
                                            value={`${((Number.parseFloat(envelope.currentAmount) / Number.parseFloat(envelope.targetedAmount)) * 100).toFixed(0)}%`}
                                            position="center"
                                            fontSize={12}
                                            fill="#333"
                                            fontWeight="bold"
                                        />
                                    </Pie>
                                </PieChart>
                            </ResponsiveContainer>

                        </div>
                    </div>
                    <div className="space-y-4">
                        <div className="flex flex-col space-y-2">
                            <div className="flex items-center space-x-2">
                                <InputNumber
                                    value={amounts[envelope.uuid] || ""}
                                    onChange={(value) => handleAmountChange(envelope.uuid, value)}
                                    placeholder={t("envelopes.amount")}
                                    disabled={envelope.pending || !!pendingActions[envelope.uuid]}
                                />
                                <ActionButton
                                    onClick={() => handleCreditEnvelope(
                                        envelope.uuid,
                                        envelope.currentAmount,
                                        envelope.targetedAmount,
                                        amounts,
                                        setCurrentAction,
                                        setDescriptionModalOpen,
                                        setError,
                                        t
                                    )}
                                    label={t("envelopes.credit")}
                                    disabled={
                                        envelope.pending ||
                                        !!pendingActions[envelope.uuid] ||
                                        isInvalidInput(amounts[envelope.uuid] || "") ||
                                        !amounts[envelope.uuid]
                                    }
                                    className="text-green-500"
                                />

                                <ActionButton
                                    onClick={() => handleDebitEnvelope(
                                        envelope.uuid,
                                        envelope.currentAmount,
                                        amounts,
                                        setCurrentAction,
                                        setDescriptionModalOpen,
                                        setError,
                                        t
                                    )}
                                    label={t("envelopes.debit")}
                                    disabled={
                                        envelope.pending ||
                                        !!pendingActions[envelope.uuid] ||
                                        isInvalidInput(amounts[envelope.uuid] || "") ||
                                        !amounts[envelope.uuid]
                                    }
                                    className="text-red-500"
                                />

                            </div>
                        </div>
                        <div className="flex justify-end mt-4" >
                            <DeletButton
                                onClick={(e) => openDeleteModal(envelope.uuid, envelope.name, e)}
                                icon={<Trash2 className="h-4 w-4 md:h-5 md:w-5" />
                                }
                                className={''}
                                disabled={envelope.pending || !!pendingActions[envelope.uuid]}
                            />
                        </div>
                    </div>
                    {envelope.deleted && <p className="text-red-500 mt-2">Deleting...</p>}
                </motion.div>
            </Link >
        ))
    }, [
        envelopesData,
        amounts,
        editingName,
        pendingActions,
        handleAmountChange,
        handleDebitEnvelope,
        handleUpdateEnvelopeName,
        openDeleteModal,
    ])

    return (
        <div className="space-y-8">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl md:text-3xl font-bold">{t("envelopes.title")}</h1>
                <button
                    onClick={() => setIsCreating(true)}
                    className="p-3 neomorphic-button text-primary hover:text-primary-dark transition-colors rounded-full"
                    aria-label={t("envelopes.createNew")}
                >
                    <PlusCircle className="h-6 w-6" />
                </button>
            </div>

            {isEmptyEnvelopes === false ? (
                <div className="text-center py-12">
                    <p className="text-lg md:text-xl mb-6">{t("envelopes.empty")}</p>
                </div>
            ) : (
                <AnimatePresence initial={false}>
                    <motion.div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">{renderedEnvelopes}</motion.div>
                </AnimatePresence>
            )}
            {isCreating && (
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
                        <h2 className="text-xl md:text-2xl font-bold mb-4">{t("envelopes.createNewEnvelope")}</h2>
                        <InputText
                            value={newEnvelopeName}
                            onChange={setNewEnvelopeName}
                            placeholder={t("envelopes.envelopeName")}
                            className="custom-class"
                        />
                        <div className="mb-4">
                            <InputNumber
                                value={newEnvelopeTarget}
                                onChange={(value) => handleAmountChange("new", value, true)}
                                placeholder={t("envelopes.targetedAmount")}
                                className="w-full p-2 md:p-3 neomorphic-input"

                            />

                        </div>
                        <div className="flex justify-between">
                            <ActionButton
                                onClick={handleCreateEnvelope}
                                label={t("envelopes.create")}
                                disabled={!newEnvelopeName || isInvalidInput(newEnvelopeTarget) || !newEnvelopeTarget}
                                className="text-green-500"
                            />
                            <ActionButton
                                onClick={() => setIsCreating(false)}
                                label={t("envelopes.cancel")}
                                className="text-red-500"
                            />
                        </div>
                    </motion.div>
                </motion.div>
            )}
            <DeleteConfirmationModal
                isOpen={deleteModalOpen}
                onClose={() => setDeleteModalOpen(false)}
                onConfirm={handleDeleteEnvelope}
                envelopeName={envelopeToDelete?.name || ""}
            />
            <DescriptionModal
                isOpen={descriptionModalOpen}
                onClose={() => setDescriptionModalOpen(false)}
                onSubmit={handleDescriptionSubmit}
                actionType={currentAction?.type || "credit"}
            />
        </div>
    )
}
