const isInvalidInput = (value: string): boolean => {
    // Allow empty input
    if (value === "") return false

    // Check if the input is a valid number or a partial decimal input
    return !/^\d{1,10}(\.\d{0,2})?$/.test(value)
}
export default isInvalidInput