'use client'

import { useCallback } from 'react'
import { translations } from '../i18n/translations'
import { useLanguage } from '../contexts/LanguageContext'

export function useTranslation() {
    const { language, setLanguage } = useLanguage()

    const t = useCallback((key: string) => {
        if (typeof key !== 'string') {
            return key;
        }
        const keys = key?.split('.')
        let value: any = translations[language]
        if (!keys) {
            return value
        }
        for (const k of keys) {
            if (value === undefined) {
                return key
            }
            value = value[k]
        }
        return value
    }, [language])

    return { t, setLanguage, language }
}
