'use client'

import {createContext, ReactNode, useContext, useState} from "react";
import ValidModal from "../components/ValidModal";

type ValidContextType = {
    validMessage: string;
    setValidMessage: (validMessage: string) => void;
}
const ValidContext = createContext<ValidContextType | undefined>(undefined);

export function ValidProvider({children}: { children: ReactNode }) {
    const [validMessage, setValidMessage] = useState<string>('');

    const addMessage = (message: string) => {
        setValidMessage(message);

        setTimeout(() => {
            setValidMessage('');
        }, 2000);
    }

    return (
        <ValidContext.Provider value={{validMessage, setValidMessage: addMessage}}>
            {children}
            {validMessage && <ValidModal />}
        </ValidContext.Provider>
    )
}

export function useValidMessage() {
    const context = useContext(ValidContext);
    if (context === undefined) {
        throw new Error("useError must be used within an ValidProvider");
    }
    return context;
}