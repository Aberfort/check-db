import React from 'react'

export default function StickySummaryBar({
    statusBadge,
    progress,
    lastUpdatedAt,
    onOpenIssues,
    onOpenTables,
    onOpenCommand,
}) {
    const updated = lastUpdatedAt
        ? new Date(lastUpdatedAt).toLocaleTimeString()
        : '—'

    return (
        <div className="sticky top-0 z-20 -mx-6 px-6 py-3 bg-white/75 backdrop-blur border-b border-brand-200/60">
            <div className="flex flex-col lg:flex-row lg:items-center gap-3">
                <div className="flex items-center gap-3">
                    <div className={`px-3 py-1.5 rounded-full text-sm font-semibold ${statusBadge.cls}`}>
                        {statusBadge.label}
                        <span className="ml-2 text-xs opacity-70">{progress}%</span>
                    </div>

                    <div className="text-xs text-ink/50">
                        Updated: <span className="font-semibold text-ink/70">{updated}</span>
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-2 lg:ml-auto">

                    <div className="flex items-center gap-2">
                        <button
                            onClick={onOpenIssues}
                            className="rounded-lg border border-brand-200/60 px-3 py-2 text-sm font-semibold text-ink/70 hover:bg-brand-50"
                        >
                            Issues
                        </button>
                        <button
                            onClick={onOpenTables}
                            className="rounded-lg border border-brand-200/60 px-3 py-2 text-sm font-semibold text-ink/70 hover:bg-brand-50"
                        >
                            Tables
                        </button>
                        <button
                            onClick={onOpenCommand}
                            className="rounded-lg bg-ink text-white px-3 py-2 text-sm font-semibold hover:bg-ink/90"
                            title="Cmd/Ctrl + K"
                        >
                            Cmd+K
                        </button>
                    </div>
                </div>
            </div>
        </div>
    )
}
