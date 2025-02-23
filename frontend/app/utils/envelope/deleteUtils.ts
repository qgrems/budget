import { React } from "react";

interface DeleteEntityParams<T> {
    entityToDelete: T | null;
    deleteFunction: (id: string, setError: (message: string) => void) => Promise<void>;
    setDeleteModalOpen: React.Dispatch<React.SetStateAction<boolean>>;
    setError: React.Dispatch<React.SetStateAction<string | null>>;
    setEntityToDelete: React.Dispatch<React.SetStateAction<T | null>>;
}

export const handleDeleteEntity = async <T extends { id: string }>({
    entityToDelete,
    deleteFunction,
    setDeleteModalOpen,
    setError,
    setEntityToDelete
}: DeleteEntityParams<T>) => {
    if (entityToDelete) {
        const { id } = entityToDelete;
        setDeleteModalOpen(false);
        await deleteFunction(id, setError);
        setEntityToDelete(null);
    }
};

export default handleDeleteEntity
