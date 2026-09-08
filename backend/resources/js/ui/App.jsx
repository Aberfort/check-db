import React from 'react'
import { Routes, Route, Navigate } from 'react-router-dom'
import { Toaster } from 'react-hot-toast'

import OverviewPage from './pages/OverviewPage.jsx'

export default function App() {
    return (
        <>
            <Toaster
                position="top-right"
                toastOptions={{
                    duration: 3500,
                    style: {
                        background: '#ffffff',
                        color: '#0f1a17',
                        border: '1px solid rgba(63,88,80,0.18)',
                        boxShadow: '0 10px 30px rgba(15,26,23,0.10)',
                        borderRadius: '14px',
                    },
                }}
            />

            <Routes>
                <Route path="/" element={<OverviewPage />} />
                <Route path="*" element={<Navigate to="/" replace />} />
            </Routes>
        </>
    )
}
