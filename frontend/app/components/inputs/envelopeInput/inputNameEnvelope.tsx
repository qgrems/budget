import React, { useRef, useEffect } from "react";

type InputTextProps = {
    value: string;
    onChange: (value: string) => void;
    onFocus?: () => void;
    className?: string;
    autoFocus?: boolean;
    minWidth?: number; // ✅ Taille minimale en pixels
};

export default function InputNameEnvelope({
    value,
    onChange,
    onFocus,
    className = "",
    autoFocus = false,
    minWidth = 100, // ✅ Valeur par défaut
}: InputTextProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const spanRef = useRef<HTMLSpanElement>(null);

    useEffect(() => {
        if (inputRef.current && spanRef.current) {
            // ✅ Ajuste la largeur de l'input en fonction du texte
            const textWidth = spanRef.current.offsetWidth;
            inputRef.current.style.width = `${Math.max(textWidth + 40, minWidth)}px`;
        }
    }, [value]); // Se déclenche à chaque changement du texte

    return (
        <div className="relative ">
            {/* ✅ Élément invisible pour mesurer la largeur du texte */}
            <span
                ref={spanRef}
                className="absolute invisible whitespace-nowrap text-lg font-bold"
            >
                {value || "Placeholder"} {/* Assure une taille minimale */}
            </span>

            <input
                ref={inputRef}
                type="text"
                value={value}
                onChange={(e) => onChange(e.target.value)}
                onFocus={onFocus}
                className={`p-1 mr-2 neomorphic-input text-lg md:text-xl font-bold ${className}`}
                autoFocus={autoFocus}
                style={{ minWidth: `${minWidth}px` }} // ✅ Applique la taille minimale
            />
        </div>
    );
}
