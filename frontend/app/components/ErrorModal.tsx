'use client'
import {motion} from "framer-motion";
import {useEffect} from "react";
import {useError} from "../contexts/ErrorContext";

export default function ErrorModal() {
    const {error, setError} = useError();



    return (
        <div>
            <motion.div
                initial={{opacity: 0, y: -50}}
                animate={{opacity: 1, y: 0}}
                exit={{opacity: 0, y: -50}}
                className="fixed top-4 left-0 right-0 mx-auto z-50 p-4"
            >
                <div className="bg-white text-black p-4 rounded-md shadow-lg max-w-md w-full mx-auto">
                    <div className="mb-2 ">{error}</div>
                    <div className="w-full bg-gray-300 rounded-full h-1.5 overflow-hidden">
                        <div className="progress-bar bg-red-500 h-1.5 rounded-full" />
                    </div>
                </div>
            </motion.div>
        </div>
    )
}