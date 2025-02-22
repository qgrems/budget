const handleNameChange = (newName: string, editingName, setEditingName) => {
    if (editingName) {
        setEditingName({ ...editingName, name: newName })
    }
}
export default handleNameChange