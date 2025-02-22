/**
 * Valide si un montant est valide en fonction du type d'opération (crédit ou débit).
 * 
 * @param amount - Le montant à valider
 * @param currentAmount - Le montant actuel
 * @param targetedAmount - Le montant cible (pour un crédit)
 * @param isCredit - Un indicateur si l'opération est un crédit ou un débit
 * @returns True si l'opération est valide, sinon false
 */
const validateAmount = (
    amount: string,
    currentAmount: string,
    targetedAmount: string,
    isCredit: boolean
): boolean => {
    const amountFloat = Number.parseFloat(amount);
    const currentAmountFloat = Number.parseFloat(currentAmount);
    const targetedAmountFloat = Number.parseFloat(targetedAmount);

    if (isCredit) {
        return currentAmountFloat + amountFloat <= targetedAmountFloat;
    } else {
        return currentAmountFloat - amountFloat >= 0;
    }
};

export default validateAmount;
