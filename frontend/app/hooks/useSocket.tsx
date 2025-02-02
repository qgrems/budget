"use client"

import { createContext, useContext, useEffect, useState, useRef } from "react"
import { io, type Socket } from "socket.io-client"

type SocketContextType = {
    socket: Socket | null
    isConnected: boolean
}

const SocketContext = createContext<SocketContextType>({
    socket: null,
    isConnected: false
})

export const SocketProvider = ({ children, user }: { children: React.ReactNode, user?: { uuid: string } }) => {
    const [socket, setSocket] = useState<Socket | null>(null)
    const [isConnected, setIsConnected] = useState(false)
    const userRef = useRef(user?.uuid)

    useEffect(() => {
        userRef.current = user?.uuid
    }, [user?.uuid])

    useEffect(() => {
        if (!user?.uuid) return

        const newSocket = io(process.env.NEXT_PUBLIC_WS_URL || "http://localhost:3030", {
            auth: {
                userId: user.uuid,
                token: localStorage.getItem("jwt")
            },
            transports: ["websocket"],
            reconnectionDelay: 5000,
            reconnectionAttempts: Infinity
        })

        const onConnect = () => {
            setIsConnected(true)
            console.log("WebSocket connected")
        }

        const onDisconnect = () => {
            setIsConnected(false)
            console.log("WebSocket disconnected")
        }

        newSocket.on("connect", onConnect)
        newSocket.on("disconnect", onDisconnect)

        setSocket(newSocket)

        return () => {
            newSocket.off("connect", onConnect)
            newSocket.off("disconnect", onDisconnect)
            newSocket.disconnect()
            setSocket(null)
            setIsConnected(false)
        }
    }, [user?.uuid])

    return (
        <SocketContext.Provider value={{ socket, isConnected }}>
            {children}
        </SocketContext.Provider>
    )
}

export const useSocket = () => {
    const context = useContext(SocketContext)
    if (!context) {
        throw new Error("useSocket must be used within SocketProvider")
    }
    return context
}
