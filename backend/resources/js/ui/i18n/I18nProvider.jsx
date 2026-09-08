import React, { createContext, useCallback, useContext, useMemo, useState } from 'react'
import { dictionaries, locales } from './dictionaries.js'

const STORAGE_KEY = 'check-db.locale'

const I18nContext = createContext(null)

function initialLocale () {
    try {
        const stored = localStorage.getItem(STORAGE_KEY)
        if (stored && locales.includes(stored)) return stored
    } catch {
        // Private browsing and blocked site data both throw here.
    }

    const browser = (navigator.language || 'en').slice(0, 2)

    return locales.includes(browser) ? browser : 'en'
}

export function I18nProvider ({ children }) {
    const [locale, setLocaleState] = useState(initialLocale)

    const setLocale = useCallback((next) => {
        setLocaleState(next)
        document.documentElement.lang = next

        try {
            localStorage.setItem(STORAGE_KEY, next)
        } catch {
            // Remembering the choice is a convenience, not a requirement.
        }
    }, [])

    const value = useMemo(() => {
        const table = dictionaries[locale] ?? dictionaries.en

        const t = (key, params) => {
            let text = table[key] ?? dictionaries.en[key] ?? key

            if (params) {
                for (const [name, replacement] of Object.entries(params)) {
                    text = text.replaceAll(`:${name}`, String(replacement))
                }
            }

            return text
        }

        return { locale, setLocale, t }
    }, [locale, setLocale])

    return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>
}

export function useI18n () {
    const context = useContext(I18nContext)

    if (!context) {
        throw new Error('useI18n must be used inside I18nProvider')
    }

    return context
}
