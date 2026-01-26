import React from 'react'

export default function Container({ className = '', children }) {
    return (
        <div className={`mx-auto w-full max-w-[1600px] px-6 ${className}`}>
            {children}
        </div>
    )
}
