"use client"

import { useState, useEffect } from "react"
import { useParams, useRouter } from "next/navigation"
import { useTranslation } from "../../hooks/useTranslation"
import { PieChart, Pie, Cell, ResponsiveContainer, Label } from "recharts"
import type { EnvelopeDetails as EnvelopeDetailsType } from "../../domain/envelope/envelopeTypes"
import { api } from "../../infrastructure/api"
import { ArrowLeft, Check, X, Loader2, Edit2, DollarSign, Calendar, Trash2 } from "lucide-react"
import Link from "next/link"
import { useError } from "../../contexts/ErrorContext"
import { useValidMessage } from "../../contexts/ValidContext"
import { motion } from "framer-motion"
import InputNameEnvelope from "../../components/inputs/inputNameEnvelope"
import ValidInputButton from "../../components/buttons/validInputButton"
import AnimatedCard from "../../components/card/AnimatedCard"
import handleNameChange from "../../utils/envelope/changeName"
import handleStartEditingName from "../../utils/form/startEditing"
import handleUpdateEnvelopeName from "../../function/EnvelopeFunction/handleUpdateEnvelopeName"
import { useEnvelopes } from "../../domain/envelope/envelopeHooks"
import { formatCurrency } from "../../utils/envelope/currencyUtils"
import InputNumber from "../../components/inputs/inputNumber"
import ActionButton from "../../components/buttons/actionButton"
import { handleCreditEnvelope } from "../../services/envelopeService/creditEnvelope"
import isInvalidInput from "../../utils/validation/IsInvalidValidInput"
import DeletButton from "../../components/buttons/deletButton"
import { handleDebitEnvelope } from "../../services/envelopeService/debitEnvelope"
import handleAmountChange from "../../utils/envelope/handleAmountChange"
import handleDeleteEntity from "../../utils/envelope/deleteUtils"
import { DeleteConfirmationModal } from "../../components/DeleteConfirmationModal"
import { DescriptionModal } from "../../components/DescriptionModal"
import handleTargetedAmountChange from "../../utils/envelope/handletargetedAmount"

const RETRY_INTERVAL = 2000 // 2 seconds
const MAX_RETRIES = 10

export default function EnvelopeDetailsPage() {
    const { t, language } = useTranslation()
    const {
        envelopesData,
        createEnvelope,
        creditEnvelope,
        debitEnvelope,
        deleteEnvelope,
        nameEnvelope,
        updateEnvelopeName,
    } = useEnvelopes()
    const router = useRouter()
    const params = useParams()
    const [details, setDetails] = useState<EnvelopeDetailsType | null>(null)
    const [pendingActions, setPendingActions] = useState<{ [key: string]: boolean }>({})
    const [editingField, setEditingField] = useState<{ id: string; value: string } | null>(null)
    const [newValues, setNewValues] = useState(null)
    const { setError: setError } = useError()
    const { setValidMessage } = useValidMessage()
    const [editingName, setEditingName] = useState<{ id: string; name: string } | null>(null)
    const [amounts, setAmounts] = useState<{ [key: string]: string }>({})
    const [currentAction, setCurrentAction] = useState<{ type: "credit" | "debit"; id: string; amount: string } | null>(
        null,
    )
    const [descriptionModalOpen, setDescriptionModalOpen] = useState(false)
    const [newEnvelopeTarget, setNewEnvelopeTarget] = useState("")
    useEffect(() => {
        const fetchEnvelopeDetails = async () => {
            if (!params.uuid) return
            try {
                const response = await api.envelopeQueries.getEnvelopeDetails(params.uuid as string)
                setDetails(response)
            } catch (err) {
                setDetails(null)
            }
        }
        fetchEnvelopeDetails()
    }, [params.uuid, envelopesData])
    const [envelopeToDelete, setEnvelopeToDelete] = useState<{ id: string; name: string } | null>(null)
    const [deleteModalOpen, setDeleteModalOpen] = useState(false)

    const openDeleteModal = (id: string, name: string, e: any) => {
        e.preventDefault()
        setEnvelopeToDelete({ id, name })
        setDeleteModalOpen(true)
    }
    const formatDate = (dateString: string) => {
        const date = new Date(dateString)
        if (language === "fr") {
            return date.toLocaleDateString("fr-FR", { day: "2-digit", month: "2-digit", year: "numeric" })
        } else {
            return date.toLocaleDateString("en-US", { month: "2-digit", day: "2-digit", year: "numeric" })
        }
    }



    const pollForChanges = async (
        field: string,
        envelopeId: string,
        action: string,
        expectedChange: (envelope: EnvelopeDetailsType | undefined) => boolean,
    ) => {
        let retries = 0
        while (retries < MAX_RETRIES) {
            await new Promise((resolve) => setTimeout(resolve, RETRY_INTERVAL))
            try {
                const updatedEnvelope = await api.envelopeQueries.getEnvelopeDetails(envelopeId)
                if (expectedChange(updatedEnvelope)) {
                    setDetails(updatedEnvelope)
                    setValidMessage(`Envelope ${action} confirmed`)
                    setPendingActions((prev) => ({ ...prev, [field]: false }))
                    return
                }
            } catch (err) {
                // Continue polling even if there's an error
            }
            retries++
        }
        setGlobalError(`Failed to confirm ${action}. Please refresh.`)
        setPendingActions((prev) => ({ ...prev, [field]: false }))
    }

    // const handleUpdate = async (field: "name" | "targetBudget") => {
    //     if (!details) return
    //     setPendingActions((prev) => ({ ...prev, [field]: true }))
    //     try {
    //         if (field === "name") {
    //             if (newValues.name.length > 25) {
    //                 setError('envelopes.validationError.nameTooLong');
    //             }
    //             if (newValues.name === details.envelope.name) {
    //                 setError('envelopes.validationError.sameName');
    //             }
    //             else {
    //                 await api.envelopeCommands.nameEnvelope(details.envelope.uuid, newValues.name)
    //                 pollForChanges("name", details.envelope.uuid, "name update", (env) => env?.envelope.name === newValues.name)
    //                 setValidMessage('envelopes.validationSuccess.name');
    //             }
    //         } else {
    //             if (newValues.targetBudget === details.envelope.targetedAmount) {
    //                 setError('envelopes.validationError.sameTargetedAmount');
    //             }
    //             if (newValues.targetBudget === 0) {
    //                 setError('envelopes.validationError.targetBudgetLessThanCurrentAmount');
    //             }
    //             if (newValues.targetBudget < details.envelope.currentAmount) {
    //                 setError('envelopes.validationError.targetBudgetLessThanCurrentAmount');
    //             }
    //             else {
    //                 await api.envelopeCommands.updateTargetBudget(details.envelope.uuid, newValues.targetBudget, details.envelope.currentAmount)
    //                 pollForChanges(
    //                     "targetBudget",
    //                     details.envelope.uuid,
    //                     "target budget update",
    //                     (env) => env?.envelope.targetedAmount === newValues.targetBudget,
    //                 )
    //                 setValidMessage('envelopes.validationSuccess.targetBudget');
    //             }

    //         }
    //     } catch (err) {
    //         // setGlobalError(`Failed to update envelope ${field}`)
    //         // setPendingActions((prev) => ({ ...prev, [field]: false }))
    //     }
    //     setEditingField(null)
    // }

    if (!details) {
        return (
            <div className="flex justify-center items-center h-screen">
                <div className="animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-primary"></div>
            </div>
        )
    }
    const handleTargetedAmount = async () => {
        try {
            await api.envelopeCommands.updateTargetBudget(details.envelope.uuid, newValues, details.envelope.currentAmount)
        } catch (err) {
            console.error("Error in handleTargetedAmount:", err)
        }
        setNewValues(null)
    }
    const handleDeleteEnvelope = async () => {
        await handleDeleteEntity({
            entityToDelete: envelopeToDelete,
            deleteFunction: deleteEnvelope,
            setDeleteModalOpen,
            setError,
            setEntityToDelete: setEnvelopeToDelete
        });
        router.push("/envelopes")
    }
    const handleDescriptionSubmit = async (description: string) => {
        if (currentAction) {
            const { type, id, amount } = currentAction
            try {
                if (type === "credit") {
                    await creditEnvelope(id, amount, description, setError)
                } else {
                    await debitEnvelope(id, amount, description, setError)
                }
                handleAmountChange(id, "", false, setNewEnvelopeTarget, setAmounts)
            } catch (err) {
                console.error("Error in handleDescriptionSubmit:", err)
            }
        }
        setDescriptionModalOpen(false)
        setCurrentAction(null)
    }
    const progress =
        (Number.parseFloat(details.envelope.currentAmount) / Number.parseFloat(details.envelope.targetedAmount)) * 100
    return (
        <div className="container mx-auto px-4 py-8">
            <AnimatedCard
                pending={details.envelope.pending}
                deleted={details.envelope.deleted}
                className="my-custom-class"
                preventClickOnSelectors="button, input"
            >
                <div className='flex items-center justify-between'>
                    <Link
                        href="/envelopes"
                        className="mb-6 flex items-center text-primary hover:underline transition-colors duration-200"
                    >
                        <ArrowLeft className="mr-2" /> {t("common.back")}
                    </Link>
                    <div className="flex justify-end mb-6" >
                        <DeletButton
                            onClick={(e) => openDeleteModal(details.envelope.uuid, details.envelope.name, e)}
                            icon={<Trash2 className=" h-4 w-4 md:h-5 md:w-5" />
                            }
                            className={''}
                            disabled={details.envelope.pending || !!pendingActions[details.envelope.uuid]}
                        />
                    </div>
                </div>

                <div className="mb-6 flex items-center">
                    <h1 className="text-3xl font-bold mr-2">
                        {editingName && editingName.id ? (<div className="flex items-center flex-grow">
                            <InputNameEnvelope
                                value={editingName.name}
                                onChange={(value) => handleNameChange(value, editingName, setEditingName)}
                                autoFocus
                                className="custom-input-class"
                            />
                            <ValidInputButton
                                onClick={(e) => handleUpdateEnvelopeName({
                                    e,
                                    editingName,
                                    name: editingName.name,
                                    envelopesData,
                                    updateEnvelopeName,
                                    setError,
                                    setPendingActions,
                                    setEditingName
                                })}
                                icon={<Check className="h-4 w-4 md:h-5 md:w-5" />}
                                className=" text-green-500 mr-1 "
                                disabled={details.envelope.pending || !!pendingActions[details.envelope.uuid]}
                                text=""
                            />
                            <ValidInputButton
                                onClick={() => {
                                    setEditingName(null); //
                                    setEditingField(null);
                                }}
                                icon={<X className="h-4 w-4 md:h-5 md:w-5" />
                                }
                                className="p-1 neomorphic-button text-red-500"
                                text=""
                            />
                        </div>
                        ) : (
                            <>
                                <InputNameEnvelope
                                    value={details.envelope.name}
                                    onChange={() => handleStartEditingName(details.envelope.uuid, details.envelope.name, setEditingName)}
                                    onFocus={() => handleStartEditingName(details.envelope.uuid, details.envelope.name, setEditingName)}
                                    className="custom-input-class cursor-pointer"
                                />
                            </>
                        )}
                    </h1>
                </div>
                <div className="flex items-center">
                    {details.envelope.pending && <Loader2 className="ml-2 h-4 w-4 animate-spin" />}
                </div>
                <div className="flex justify-between items-center mb-4">
                    <div className='flex items-center'>
                        <p className="text-lg md:text-xl font-semibold">
                            {formatCurrency(details.envelope.currentAmount, details.envelope.currency)}
                        </p>
                        <motion.div
                            className=""

                        >
                            <div className="flex items-center">
                                <p>/</p>
                                {newValues !== null ? (<div className="flex items-center flex-grow ">
                                    <InputNameEnvelope
                                        value={newValues || ""}
                                        onChange={(value) => handleTargetedAmountChange(value, setNewValues)}
                                        className="custom-input-class"
                                    />
                                    <ValidInputButton
                                        onClick={(e) => handleTargetedAmount()}
                                        icon={<Check className="h-4 w-4 md:h-5 md:w-5" />}
                                        className=" text-green-500 mr-1"
                                        text=""
                                    />
                                    <ValidInputButton
                                        onClick={(e) => {
                                            setNewValues(null);
                                        }}
                                        icon={<X className="h-4 w-4 md:h-5 md:w-5" />}
                                        className="text-red-500"
                                        text=""
                                    />
                                </div>
                                ) : (
                                    <>

                                        <InputNameEnvelope
                                            value={details.envelope.targetedAmount}
                                            onChange={() => setNewValues(details.envelope.targetedAmount)}
                                            onFocus={() => setNewValues(details.envelope.targetedAmount)}
                                            className="custom-input-class cursor-pointer "
                                        />
                                    </>
                                )}


                            </div>
                        </motion.div>

                    </div>
                    <div className="w-16 h-16 md:w-20 md:h-20 neomorphic-circle flex items-center justify-center">
                        <ResponsiveContainer width="100%" height="100%">
                            <PieChart>
                                <Pie
                                    data={[
                                        { name: "Used", value: Number.parseFloat(details.envelope.currentAmount) },
                                        {
                                            name: "Remaining",
                                            value: Math.max(0, Number.parseFloat(details.envelope.targetedAmount) - Number.parseFloat(details.envelope.currentAmount)),
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
                                        value={`${((Number.parseFloat(details.envelope.currentAmount) / Number.parseFloat(details.envelope.targetedAmount)) * 100).toFixed(0)}%`}
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
                <div className="space-y-4 mb-4">
                    <div className="flex flex-col space-y-2">

                        <div className="flex items-center space-x-2 ">
                            <InputNumber
                                value={amounts[details.envelope.uuid] || ""}
                                onChange={(value) => handleAmountChange(details.envelope.uuid, value, false, setNewEnvelopeTarget, setAmounts)}
                                placeholder={t("envelopes.amount")}
                                disabled={details.envelope.pending || !!pendingActions[details.envelope.uuid]}
                            />
                            <ActionButton
                                onClick={() => handleCreditEnvelope(
                                    details.envelope.uuid,
                                    details.envelope.currentAmount,
                                    details.envelope.targetedAmount,
                                    amounts,
                                    setCurrentAction,
                                    setDescriptionModalOpen,
                                    setError,
                                    t
                                )}
                                label={t("envelopes.credit")}
                                disabled={
                                    details.envelope.pending ||
                                    !!pendingActions[details.envelope.uuid] ||
                                    isInvalidInput(amounts[details.envelope.uuid] || "") ||
                                    !amounts[details.envelope.uuid]
                                }
                                className="text-green-500"
                            />

                            <ActionButton
                                onClick={() => handleDebitEnvelope(
                                    details.envelope.uuid,
                                    details.envelope.currentAmount,
                                    amounts,
                                    setCurrentAction,
                                    setDescriptionModalOpen,
                                    setError,
                                    t
                                )}
                                label={t("envelopes.debit")}
                                disabled={
                                    details.envelope.pending ||
                                    !!pendingActions[details.envelope.uuid] ||
                                    isInvalidInput(amounts[details.envelope.uuid] || "") ||
                                    !amounts[details.envelope.uuid]
                                }
                                className="text-red-500"
                            />

                        </div>
                    </div>

                </div>
                <h2 className="text-2xl font-bold mb-4 flex items-center">
                    <Calendar className="mr-2 text-primary" />
                    {t("envelopes.transactionHistory")}
                </h2>
                <div className="overflow-x-auto">
                    <table className="w-full border-collapse neomorphic">
                        <tbody>
                            {details.ledger.map((transaction, index) => (
                                <motion.tr
                                    key={index}
                                    initial={{ opacity: 0, y: 20 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ duration: 0.3, delay: index * 0.1 }}
                                    className="border-b hover:bg-gray-50 transition-colors duration-150"
                                >
                                    <td className="p-3">
                                        <div>
                                            <p>
                                                <strong>{formatDate(transaction.created_at)}</strong>
                                            </p>
                                            <p>
                                                {transaction.description || "-"}
                                            </p>
                                        </div>
                                    </td>
                                    <td className="p-3"></td>
                                    <td className={`p-3 ${transaction.entry_type === "credit" ? "text-green-600" : "text-red-600"}`}>{transaction.monetary_amount}</td>
                                </motion.tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </AnimatedCard >
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
        </div >
    )
}
