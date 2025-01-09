'use client'

import { useState, useEffect } from 'react'
import { useParams, useRouter } from 'next/navigation'
import { useTranslation } from '../../hooks/useTranslation'
import { EnvelopeDetails as EnvelopeDetailsType } from '../../domain/envelope/envelopeTypes'
import { api } from '../../infrastructure/api'
import { ArrowLeft, Check, X, Loader2, Edit2, DollarSign, Calendar } from 'lucide-react'
import Link from 'next/link'
import { useError } from '../../contexts/ErrorContext'
import { useValidMessage } from '../../contexts/ValidContext'
import { motion } from 'framer-motion'

const RETRY_INTERVAL = 2000 // 2 seconds
const MAX_RETRIES = 10

export default function EnvelopeDetailsPage() {
    const { t, language } = useTranslation()
    const router = useRouter()
    const params = useParams()
    const [details, setDetails] = useState<EnvelopeDetailsType | null>(null)
    const [pendingActions, setPendingActions] = useState<{ [key: string]: boolean }>({})
    const [editingField, setEditingField] = useState<string | null>(null)
    const [newValues, setNewValues] = useState({ name: '', targetBudget: '' })
    const { setError: setGlobalError } = useError()
    const { setValidMessage } = useValidMessage()

    useEffect(() => {
        const fetchEnvelopeDetails = async () => {
            if (!params.uuid) return

            try {
                const response = await api.envelopeQueries.getEnvelopeDetails(params.uuid as string)
                setDetails(response)
                setNewValues({
                    name: response.envelope.name,
                    targetBudget: response.envelope.targetedAmount
                })
            } catch (err) {
                setGlobalError('Failed to fetch envelope details')
            }
        }

        fetchEnvelopeDetails()
    }, [params.uuid])

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        if (language === 'fr') {
            return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
        } else {
            return date.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' });
        }
    };

    const handleAmountChange = (value: string) => {
        value = value.replace(/[^\d.]/g, '');
        if (value.startsWith('.')) {
            value = '0' + value;
        }
        const parts = value.split('.');
        if (parts.length > 2) {
            parts.pop();
            value = parts.join('.');
        }
        if (value.includes('.')) {
            const [integerPart, decimalPart] = value.split('.');
            value = `${integerPart.slice(0, 10)}.${decimalPart.slice(0, 2)}`;
        } else {
            value = value.slice(0, 10);
        }
        if (value.length > 1 && value.startsWith('0') && !value.startsWith('0.')) {
            value = value.replace(/^0+/, '');
        }
        setNewValues(prev => ({ ...prev, targetBudget: value }));
    };

    const pollForChanges = async (field: string, envelopeId: string, action: string, expectedChange: (envelope: EnvelopeDetailsType | undefined) => boolean) => {
        let retries = 0
        while (retries < MAX_RETRIES) {
            await new Promise(resolve => setTimeout(resolve, RETRY_INTERVAL))
            try {
                const updatedEnvelope = await api.envelopeQueries.getEnvelopeDetails(envelopeId)
                if (expectedChange(updatedEnvelope)) {
                    setDetails(updatedEnvelope)
                    setValidMessage(`Envelope ${action} confirmed`)
                    setPendingActions(prev => ({ ...prev, [field]: false }))
                    return
                }
            } catch (err) {
                // Continue polling even if there's an error
            }
            retries++
        }
        setGlobalError(`Failed to confirm ${action}. Please refresh.`)
        setPendingActions(prev => ({ ...prev, [field]: false }))
    }

    const handleUpdate = async (field: 'name' | 'targetBudget') => {
        if (!details) return
        setPendingActions(prev => ({ ...prev, [field]: true }))
        try {
            if (field === 'name') {
                await api.envelopeCommands.nameEnvelope(details.envelope.uuid, newValues.name)
                pollForChanges('name', details.envelope.uuid, 'name update', (env) => env?.envelope.name === newValues.name)
            } else {
                await api.envelopeCommands.updateTargetBudget(details.envelope.uuid, newValues.targetBudget, details.envelope.currentAmount)
                pollForChanges('targetBudget', details.envelope.uuid, 'target budget update', (env) => details.envelope.targetedAmount === newValues.targetBudget)
            }
        } catch (err) {
            setGlobalError(`Failed to update envelope ${field}`)
            setPendingActions(prev => ({ ...prev, [field]: false }))
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

    const progress = (parseFloat(details.envelope.currentAmount) / parseFloat(details.envelope.targetedAmount)) * 100

    return (
        <div className="container mx-auto px-4 py-8">
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5 }}
                className="neomorphic rounded-lg p-6 bg-gradient-to-br from-gray-50 to-white"
            >
                <Link href="/envelopes" className="mb-6 flex items-center text-primary hover:underline transition-colors duration-200">
                    <ArrowLeft className="mr-2" /> {t('common.back')}
                </Link>
                <div className="mb-6 flex items-center">
                    <h1 className="text-3xl font-bold mr-2">
                        {editingField === 'name' ? (
                            <form onSubmit={(e) => { e.preventDefault(); handleUpdate('name'); }} className="flex items-center">
                                <input
                                    type="text"
                                    value={newValues.name}
                                    onChange={(e) => setNewValues(prev => ({ ...prev, name: e.target.value }))}
                                    className="flex-grow mr-2 p-2 border-b-2 border-primary bg-transparent focus:outline-none text-2xl font-bold neomorphic-input transition-all duration-200"
                                    autoFocus
                                />
                                <motion.button
                                    type="submit"
                                    className="p-2 text-green-500 mr-1 neomorphic-button hover:shadow-lg transition-all duration-200"
                                    disabled={pendingActions.name}
                                    whileHover={{ scale: 1.05 }}
                                    whileTap={{ scale: 0.95 }}
                                >
                                    {pendingActions.name ? <Loader2 className="h-5 w-5 animate-spin" /> : <Check className="h-5 w-5" />}
                                </motion.button>
                                <motion.button
                                    type="button"
                                    onClick={() => setEditingField(null)}
                                    className="p-2 text-red-500 neomorphic-button hover:shadow-lg transition-all duration-200"
                                    whileHover={{ scale: 1.05 }}
                                    whileTap={{ scale: 0.95 }}
                                >
                                    <X className="h-5 w-5" />
                                </motion.button>
                            </form>
                        ) : (
                            details.envelope.name
                        )}
                    </h1>
                    {editingField !== 'name' && (
                        <motion.button
                            onClick={() => setEditingField('name')}
                            className="p-2 text-primary neomorphic-button hover:shadow-lg transition-all duration-200"
                            whileHover={{ scale: 1.05 }}
                            whileTap={{ scale: 0.95 }}
                            aria-label={t('envelopes.updateName')}
                        >
                            <Edit2 className="h-5 w-5" />
                        </motion.button>
                    )}
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <motion.div
                        className="p-6 rounded-lg neomorphic bg-gradient-to-br from-blue-50 to-white"
                        whileHover={{ scale: 1.02 }}
                        transition={{ type: "spring", stiffness: 300 }}
                    >
                        <h2 className="text-xl font-semibold mb-2 flex items-center">
                            <DollarSign className="mr-2 text-primary" />
                            {t('envelopes.currentAmount')}
                        </h2>
                        <p className="text-3xl font-bold text-primary">
                            {details.envelope.currentAmount}
                        </p>
                    </motion.div>
                    <motion.div
                        className="p-6 rounded-lg neomorphic bg-gradient-to-br from-green-50 to-white"
                        whileHover={{ scale: 1.02 }}
                        transition={{ type: "spring", stiffness: 300 }}
                    >
                        <h2 className="text-xl font-semibold mb-2">{t('envelopes.targetedAmount')}</h2>
                        <div className="flex items-center">
                            {editingField === 'targetBudget' ? (
                                <form onSubmit={(e) => { e.preventDefault(); handleUpdate('targetBudget'); }} className="flex items-center w-full">
                                    <input
                                        type="text"
                                        value={newValues.targetBudget}
                                        onChange={(e) => handleAmountChange(e.target.value)}
                                        className="flex-grow mr-2 p-2 border-b-2 border-primary bg-transparent focus:outline-none text-xl neomorphic-input transition-all duration-200"
                                        autoFocus
                                    />
                                    <motion.button
                                        type="submit"
                                        className="p-2 text-green-500 mr-1 neomorphic-button hover:shadow-lg transition-all duration-200"
                                        disabled={pendingActions.targetBudget}
                                        whileHover={{ scale: 1.05 }}
                                        whileTap={{ scale: 0.95 }}
                                    >
                                        {pendingActions.targetBudget ? <Loader2 className="h-5 w-5 animate-spin" /> : <Check className="h-5 w-5" />}
                                    </motion.button>
                                    <motion.button
                                        type="button"
                                        onClick={() => setEditingField(null)}
                                        className="p-2 text-red-500 neomorphic-button hover:shadow-lg transition-all duration-200"
                                        whileHover={{ scale: 1.05 }}
                                        whileTap={{ scale: 0.95 }}
                                    >
                                        <X className="h-5 w-5" />
                                    </motion.button>
                                </form>
                            ) : (
                                <>
                                    <p className="text-3xl font-bold text-primary mr-2">
                                        {details.envelope.targetedAmount}
                                    </p>
                                    <motion.button
                                        onClick={() => setEditingField('targetBudget')}
                                        className="p-2 text-primary neomorphic-button hover:shadow-lg transition-all duration-200"
                                        whileHover={{ scale: 1.05 }}
                                        whileTap={{ scale: 0.95 }}
                                        aria-label={t('envelopes.updateTargetBudget')}
                                    >
                                        <Edit2 className="h-5 w-5" />
                                    </motion.button>
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
                    {t('envelopes.transactionHistory')}
                </h2>
                <div className="overflow-x-auto">
                    <table className="w-full border-collapse neomorphic">
                        <thead>
                        <tr className="bg-gradient-to-r from-gray-50 to-white">
                            <th className="p-3 text-left border-b">{t('envelopes.date')}</th>
                            <th className="p-3 text-left border-b">{t('envelopes.amount')}</th>
                            <th className="p-3 text-left border-b">{t('envelopes.type')}</th>
                        </tr>
                        </thead>
                        <tbody>
                        {details.history.map((transaction, index) => (
                            <motion.tr
                                key={index}
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.3, delay: index * 0.1 }}
                                className="border-b hover:bg-gray-50 transition-colors duration-150"
                            >
                                <td className="p-3">{formatDate(transaction.created_at)}</td>
                                <td className="p-3">{transaction.monetary_amount}</td>
                                <td className={`p-3 ${transaction.transaction_type === 'credit' ? 'text-green-600' : 'text-red-600'}`}>
                                    {t(`envelopes.${transaction.transaction_type}`)}
                                </td>
                            </motion.tr>
                        ))}
                        </tbody>
                    </table>
                </div>
            </motion.div>
        </div>
    )
}
