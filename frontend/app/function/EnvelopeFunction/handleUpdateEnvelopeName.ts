import { React, Dispatch, SetStateAction } from "react";
import { useEnvelopes } from "../../domain/envelope/envelopeHooks";
import { EditingNameInterface, Envelope, EnvelopeDetails, EnvelopesData, UpdateEnvelopeName } from "../../domain/envelope/envelopeTypes";

export interface HandleUpdateEnvelopeNameProps {
    e: React.MouseEvent;
    editingName: EditingNameInterface;
    name: string;
    envelopesData: EnvelopesData;
    updateEnvelopeName: UpdateEnvelopeName;
    setError: Dispatch<SetStateAction<string | null>>;
    setPendingActions: Dispatch<SetStateAction<Record<string, string>>>;
    setEditingName: Dispatch<SetStateAction<any | null>>;
}
const handleUpdateEnvelopeName = async ({
    e,
    editingName,
    name,
    envelopesData,
    updateEnvelopeName,
    setError,
    setPendingActions,
    setEditingName,
}: HandleUpdateEnvelopeNameProps) => {
    e.preventDefault()
    if (editingName && name.trim() !== '') {
        const { id, name: currentName } = editingName;
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
        if (name === currentEnvelope?.name) {
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
            setPendingActions((prev) => {
                const newPending = { ...prev };
                delete newPending[id];
                return newPending;
            });

            setEditingName(null);
        }
    }
};

export default handleUpdateEnvelopeName;
