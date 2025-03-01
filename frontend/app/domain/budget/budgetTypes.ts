export interface BudgetPlansCalendar {
    [year: string]: {
        [month: string]: string // month -> budgetPlanId
    }
}

export interface BudgetPlan {
    budgetPlan: {
        uuid: string
        userId: string
        currency: string
        date: string
        createdAt: string
        updatedAt: string
    }
    needs: Array<{
        uuid: string
        budgetPlanUuid: string
        needName: string
        needAmount: string
    }>
    savings: Array<{
        uuid: string
        budgetPlanUuid: string
        savingName: string
        savingAmount: string
    }>
    wants: Array<{
        uuid: string
        budgetPlanUuid: string
        wantName: string
        wantAmount: string
    }>
    incomes: Array<{
        uuid: string
        budgetPlanUuid: string
        incomeName: string
        incomeAmount: string
    }>
}

export interface Income {
    name: string
    amount: string
}

export interface CreateBudgetPlanPayload {
    uuid: string
    currency: string
    date: string
    incomes: Array<{
        uuid: string
        incomeName: string
        amount: string
    }>
}

export interface CreateFromExistingPayload {
    uuid: string
    budgetPlanUuidThatAlreadyExists: string
    date: string
}
