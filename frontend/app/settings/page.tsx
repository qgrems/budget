'use client'

import { useState, useEffect } from 'react'
import { useUser } from '../domain/user/userHooks'
import { useTranslation } from '../hooks/useTranslation'
import { Check, X, Mail, Users } from 'lucide-react'
import { useRouter } from 'next/navigation'
import { useError } from "../contexts/ErrorContext";
import { useValidMessage } from "../contexts/ValidContext";
import ActionButton from '../components/buttons/actionButton'
import ValidInputButton from '../components/buttons/validInputButton'
import InputNameEnvelope from '../components/inputs/envelopeInput/inputNameEnvelope'
import PasswordInput from '../components/inputs/passwordInput'
import { Modal } from '../components/Modal'

export default function SettingsPage() {
  const { user, updateFirstname, updateLastname, changePassword, signOut, deleteAccount } = useUser()
  const { t } = useTranslation()
  const [firstname, setFirstname] = useState('')
  const [lastname, setLastname] = useState('')
  const [oldPassword, setOldPassword] = useState('')
  const [newPassword, setNewPassword] = useState('')
  const [confirmNewPassword, setConfirmNewPassword] = useState('')
  const [editingFirstname, setEditingFirstname] = useState(false)
  const [editingLastname, setEditingLastname] = useState(false)
  const [modalDeleteAccountOpen, setModalDeleteAccountOpen] = useState(false)
  const [success, setSuccess] = useState<string | null>(null)
  const { validMessage, setValidMessage } = useValidMessage();
  const router = useRouter()
  const { error, setError } = useError()
  useEffect(() => {
    if (user) {
      setFirstname(user.firstname || '')
      setLastname(user.lastname || '')
    }
  }, [user])
  const handleUpdateFirstname = async () => {
    setError(null)
    setSuccess(null)
    const result = await updateFirstname(firstname, setError, setValidMessage)
    if (result) {
      setSuccess(t('settings.firstnameUpdated'))
      setValidMessage('settings.firstnameUpdated')
      setEditingFirstname(false)
    }
  }

  const handleUpdateLastname = async () => {
    setError(null)
    setSuccess(null)
    const result = await updateLastname(lastname, setError, setValidMessage)
    if (result) {
      setSuccess(t('settings.lastnameUpdated'))
      setValidMessage('settings.lastnameUpdated')
      setEditingLastname(false)
    }
  }

  const handleChangePassword = async (e: React.FormEvent) => {
    e.preventDefault()
    if (newPassword !== confirmNewPassword) {
      setError(("users.passwordsDoNotMatch"))
      return
    }
    setError(null)
    setSuccess(null)
    const result = await changePassword(oldPassword, newPassword, setError, setValidMessage)
    if (result) {
      setValidMessage(t('settings.passwordChanged'))
      setOldPassword('')
      setNewPassword('')
      setConfirmNewPassword('')
    }
  }
  const toggleModalDeleteAccount = () => {
    setModalDeleteAccountOpen(prev => !prev);
  };
  const handleDeleteAccount = async () => {
    await deleteAccount(setError, setValidMessage, t)
    router.push('/')
  }
  const handleSignOut = async () => {
    await signOut(setError)
    router.push('/')
  }

  if (!user) {
    return <div className="text-center mt-8">{t('settings.noProfile')}</div>
  }

  return (
    <div className="container mx-auto px-4 py-8 max-w-2xl">
      <h1 className="text-3xl font-bold mb-6">{t('settings.title')}</h1>
      <div className="space-y-6">
        <div className="bg-white shadow-md rounded-lg p-4 neomorphic mx-auto">
          <h2 className="text-2xl font-semibold mb-4">{t('settings.personalInfo')}</h2>
          <div className="space-y-4">
            <div>
              <label htmlFor="firstname" className="block text-sm font-medium text-gray-700 mb-1">{t('settings.firstname')}</label>
              <div className="flex items-center">
                {editingFirstname ? (
                  <>
                    <div className='flex items-center flex-grow'>
                      <InputNameEnvelope
                        value={firstname}
                        onChange={(value) => setFirstname(value)} // Just pass the string directly
                        className="custom-input-class maxWidthInput" // Tu peux ajouter des classes supplémentaires si nécessaire
                      />
                      <ValidInputButton
                        onClick={handleUpdateFirstname}
                        icon={<Check className="h-4 w-4 md:h-5 md:w-5" />}
                        className=" text-green-500 mr-1"
                        disabled={user.pending}
                        text=""
                      />
                      <ValidInputButton
                        onClick={() => {
                          setEditingFirstname(false)
                          setFirstname(user.firstname || '')
                        }}
                        icon={<X className="h-4 w-4" />}
                        className="text-red-500 mr-1"
                        disabled={user.pending}
                        text=""
                      />
                    </div>

                  </>
                ) : (
                  <>
                    <span className="flex-grow">{user.firstname}</span>
                    <ActionButton
                      onClick={() => setEditingFirstname(true)}
                      label={t('settings.edit')}
                      disabled={user.pending}
                      className="text-primary"
                    />
                  </>
                )}
              </div>
            </div>
            <div>
              <label htmlFor="lastname" className="block text-sm font-medium text-gray-700 mb-1">{t('settings.lastname')}</label>
              <div className="flex items-center">
                {editingLastname ? (
                  <div className='flex items-center flex-grow'>
                    <InputNameEnvelope
                      value={lastname}
                      onChange={(value) => setLastname(value)}
                      className="custom-input-class"
                    />
                    <ValidInputButton
                      onClick={handleUpdateLastname}
                      icon={<Check className="h-4 w-4 md:h-5 md:w-5" />}
                      className=" text-green-500 mr-1"
                      disabled={user.pending}
                      text=""
                    />
                    <ValidInputButton
                      onClick={() => {
                        setEditingLastname(false)
                        setLastname(user.lastname || '')
                      }}
                      icon={<X className="h-4 w-4" />}
                      className="text-red-500 mr-1"
                      disabled={user.pending}
                      text=""
                    />

                  </div>
                ) : (
                  <>
                    <span className="flex-grow">{user.lastname}</span>
                    <ActionButton
                      onClick={() => setEditingLastname(true)}
                      label={t('settings.edit')}
                      disabled={user.pending}
                      className="text-primary"
                    />
                  </>
                )}
              </div>
            </div>
            <div>
              <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">{t('settings.email')}</label>
              <div className="flex items-center">
                <span className="flex-grow flex items-center">
                  <Mail className="w-4 h-4 mr-2 text-gray-500" />
                  {user.email}
                </span>
              </div>
            </div>
            <div className="mt-8">
              <ActionButton
                onClick={toggleModalDeleteAccount}
                disabled={user.pending}
                label={t('settings.deleteAccount')}
                className="w-full py-2 px-4 neomorphic-button text-red-500 hover:text-red-700 transition-colors"
              />
            </div>
          </div>
        </div>

        <div className="bg-white shadow-md rounded-lg p-4 neomorphic mx-auto">
          <h2 className="text-2xl font-semibold mb-4">{t('settings.changePassword')}</h2>
          <form onSubmit={handleChangePassword} className="space-y-3">
            <PasswordInput
              id="oldPassword"
              value={oldPassword}
              onChange={setOldPassword}
              placeholder="Enter your old password"
              label="Old Password"
            />
            <PasswordInput
              id="newPassword"
              value={newPassword}
              onChange={setNewPassword}
              placeholder="Enter your new password"
              label="New Password"
            />
            <PasswordInput
              id="confirmNewPassword"
              value={confirmNewPassword}
              onChange={setConfirmNewPassword}
              placeholder="Confirm your new password"
              label="Confirm New Password"
            />
            <ActionButton
              onClick={() => handleChangePassword}
              disabled={user.pending}
              label={user.pending ? t('common.loading') : t('settings.changePassword')}
              className="text-primary neomorphic-button text-primary w-full py-2"
            />
          </form>

        </div>
      </div>
      <div className="mt-8">
        <ActionButton
          onClick={handleSignOut}
          disabled={user.pending}
          label={t('settings.signOut')}
          className="w-full py-2 px-4 neomorphic-button text-red-500 hover:text-red-700 transition-colors"
        />
      </div>
      <Modal isOpen={modalDeleteAccountOpen} onClose={toggleModalDeleteAccount}>
        <h2 className='font-semibold'>{t('users.confirmDeleteAccount')}</h2>
        <div className="flex justify-around">
          <ActionButton onClick={handleDeleteAccount}
            disabled={user.pending}
            label={t('yes')}
            className=" py-2 px-4 neomorphic-button text-red-500 hover:text-red-700 transition-colors">
          </ActionButton>
          <ActionButton onClick={toggleModalDeleteAccount}
            disabled={user.pending}
            label={t('no')}
            className=" py-2 px-4 neomorphic-button text-primary">
          </ActionButton>
        </div>
      </Modal>
    </div>
  )
}
