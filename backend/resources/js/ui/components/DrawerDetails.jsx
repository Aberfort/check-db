import React from 'react'

export default function DrawerDetails({
    status,
    exportUrl,

    severityFilter,
    setSeverityFilter,

    qText,
    setQText,

    sort,
    setSort,

    perPage,
    setPerPage,

    findingsLoading,
    findings,

    onPrev,
    onNext,
}) {
    return (
        <div className="space-y-4">
            <div className="flex items-center gap-2 flex-wrap">
                <span className="text-xs text-ink/60 font-semibold">Severity:</span>
                <SegButton active={severityFilter === 'all'} onClick={() => setSeverityFilter('all')}>All</SegButton>
                <SegButton active={severityFilter === 'ok'} onClick={() => setSeverityFilter('ok')}>OK</SegButton>
                <SegButton active={severityFilter === 'warning'} onClick={() => setSeverityFilter('warning')}>Warning</SegButton>
                <SegButton active={severityFilter === 'critical'} onClick={() => setSeverityFilter('critical')}>Critical</SegButton>
            </div>

            <div className="grid grid-cols-1 gap-2">
                <input
                    value={qText}
                    onChange={(e) => setQText(e.target.value)}
                    placeholder="Пошук: message / table / column…"
                    className="w-full rounded-lg border border-brand-200/60 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-brand/30"
                />

                <div className="flex gap-2">
                    <select
                        value={sort}
                        onChange={(e) => setSort(e.target.value)}
                        className="flex-1 rounded-lg border border-brand-200/60 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-brand/30"
                    >
                        <option value="new">Newest</option>
                        <option value="old">Oldest</option>
                        <option value="severity">Severity</option>
                    </select>

                    <select
                        value={perPage}
                        onChange={(e) => setPerPage(Number(e.target.value))}
                        className="w-[120px] rounded-lg border border-brand-200/60 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-brand/30"
                    >
                        <option value={25}>25</option>
                        <option value={50}>50</option>
                        <option value={100}>100</option>
                    </select>

                    {exportUrl ? (
                        <a
                            href={exportUrl}
                            className="rounded-lg border border-brand-200/60 px-3 py-2 text-sm font-semibold text-ink/70 hover:bg-brand-50"
                        >
                            Export
                        </a>
                    ) : null}
                </div>
            </div>

            <div>
                {status !== 'success' ? (
                    <div className="text-sm text-ink/60">Деталі доступні після завершення аналізу.</div>
                ) : findingsLoading ? (
                    <div className="text-sm text-ink/60">Завантаження…</div>
                ) : !findings?.data?.length ? (
                    <div className="text-sm text-ink/60">Нічого не знайдено.</div>
                ) : (
                    <>
                        <div className="text-xs text-ink/60 flex items-center justify-between">
                            <div>Items: <span className="font-semibold text-ink">{findings.total}</span></div>
                            <div>Page: <span className="font-semibold text-ink">{findings.current_page}</span> / {findings.last_page}</div>
                        </div>

                        <div className="mt-3 overflow-hidden rounded-lg border border-brand-200/60">
                            <div className="max-h-[65vh] overflow-auto">
                                <table className="w-full text-sm">
                                    <thead className="sticky top-0 bg-brand-50 border-b border-brand-200/60">
                                    <tr className="text-left text-ink/70">
                                        <th className="px-3 py-2 font-semibold">Проблема</th>
                                        <th className="px-3 py-2 font-semibold">Де</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    {findings.data.map((f) => (
                                        <tr key={f.id} className="border-b border-brand-100 last:border-b-0 hover:bg-brand-50/60">
                                            <td className="px-3 py-2">
                                                <div className="flex items-start gap-2">
                                                    <SeverityBadge severity={f.severity} />
                                                    <div>
                                                        <div className="font-semibold text-ink">{f.message}</div>
                                                        {f.meta ? (
                                                            <div className="mt-1 text-xs text-ink/60 break-words">{safeJson(f.meta)}</div>
                                                        ) : null}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-3 py-2 text-ink/70">
                                                <div className="text-xs">
                                                    <div><span className="text-ink/50">table:</span> <span className="font-semibold text-ink">{f.table_name || '—'}</span></div>
                                                    <div className="mt-1"><span className="text-ink/50">col:</span> <span className="font-semibold text-ink">{f.column_name || '—'}</span></div>
                                                    {f.row_ref ? (
                                                        <div className="mt-1 text-ink/60 break-words"><span className="text-ink/50">row:</span> {safeJson(f.row_ref)}</div>
                                                    ) : null}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div className="mt-3 flex items-center justify-between">
                            <button
                                disabled={!findings.prev_page_url}
                                onClick={onPrev}
                                className={[
                                    'rounded-lg px-3 py-2 text-sm font-semibold border transition',
                                    findings.prev_page_url ? 'border-brand-200/60 hover:bg-brand-50 text-ink' : 'border-brand-100 text-ink/40 cursor-not-allowed',
                                ].join(' ')}
                            >
                                Prev
                            </button>

                            <button
                                disabled={!findings.next_page_url}
                                onClick={onNext}
                                className={[
                                    'rounded-lg px-3 py-2 text-sm font-semibold border transition',
                                    findings.next_page_url ? 'border-brand-200/60 hover:bg-brand-50 text-ink' : 'border-brand-100 text-ink/40 cursor-not-allowed',
                                ].join(' ')}
                            >
                                Next
                            </button>
                        </div>
                    </>
                )}
            </div>
        </div>
    )
}

/* atoms */
function SegButton({ active, onClick, children }) {
    return (
        <button
            onClick={onClick}
            className={[
                'px-3 py-1.5 rounded-full text-xs font-bold border transition',
                active ? 'bg-brand text-white border-brand' : 'bg-white text-ink/70 border-brand-200/60 hover:bg-brand-50',
            ].join(' ')}
        >
            {children}
        </button>
    )
}

function SeverityBadge({ severity }) {
    if (severity === 'ok') {
        return <span className="mt-0.5 px-2 py-1 rounded-full text-[11px] font-bold border bg-emerald-50 text-emerald-800 border-emerald-200">OK</span>
    }
    if (severity === 'critical') {
        return <span className="mt-0.5 px-2 py-1 rounded-full text-[11px] font-bold border bg-rose-50 text-rose-800 border-rose-200">CRIT</span>
    }
    return <span className="mt-0.5 px-2 py-1 rounded-full text-[11px] font-bold border bg-amber-50 text-amber-800 border-amber-200">WARN</span>
}

function safeJson(obj) {
    try { return JSON.stringify(obj) } catch { return String(obj) }
}
