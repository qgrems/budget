"use client"

import { useAppContext } from "../providers"
import { SocketProvider } from "../hooks/useSocket"

export function SocketWrapper({ children }: { children: React.ReactNode }) {
    const { state } = useAppContext()

    return (
        <SocketProvider user={state.user}>
            {children}
        </SocketProvider>
    )
}
