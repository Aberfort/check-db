import { useCallback, useEffect, useState } from 'react'

const STORAGE_KEY = 'check-db.theme'

function stored () {
    try {
        const value = localStorage.getItem(STORAGE_KEY)

        return value === 'light' || value === 'dark' ? value : null
    } catch {
        return null
    }
}

/**
 * Returns the theme actually in effect. With no explicit choice the OS setting
 * decides, and the stamp is left off the root element so it keeps deciding.
 */
export default function useTheme () {
    const [choice, setChoice] = useState(stored)

    const systemDark = typeof matchMedia === 'function'
        && matchMedia('(prefers-color-scheme: dark)').matches

    const resolved = choice ?? (systemDark ? 'dark' : 'light')

    useEffect(() => {
        if (choice) {
            document.documentElement.setAttribute('data-theme', choice)
        } else {
            document.documentElement.removeAttribute('data-theme')
        }
    }, [choice])

    const toggle = useCallback(() => {
        setChoice((current) => {
            const next = (current ?? (systemDark ? 'dark' : 'light')) === 'dark' ? 'light' : 'dark'

            try {
                localStorage.setItem(STORAGE_KEY, next)
            } catch {
                // Theme still applies for this session.
            }

            return next
        })
    }, [systemDark])

    return { theme: resolved, toggle }
}
