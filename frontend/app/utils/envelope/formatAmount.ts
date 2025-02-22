/**
 * Formate un montant en chaîne avec deux décimales
 * @param amount - La chaîne représentant le montant
 * @returns Le montant formaté sous forme de chaîne (ex: "123.45")
 */
const formatAmount = (amount: string): string => {
    if (!amount) return "";

    let [integerPart, decimalPart] = amount.split(".");
    integerPart = integerPart || "0";
    decimalPart = decimalPart || "00";

    // S'assure que la partie décimale ait exactement deux chiffres
    decimalPart = decimalPart.padEnd(2, "0").slice(0, 2);

    return `${integerPart}.${decimalPart}`;
};

export default formatAmount;
