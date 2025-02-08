"use client"

import { useState, useEffect } from "react"
import { useParams, useRouter } from "next/navigation"
import { useTranslation } from "../../hooks/useTranslation"
import type { EnvelopeDetails as EnvelopeDetailsType } from "../../domain/envelope/envelopeTypes"
import { api } from "../../infrastructure/api"
import { ArrowLeft, Check, X, Loader2, Edit2, DollarSign, Calendar } from "lucide-react"
import Link from "next/link"
import { useError } from "../../contexts/ErrorContext"
import { useValidMessage } from "../../contexts/ValidContext"
import { motion } from "framer-motion"
import InputNameEnvelope from "../../components/inputs/inputNameEnvelope"
import ValidInputButton from "../../components/buttons/validInputButton"

const RETRY_INTERVAL = 2000 // 2 seconds
const MAX_RETRIES = 10

export default function EnvelopeDetailsPage() {
    const { t, language } = useTranslation()
    const router = useRouter()
    const params = useParams()
    const [details, setDetails] = useState<EnvelopeDetailsType | null>(null)
    const [pendingActions, setPendingActions] = useState<{ [key: string]: boolean }>({})
    const [editingField, setEditingField] = useState<string | null>(null)
    const [newValues, setNewValues] = useState({ name: "", targetBudget: "" })
    const { setError: setError } = useError()
    const { setValidMessage } = useValidMessage()



    useEffect(() => {
        const fetchEnvelopeDetails = async () => {
            if (!params.uuid) return

            try {
                const response = await api.envelopeQueries.getEnvelopeDetails(params.uuid as string)
                setDetails(response)
                setNewValues({
                    name: response.envelope.name,
                    targetBudget: response.envelope.targetedAmount,
                })
            } catch (err) {
                setGlobalError("Failed to fetch envelope details")
            }
        }

        fetchEnvelopeDetails()
    }, [params.uuid])

    const formatDate = (dateString: string) => {
        const date = new Date(dateString)
        if (language === "fr") {
            return date.toLocaleDateString("fr-FR", { day: "2-digit", month: "2-digit", year: "numeric" })
        } else {
            return date.toLocaleDateString("en-US", { month: "2-digit", day: "2-digit", year: "numeric" })
        }
    }

    const handleAmountChange = (value: string) => {
        value = value.replace(/[^\d.]/g, "")
        if (value.startsWith(".")) {
            value = "0" + value
        }
        const parts = value.split(".")
        if (parts.length > 2) {
            parts.pop()
            value = parts.join(".")
        }
        if (value.includes(".")) {
            const [integerPart, decimalPart] = value.split(".")
            value = `${integerPart.slice(0, 10)}.${decimalPart.slice(0, 2)}`
        } else {
            value = value.slice(0, 10)
        }
        if (value.length > 1 && value.startsWith("0") && !value.startsWith("0.")) {
            value = value.replace(/^0+/, "")
        }
        setNewValues((prev) => ({ ...prev, targetBudget: value }))
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

    const handleUpdate = async (field: "name" | "targetBudget") => {
        if (!details) return
        setPendingActions((prev) => ({ ...prev, [field]: true }))
        try {
            if (field === "name") {
                if (newValues.name.length > 25) {
                    setError('envelopes.validationError.nameTooLong');
                }
                if (newValues.name === details.envelope.name) {
                    setError('envelopes.validationError.sameName');
                }
                else {
                    await api.envelopeCommands.nameEnvelope(details.envelope.uuid, newValues.name)
                    pollForChanges("name", details.envelope.uuid, "name update", (env) => env?.envelope.name === newValues.name)
                    setValidMessage('envelopes.validationSuccess.name');
                }
            } else {
                if (newValues.targetBudget === details.envelope.targetedAmount) {
                    setError('envelopes.validationError.sameTargetedAmount');
                }
                console.log(newValues.targetBudget)
                if (newValues.targetBudget === 0) {
                    setError('envelopes.validationError.targetBudgetLessThanCurrentAmount');
                }
                if (newValues.targetBudget < details.envelope.currentAmount) {
                    setError('envelopes.validationError.targetBudgetLessThanCurrentAmount');
                }
                else {
                    await api.envelopeCommands.updateTargetBudget(details.envelope.uuid, newValues.targetBudget, details.envelope.currentAmount)
                    pollForChanges(
                        "targetBudget",
                        details.envelope.uuid,
                        "target budget update",
                        (env) => env?.envelope.targetedAmount === newValues.targetBudget,
                    )
                    setValidMessage('envelopes.validationSuccess.targetBudget');
                }

            }
        } catch (err) {
            // setGlobalError(`Failed to update envelope ${field}`)
            // setPendingActions((prev) => ({ ...prev, [field]: false }))
        }
        setEditingField(null)
    }

    if (!details) {
        return (
            <div className="flex justify-center items-center h-screen">
                <div className="animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-primary"></div>
            </div>
        )
    }

    const progress =
        (Number.parseFloat(details.envelope.currentAmount) / Number.parseFloat(details.envelope.targetedAmount)) * 100
    return (
        <div className="container mx-auto px-4 py-8">
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5 }}
                className="neomorphic rounded-lg p-6 bg-gradient-to-br from-gray-50 to-white"
            >
                <Link
                    href="/envelopes"
                    className="mb-6 flex items-center text-primary hover:underline transition-colors duration-200"
                >
                    <ArrowLeft className="mr-2" /> {t("common.back")}
                </Link>
                <div className="mb-6 flex items-center">
                    <h1 className="text-3xl font-bold mr-2">

                        {editingField === "name" ? (
                            <div className="flex items-center flex-grow">
                                <InputNameEnvelope
                                    value={newValues?.name || ""} // ✅ Gère le cas undefined
                                    onChange={(value) =>
                                        setNewValues((prev) => ({ ...(prev || {}), name: value }))
                                    }
                                    autoFocus
                                    className="custom-input-class"
                                />
                                <ValidInputButton
                                    onClick={() => handleUpdate("name")}
                                    icon={<Check className="h-4 w-4 md:h-5 md:w-5" />}
                                    className="p-1 neomorphic-button text-green-500 mr-1"
                                    text=""
                                />
                                <ValidInputButton
                                    onClick={() => {
                                        setNewValues((prev) => ({ ...prev, name: details.envelope.name })); //
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
                                    onChange={() => setEditingField("name")}// ✅ Active le mode édition
                                    onFocus={() => setEditingField("name")} // ✅ Active aussi au clic
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
                    </h1>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <motion.div
                        className="p-6 rounded-lg neomorphic bg-gradient-to-br from-blue-50 to-white"
                        whileHover={{ scale: 1.02 }}
                        transition={{ type: "spring", stiffness: 300 }}
                    >
                        <h2 className="text-xl font-semibold mb-2 flex items-center">
                            <DollarSign className="mr-2 text-primary" />
                            {t("envelopes.currentAmount")}
                        </h2>
                        <p className="text-3xl font-bold text-primary">{details.envelope.currentAmount}</p>
                    </motion.div>
                    <motion.div
                        className="p-6 rounded-lg neomorphic bg-gradient-to-br from-green-50 to-white"
                        whileHover={{ scale: 1.02 }}
                        transition={{ type: "spring", stiffness: 300 }}
                    >
                        <h2 className="text-xl font-semibold mb-2">{t("envelopes.targetedAmount")}</h2>
                        <div className="flex items-center">
                            {editingField === "targetBudget" ? (
                                <div
                                    className="flex items-center w-full"
                                >
                                    <InputNameEnvelope
                                        value={newValues.targetBudget}
                                        onChange={(value) =>
                                            handleAmountChange(value)
                                        }
                                        autoFocus
                                        className="flex-grow mr-2 p-2 border-b-2 border-primary bg-transparent focus:outline-none text-xl neomorphic-input transition-all duration-200"
                                    />
                                    <ValidInputButton
                                        onClick={(e) => handleUpdate("targetBudget")}
                                        icon={<Check className="h-4 w-4 md:h-5 md:w-5" />}
                                        className=" text-green-500 mr-1"
                                        text=""
                                    />
                                    <ValidInputButton
                                        onClick={(e) => {
                                            setEditingField(null)
                                            setNewValues((prev) => ({ ...prev, targetBudget: details.envelope.targetedAmount })); //
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
                                        onChange={() => setEditingField("targetBudget")}
                                        aria-label={t("envelopes.updateTargetBudget")}
                                        className="flex-grow mr-2 p-2 border-b-2 border-primary bg-transparent focus:outline-none text-xl neomorphic-input transition-all duration-200"
                                        onFocus={() => setEditingField("targetBudget")} // ✅ Active aussi au clic

                                    />
                                </>
                            )}
                        </div>
                    </motion.div>
                </div>
                <div className="mb-8">
                    <h3 className="text-lg font-semibold mb-2">Progress</h3>
                    <div className="w-full h-4 bg-gray-200 rounded-full overflow-hidden neomorphic">
                        <motion.div
                            className="h-full bg-primary"
                            initial={{ width: 0 }}
                            animate={{ width: `${progress}%` }}
                            transition={{ duration: 1, ease: "easeOut" }}
                        />
                    </div>
                    <p className="text-right mt-1 text-sm text-gray-600">{progress.toFixed(2)}%</p>
                </div>
                <h2 className="text-2xl font-bold mb-4 flex items-center">
                    <Calendar className="mr-2 text-primary" />
                    {t("envelopes.transactionHistory")}
                </h2>
                <div className="overflow-x-auto">
                    <table className="w-full border-collapse neomorphic">
                        <thead>
                            <tr className="bg-gradient-to-r from-gray-50 to-white">
                                <th className="p-3 text-left border-b">{t("envelopes.date")}</th>
                                <th className="p-3 text-left border-b">{t("envelopes.description")}</th>
                                <th className="p-3 text-left border-b">{t("envelopes.amount")}</th>
                                <th className="p-3 text-left border-b">{t("envelopes.type")}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {details.ledger.map((transaction, index) => (
                                <motion.tr
                                    key={index}
                                    initial={{ opacity: 0, y: 20 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ duration: 0.3, delay: index * 0.1 }}
                                    className="border-b hover:bg-gray-50 transition-colors duration-150"
                                >
                                    <td className="p-3">{formatDate(transaction.created_at)}</td>
                                    <td className="p-3">{transaction.description || "-"}</td>
                                    <td className="p-3">{transaction.monetary_amount}</td>
                                    <td
                                        className={`p-3 ${transaction.entry_type === "credit" ? "text-green-600" : "text-red-600"}`}
                                    >
                                        {t(`envelopes.${transaction.entry_type}`)}
                                    </td>
                                </motion.tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </motion.div >
        </div >
    )
}
