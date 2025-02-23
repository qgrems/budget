import { Dispatch, SetStateAction } from "react";
import formatAmount from "../../utils/envelope/formatAmount";
import validateAmount from "../../utils/envelope//validateAmount";
interface Action {
    type: "credit" | "debit";
    id: string;
    amount: string;
}
export const handleDebitEnvelope = (
    id: string,
    currentAmount: string,
    amounts: Record<string, string>,
    setCurrentAction: Dispatch<SetStateAction<Action | null>>,
    setDescriptionModalOpen: Dispatch<SetStateAction<boolean>>,
    setError: Dispatch<SetStateAction<string | null>>,
    t: (key: string) => string
) => {
    if (amounts[id]) {
        const formattedAmount = formatAmount(amounts[id]);
        const maxDebit = Number.parseFloat(currentAmount).toFixed(2);

        if (validateAmount(formattedAmount, currentAmount, "0", false)) {
            setCurrentAction({ type: "debit", id, amount: formattedAmount });
            setDescriptionModalOpen(true);
            setError(null);
        } else {
            setError(t("envelopes.debitError").replace("${amount}", formattedAmount).replace("${max}", maxDebit));
        }
    }
};