export const currencyOptions = [
    { value: "USD", label: "$ - Dollar US" },
    { value: "EUR", label: "€ - Euro" },
    { value: "GBP", label: "£ - Livre Sterling" },
];
const currencySymbols: Record<string, string> = {
    "USD": "$",
    "EUR": "€",
    "GBP": "£",
    "JPY": "¥",
    "CHF": "₣"
};
export const getCurrencySymbol = (currencyCode: string): string => {
    return currencySymbols[currencyCode] || currencyCode;
};
