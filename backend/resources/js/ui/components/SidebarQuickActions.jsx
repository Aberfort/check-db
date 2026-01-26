import React from 'react'

export default function SidebarQuickActions({
    analysisId,
    status,
    onOpenCommand,
    onCopyLink,
}) {
    const disabled = !analysisId

    return (
        <div className="bg-white rounded-xl2 shadow-soft border border-brand-200/60 p-4">
            <div className="text-xs uppercase tracking-wide text-ink/60 font-semibold">Quick actions</div>

            <div className="mt-3 grid grid-cols-1 gap-2">
                <button
                    onClick={onOpenCommand}
                    className="rounded-lg bg-ink text-white px-3 py-2 text-sm font-semibold hover:bg-ink/90"
                    title="Cmd/Ctrl + K"
                >
                    Command (Cmd+K)
                </button>

                <button
                    onClick={onCopyLink}
                    disabled={disabled}
                    className={[
                        'rounded-lg border px-3 py-2 text-sm font-semibold transition',
                        disabled ? 'border-brand-100 text-ink/40 cursor-not-allowed' : 'border-brand-200/60 text-ink/70 hover:bg-brand-50',
                    ].join(' ')}
                >
                    Copy link
                </button>

                <div className="pt-2 text-xs text-ink/50">
                    Status: <span className="font-semibold text-ink/70">{status || '—'}</span>
                </div>
            </div>
        </div>
    )
}
