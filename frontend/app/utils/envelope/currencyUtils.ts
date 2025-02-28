import { getCurrencySymbol } from "../../constants/currencyOption";

// Fonction pour formater le montant avec le symbole monétaire correctement positionné
export const formatCurrency = (amount: number | string, currency: string): string => {
    const formattedAmount = Number.parseFloat(amount as string).toFixed(2);
    const symbol = getCurrencySymbol(currency);

    // Positionne le symbole avant ou après selon la devise
    return currency === "EUR" ? `${formattedAmount}${symbol}` : `${symbol}${formattedAmount}`;
};
