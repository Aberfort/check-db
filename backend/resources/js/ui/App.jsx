import React from 'react'
import { Toaster } from 'react-hot-toast'

import OverviewPage from './pages/OverviewPage.jsx'
import { I18nProvider } from './i18n/I18nProvider.jsx'

export default function App () {
    return (
        <I18nProvider>
            <Toaster
                position="top-right"
                toastOptions={{
                    duration: 4000,
                    style: {
                        background: 'var(--surface-raised)',
                        color: 'var(--ink)',
                        border: '1px solid var(--line)',
                        borderRadius: '12px',
                    },
                }}
            />

            <OverviewPage />
        </I18nProvider>
    )
}
