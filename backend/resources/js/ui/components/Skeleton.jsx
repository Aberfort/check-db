import React from 'react'

export default function Skeleton({ className = '' }) {
    return (
        <div
            className={[
                'animate-pulse rounded-lg bg-brand-100/70',
                className || 'h-4 w-full',
            ].join(' ')}
            aria-hidden="true"
        />
    )
}
