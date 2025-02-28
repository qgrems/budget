import React from "react"

const handleTargetedAmountChange = (value: string, setAmounts: React.Dispatch<React.SetStateAction<{} >>
) => {
    // Remove any non-digit and non-dot characters
    value = value.replace(/[^\d.]/g, "")

    // Handle cases where the decimal point might be the first character
    if (value.startsWith(".")) {
        value = "0" + value
    }

    // Ensure only one decimal point
    const parts = value.split(".")
    if (parts.length > 2) {
        parts.pop()
        value = parts.join(".")
    }

    // Enforce character limits
    if (value.includes(".")) {
        // With decimal: limit to 13 characters (10 before decimal, 1 decimal point, 2 after decimal)
        const [integerPart, decimalPart] = value.split(".")
        value = `${integerPart.slice(0, 10)}.${decimalPart.slice(0, 2)}`
    } else {
        // Without decimal: limit to 10 characters
        value = value.slice(0, 10)
    }

    // Remove leading zeros, except if it's "0." or "0"
    if (value.length > 1 && value.startsWith("0") && !value.startsWith("0.")) {
        value = value.replace(/^0+/, "")
    }

   
        setAmounts(value )
}
export default handleTargetedAmountChange