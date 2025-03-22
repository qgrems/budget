'use client'

import { useState } from 'react'
import { useAppContext } from '../../providers'
import { User, UserState } from './userTypes'
import { api } from '../../infrastructure/api'
import { authService } from '../../services/auth';
import { useRouter } from 'next/router'

export function useUser() {
  const { state, login, logout, setState } = useAppContext()
  const [error, setError] = useState<string | null>(null)

  const signIn = async (email: string, password: string, setError): Promise<boolean> => {
    try {
      const success = await login(email, password)
      if (!success) {
        setError('Failed to sign in. Please check your credentials and try again.')
      }
      return success
    } catch (err) {
      setError(err.message)
      return false
    }
  }

  const signOut = async (setError) => {
    try {
      await authService.logout();
      logout();
    } catch (error) {
      setError(error.message);
    }
  }

  const createUser = async (userData: any, setError) => {
    setError(null)
    try {
      await api.commands.createUser(userData)
      return true
    } catch (err) {
      setError(err.message)
      return false
    }
  }

  const hasEnvelopes = async (): Promise<boolean> => {
    try {
      const envelopes = await api.envelopeQueries.listEnvelopes()
      return envelopes.envelopes.length > 0
    } catch (err) {
      return false
    }
  }

  const updateFirstname = async (firstname: string, setError, setValidMessage) => {
    setState(prevState => ({
      ...prevState,
      user: prevState.user ? { ...prevState.user, firstname, pending: true } : null
    }))
    try {
      await api.commands.updateFirstname(firstname)
      await pollForChanges(setValidMessage, 'firstname', firstname)
      return true
    } catch (err) {
      setError(err.message)
      setState(prevState => ({
        ...prevState,
        user: prevState.user ? { ...prevState.user, pending: false } : null
      }))
      return false
    }
  }

  const updateLastname = async (lastname: string, setError, setValidMessage) => {
    setState(prevState => ({
      ...prevState,
      user: prevState.user ? { ...prevState.user, lastname, pending: true } : null
    }))
    try {
      await api.commands.updateLastname(lastname)
      await pollForChanges(setValidMessage, 'lastname', lastname)
      return true
    } catch (err) {
      setError(err.message)
      setState(prevState => ({
        ...prevState,
        user: prevState.user ? { ...prevState.user, pending: false } : null
      }))
      return false
    }
  }

  const changePassword = async (oldPassword: string, newPassword: string, setError, setValidMessage) => {
    setState(prevState => ({
      ...prevState,
      user: prevState.user ? { ...prevState.user, pending: true } : null
    }))
    try {
      await api.commands.changePassword(oldPassword, newPassword)
      await pollForChanges(setValidMessage, 'password')
      return true
    } catch (err) {
      setError(err.message)
      setState(prevState => ({
        ...prevState,
        user: prevState.user ? { ...prevState.user, pending: false } : null
      }))
      return false
    }
  }
  const deleteAccount = async (setError, setValidMessage, t) => {
    try {
      await api.commands.deleteAccount()
      setValidMessage(t('users.accountDeleted'))
      logout();
      const router = useRouter();
      router.push('/');
    } catch (err) {
      setError(err.message)
      setState(prevState => ({
        ...prevState,
        user: prevState.user ? { ...prevState.user, pending: false } : null
      }))
      return false
    }
  }
  const pollForChanges = async (setValidMessage, field: 'firstname' | 'lastname' | 'password', expectedValue?: string) => {
    const maxRetries = 10
    const retryInterval = 1000 // 1 second

    for (let i = 0; i < maxRetries; i++) {
      await new Promise(resolve => setTimeout(resolve, retryInterval))
      try {
        const updatedUser = await api.queries.getCurrentUser()
        if (field === 'password' || updatedUser[field] === expectedValue) {
          setState(prevState => ({
            ...prevState,
            user: { ...updatedUser, pending: false }
          }))
          setValidMessage(`Successfully updated ${field}`)
          return
        }
      } catch (err) {
      }
    }
    setError(`Failed to confirm ${field} update. Please refresh.`)
  }

  return {
    user: state.user,
    isAuthenticated: state.isAuthenticated,
    loading: state.loading,
    error,
    signIn,
    signOut,
    createUser,
    hasEnvelopes,
    updateFirstname,
    updateLastname,
    changePassword,
    deleteAccount,
  }
}
