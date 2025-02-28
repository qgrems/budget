import { motion, MotionProps } from "framer-motion";
import React, { ReactNode, MouseEvent } from "react";

interface AnimatedCardProps extends MotionProps {
    children: ReactNode;
    className?: string;
    pending?: boolean;
    deleted?: boolean;
    onClick?: (e: MouseEvent<HTMLDivElement>) => void;
    preventClickOnSelectors?: string;
}

const AnimatedCard: React.FC<AnimatedCardProps> = ({
    children,
    className = "",
    pending = false,
    deleted = false,
    onClick,
    preventClickOnSelectors = "button, input",
    ...motionProps
}) => {
    return (
        <motion.div
            layout
            initial={{ opacity: 0, scale: 0.8 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0, scale: 0.8 }}
            transition={{ duration: 0.3 }}
            className={`neomorphic p-3 md:p-4 ${pending ? "opacity-70" : ""} ${deleted ? "bg-red-100" : ""} ${className}`}
            onClick={(e) => {
                if (
                    preventClickOnSelectors &&
                    e.target instanceof HTMLElement &&
                    e.target.closest(preventClickOnSelectors)
                ) {
                    e.preventDefault();
                    return;
                }
                onClick?.(e);
            }}
            {...motionProps}
        >
            {children}
        </motion.div>
    );
};

export default AnimatedCard;
