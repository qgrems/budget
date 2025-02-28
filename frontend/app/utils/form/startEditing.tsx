interface SetEditing {
    (editing: { id: string; name: string } | null): void;
}

const handleStartEditingName = (id: string, currentName: string, setEditing: SetEditing) => {
    console.log(currentName)
    setEditing({ id, name: currentName });
};
export default handleStartEditingName