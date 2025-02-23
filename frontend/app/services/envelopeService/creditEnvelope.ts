// 📂 src/services/envelopeService.ts
import { Dispatch, SetStateAction } from "react";
import formatAmount from "../../utils/envelope/formatAmount";
import validateAmount from "../../utils/envelope//validateAmount";

interface Action {
    type: "credit" | "debit";
    id: string;
    amount: string;
}

export const handleCreditEnvelope = (
    id: string,
    currentAmount: string,
    targetedAmount: string,
    amounts: Record<string, string>,
    setCurrentAction: Dispatch<SetStateAction<Action | null>>,
    setDescriptionModalOpen: Dispatch<SetStateAction<boolean>>,
    setError: Dispatch<SetStateAction<string | null>>,
    t:string
) => {
    if (amounts[id]) {
        const formattedAmount = formatAmount(amounts[id]);
        const maxCredit = (Number.parseFloat(targetedAmount) - Number.parseFloat(currentAmount)).toFixed(2);

        if (validateAmount(formattedAmount, currentAmount, targetedAmount, true)) {
            setCurrentAction({ type: "credit", id, amount: formattedAmount });
            setDescriptionModalOpen(true);
            setError(null);
        } else {
            setError(t("envelopes.creditError").replace("${amount}", formattedAmount).replace("${max}", maxCredit));
        }
    }
};

