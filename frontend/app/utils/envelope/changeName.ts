const handleNameChange = (newName: string, editingName, setEditingName) => {
    console.log(newName)
    if (editingName) {
        setEditingName({ ...editingName, name: newName })
    }
}
export default handleNameChange