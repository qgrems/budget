'use client'
import {motion} from "framer-motion";
import {useValidMessage} from "../contexts/ValidContext";

export default function ValidModal() {
    const {validMessage, setValidMessage} = useValidMessage();

    return (
        <div>
            <motion.div
                initial={{opacity: 0, y: -50}}
                animate={{opacity: 1, y: 0}}
                exit={{opacity: 0, y: -50}}
                className="fixed top-4 left-0 right-0 mx-auto z-50 p-4"
            >
                <div className="bg-white text-black p-4 rounded-md shadow-lg max-w-md w-full mx-auto">
                    <div className="mb-2 ">{validMessage}</div>
                    <div className="w-full bg-gray-300 rounded-full h-1.5 overflow-hidden">
                        <div className="progress-bar-validation bg-green-500 h-1.5 rounded-full" />
                    </div>
                </div>
            </motion.div>
        </div>
    )
}