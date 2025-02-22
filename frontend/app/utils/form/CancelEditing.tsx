import React, { MouseEvent, Dispatch, SetStateAction } from "react";

interface CancelEditingProps<T> {
    e: MouseEvent<HTMLButtonElement>;
    setEditing: Dispatch<SetStateAction<T | null>>;
}

const cancelEditing = <T,>({ e, setEditing }: CancelEditingProps<T>) => {
    e.preventDefault();
    setEditing(null);
};

export default cancelEditing;
