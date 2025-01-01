'use client'

import {createContext, ReactNode, useContext, useState} from "react";
import { useTranslation } from '../hooks/useTranslation'
import ErrorModal from "../components/ErrorModal";

type ErrorContextType = {
    error: string;
    setError: (error: string) => void;
}
const ErrorContext = createContext<ErrorContextType | undefined>(undefined);

export function ErrorProvider({children}: { children: ReactNode }) {
    const { t } = useTranslation()
    const [error, setError] = useState<string>('');

    const addError = (error: string) => {
        setError(t(error));

        setTimeout(() => {
            setError('');
        }, 5000);
    }

    return (
        <ErrorContext.Provider value={{error, setError: addError}}>
            {children}
            {t(error) && <ErrorModal />}
        </ErrorContext.Provider>
    )
}

export function useError() {
    const context = useContext(ErrorContext);
    if (context === undefined) {
        throw new Error("useError must be used within an ErrorProvider");
    }
    return context;
}