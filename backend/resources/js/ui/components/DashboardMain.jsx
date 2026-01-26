import React, { useMemo } from 'react'
import Skeleton from './Skeleton.jsx'

export default function DashboardMain({
    status,
    summary,
    tableCount,
    totalRows,
    totalIssues,
    errorPercentUi,
    okCount,
    warnCount,
    critCount,
    errorCount,
    issuesByType,
    httpCheckKey,
    totalHttpFindings,
    httpDistLoading,
    httpCodeCounts,
    onOpenCheck,
    onOpenHttpCode,
    activeCheckKey,
}) {
    const issueCards = useMemo(() => {
        const entries = Object.entries(issuesByType || {}).map(([key, v]) => ({
            key,
            title: v?.title || key,
            count: Number(v?.count || 0),
        }))
        entries.sort((a, b) => b.count - a.count)
        return entries
    }, [issuesByType])

    const tables = summary?.tables?.names || []
    const rowCounts = summary?.tables?.row_counts || {}

    const health = summary?.health || { score: null, label: null }

    const isReady = status === 'success'

    return (
        <>
            {/* Overview stats */}
            <section className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <StatCard title="Таблиць" value={isReady ? tableCount : <Skeleton className="h-8 w-20" />} />
                <StatCard title="Рядків" value={isReady ? totalRows : <Skeleton className="h-8 w-28" />} />
                <StatCard title="Помилок" value={isReady ? totalIssues : <Skeleton className="h-8 w-16" />} />
                <StatCard title="% помилок" value={isReady ? `${errorPercentUi}%` : <Skeleton className="h-8 w-16" />} />
                <HealthCard health={health} />
            </section>

            {/* Row 1 */}
            <section className="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <div className="lg:col-span-5">
                    <HealthGauge score={health?.score ?? 0} label={health?.label} />
                </div>

                <div className="lg:col-span-7 bg-white rounded-xl2 shadow-soft border border-brand-200/60 p-5">
                    <div className="flex items-center justify-between">
                        <h2 className="text-sm font-extrabold tracking-wide text-ink uppercase">HTTP codes</h2>
                        <div className="text-xs text-ink/60">
                            {isReady ? `${totalHttpFindings} rows` : '—'}
                        </div>
                    </div>

                    {status !== 'success' ? (
                        <div className="mt-3">
                            <Skeleton className="h-4 w-60" />
                            <div className="mt-2 space-y-2">
                                <Skeleton className="h-12 w-full" />
                                <Skeleton className="h-12 w-full" />
                                <Skeleton className="h-12 w-full" />
                            </div>
                        </div>
                    ) : !issuesByType?.[httpCheckKey] ? (
                        <div className="mt-3 text-sm text-ink/60">
                            Чек <span className="font-semibold">{httpCheckKey}</span> не увімкнено в профілі.
                        </div>
                    ) : httpDistLoading ? (
                        <div className="mt-3 text-sm text-ink/60">Будую розподіл…</div>
                    ) : !httpCodeCounts?.codes?.length ? (
                        <div className="mt-3 text-sm text-ink/60">Немає кодів для відображення.</div>
                    ) : (
                        <>
                            <div className="mt-4 space-y-2">
                                {httpCodeCounts.codes.slice(0, 6).map((x) => (
                                    <button
                                        key={x.code}
                                        onClick={() => onOpenHttpCode(x.code)}
                                        className="w-full text-left rounded-lg border border-brand-200/60 px-3 py-2 hover:bg-brand-50/60 transition"
                                        title="Клік → деталізація"
                                    >
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <CodePill code={x.code} severity={x.severity} />
                                                <span className="text-xs text-ink/60">{String(x.severity || '').toUpperCase()}</span>
                                            </div>
                                            <div className="text-sm font-extrabold text-ink">{x.count}</div>
                                        </div>

                                        <div className="mt-2 h-1.5 rounded-full bg-brand-100 overflow-hidden">
                                            <div
                                                className={barToneClass(x.severity)}
                                                style={{ width: `${pct(x.count, httpCodeCounts.codes[0].count)}%` }}
                                            />
                                        </div>
                                    </button>
                                ))}
                            </div>

                            <div className="mt-3 flex items-center justify-between text-xs text-ink/50">
                                <button
                                    onClick={() => onOpenCheck(httpCheckKey, issuesByType?.[httpCheckKey]?.title || 'HTTP errors')}
                                    className="font-semibold text-brand hover:underline"
                                >
                                    View details
                                </button>

                                {httpCodeCounts.truncated ? (
                                    <span title={`Підтягнуто ${httpCodeCounts.fetched} rows (cap)`}>partial</span>
                                ) : (
                                    <span>full</span>
                                )}
                            </div>
                        </>
                    )}
                </div>
            </section>

            {/* Row 2 */}
            <section className="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <div className="lg:col-span-6 bg-white rounded-xl2 shadow-soft border border-brand-200/60 p-5">
                    <div className="flex items-center justify-between">
                        <h2 className="text-sm font-extrabold tracking-wide text-ink uppercase">Issues distribution</h2>
                        <div className="text-xs text-ink/60">{status === 'success' ? 'by severity' : '—'}</div>
                    </div>

                    <div className="mt-4 grid grid-cols-12 gap-4 items-center">
                        <div className="col-span-5">
                            <Donut
                                size={130}
                                thickness={16}
                                parts={[
                                    { value: okCount, className: 'text-emerald-500' },
                                    { value: warnCount, className: 'text-amber-500' },
                                    { value: critCount, className: 'text-rose-500' },
                                ]}
                                centerTop={status === 'success' ? `${okCount + warnCount + critCount}` : '—'}
                                centerBottom="total"
                            />
                        </div>

                        <div className="col-span-7 space-y-3">
                            <DistRow label="OK" value={okCount} tone="ok" total={okCount + warnCount + critCount} />
                            <DistRow label="Warnings" value={warnCount} tone="warning" total={okCount + warnCount + critCount} />
                            <DistRow label="Critical" value={critCount} tone="critical" total={okCount + warnCount + critCount} />
                        </div>
                    </div>
                </div>

                <div className="lg:col-span-6 bg-white rounded-xl2 shadow-soft border border-brand-200/60 p-5">
                    <div className="flex items-center justify-between">
                        <h2 className="text-sm font-extrabold tracking-wide text-ink uppercase">Error distribution</h2>
                        <div className="text-xs text-ink/60">OK vs Errors</div>
                    </div>

                    <div className="mt-4 flex items-center gap-5">
                        <Donut
                            size={130}
                            thickness={16}
                            parts={[
                                { value: okCount, className: 'text-emerald-500' },
                                { value: errorCount, className: 'text-rose-500' },
                            ]}
                            centerTop={status === 'success' ? `${pctOk(okCount, errorCount)}` : '—'}
                            centerBottom="% OK"
                        />

                        <div className="flex-1 space-y-2">
                            <LegendRow dot="bg-emerald-500" label="Rows OK" value={okCount} />
                            <LegendRow dot="bg-rose-500" label="Rows with issues" value={errorCount} />
                            <div className="pt-2 text-xs text-ink/50">
                                * Базується на severity.
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Chips */}
            <section className="flex flex-wrap gap-2">
                <Chip tone="ok" label={`OK: ${okCount ?? 0}`} />
                <Chip tone="warning" label={`Warning: ${warnCount ?? 0}`} />
                <Chip tone="critical" label={`Critical: ${critCount ?? 0}`} />
            </section>

            {/* Types of issues */}
            <section id="issues-section" className="bg-white rounded-xl2 shadow-soft border border-brand-200/60 p-5">
                <div className="flex items-center justify-between">
                    <h2 className="text-sm font-extrabold tracking-wide text-ink uppercase">Типи проблем</h2>
                    <div className="text-xs text-ink/60">Клікни на карточку → drawer</div>
                </div>

                {!issueCards.length ? (
                    <div className="mt-3 text-sm text-ink/60">Немає даних.</div>
                ) : (
                    <div className="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {issueCards.map((c) => {
                            const isActive = activeCheckKey === c.key
                            const isOk = c.count === 0

                            return (
                                <button
                                    key={c.key}
                                    onClick={() => onOpenCheck(c.key, c.title)}
                                    className={[
                                        'text-left rounded-xl border p-4 transition shadow-sm',
                                        isActive ? 'border-brand bg-brand-50/70' : 'border-brand-200/60 bg-white hover:bg-brand-50/50',
                                    ].join(' ')}
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <div className="text-sm font-extrabold text-ink">{c.title}</div>
                                            <div className="mt-1 text-xs text-ink/60">key: {c.key}</div>
                                        </div>

                                        <div className="flex flex-col items-end">
                                            <div className={[
                                                'px-2.5 py-1 rounded-full text-xs font-bold border',
                                                isOk ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                                    : 'bg-brand-100 text-brand-800 border-brand-200',
                                            ].join(' ')}>
                                                {c.count}
                                            </div>
                                            <div className="mt-2 text-[11px] text-ink/50">{isOk ? 'ok' : 'issues'}</div>
                                        </div>
                                    </div>

                                    <div className="mt-3 h-1.5 rounded-full bg-brand-100 overflow-hidden">
                                        <div className="h-1.5 bg-brand/70 rounded-full" style={{ width: `${barPercent(c.count)}%` }} />
                                    </div>
                                </button>
                            )
                        })}
                    </div>
                )}
            </section>

            {/* Tables */}
            <section id="tables-section" className="bg-white rounded-xl2 shadow-soft border border-brand-200/60 p-5">
                <div className="flex items-center justify-between">
                    <h2 className="text-sm font-extrabold tracking-wide text-ink uppercase">Таблиці</h2>
                    <div className="text-xs text-ink/60">{tables.length ? `${tables.length} items` : '—'}</div>
                </div>

                {!tables.length ? (
                    <div className="mt-3 text-sm text-ink/60">Немає даних.</div>
                ) : (
                    <div className="mt-3 overflow-hidden rounded-lg border border-brand-200/60">
                        <div className="max-h-[360px] overflow-auto">
                            <table className="w-full text-sm">
                                <thead className="sticky top-0 bg-brand-50 border-b border-brand-200/60">
                                <tr className="text-left text-ink/70">
                                    <th className="px-4 py-3 font-semibold">Таблиця</th>
                                    <th className="px-4 py-3 font-semibold text-right">Рядків</th>
                                </tr>
                                </thead>
                                <tbody>
                                {tables.map((t) => (
                                    <tr key={t} className="border-b border-brand-100 last:border-b-0 hover:bg-brand-50/60">
                                        <td className="px-4 py-3 font-medium text-ink">{t}</td>
                                        <td className="px-4 py-3 text-right text-ink/70">{rowCounts?.[t] ?? 0}</td>
                                    </tr>
                                ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </section>

            {/* Top issues */}
            <section className="bg-white rounded-xl2 shadow-soft border border-brand-200/60 p-5">
                <TopIssuesTable
                    items={issueCards}
                    onOpen={(x) => onOpenCheck(x.key, x.title)}
                />
            </section>
        </>
    )
}

/* ---------- UI bits ---------- */

function StatCard({ title, value }) {
    return (
        <div className="bg-white rounded-xl2 shadow-soft border border-brand-200/60 p-5">
            <div className="text-xs uppercase tracking-wide text-ink/60 font-semibold">{title}</div>
            <div className="mt-2 text-2xl font-extrabold text-ink">{value}</div>
            <div className="mt-3 h-1.5 rounded-full bg-brand-100 overflow-hidden">
                <div className="h-1.5 w-2/3 bg-brand/70 rounded-full" />
            </div>
        </div>
    )
}

function Chip({ tone, label }) {
    const cls =
        tone === 'critical'
            ? 'bg-rose-50 text-rose-800 border-rose-200'
            : tone === 'warning'
                ? 'bg-amber-50 text-amber-800 border-amber-200'
                : 'bg-emerald-50 text-emerald-800 border-emerald-200'

    return <div className={`px-3 py-1.5 rounded-full text-xs font-bold border ${cls}`}>{label}</div>
}

function HealthCard({ health }) {
    const score = health?.score
    const label = health?.label

    const badge =
        label === 'OK'
            ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
            : label === 'Warning'
                ? 'bg-amber-50 text-amber-800 border-amber-200'
                : 'bg-rose-50 text-rose-800 border-rose-200'

    return (
        <div className="bg-white rounded-xl2 shadow-soft border border-brand-200/60 p-5">
            <div className="text-xs uppercase tracking-wide text-ink/60 font-semibold">Health</div>
            <div className="mt-2 flex items-end justify-between gap-3">
                <div className="text-2xl font-extrabold text-ink">{typeof score === 'number' ? score : '—'}</div>
                <div className={`px-2.5 py-1 rounded-full text-xs font-bold border ${badge}`}>{label || '—'}</div>
            </div>
            <div className="mt-3 h-2 rounded-full bg-brand-100 overflow-hidden">
                <div className="h-2 bg-brand/70 rounded-full" style={{ width: `${clamp(score ?? 0, 0, 100)}%` }} />
            </div>
        </div>
    )
}

function Donut({ size = 120, thickness = 14, parts, centerTop, centerBottom }) {
    const r = (size - thickness) / 2
    const c = size / 2
    const circumference = 2 * Math.PI * r
    const total = parts.reduce((s, p) => s + (Number(p.value) || 0), 0) || 1

    let offset = 0
    const rings = parts.map((p, idx) => {
        const v = Math.max(0, Number(p.value) || 0)
        const len = (v / total) * circumference
        const dash = `${len} ${circumference - len}`
        const dashOffset = -offset
        offset += len
        return (
            <circle
                key={idx}
                cx={c}
                cy={c}
                r={r}
                fill="transparent"
                stroke="currentColor"
                strokeWidth={thickness}
                strokeDasharray={dash}
                strokeDashoffset={dashOffset}
                className={p.className}
                strokeLinecap="butt"
            />
        )
    })

    return (
        <div className="relative inline-flex items-center justify-center">
            <svg width={size} height={size} className="block">
                <circle cx={c} cy={c} r={r} fill="transparent" stroke="currentColor" strokeWidth={thickness} className="text-brand-100" />
                <g transform={`rotate(-90 ${c} ${c})`}>{rings}</g>
            </svg>

            <div className="absolute text-center">
                <div className="text-2xl font-extrabold text-ink leading-none">{centerTop}</div>
                <div className="mt-1 text-xs text-ink/60">{centerBottom}</div>
            </div>
        </div>
    )
}

function DistRow({ label, value, tone, total }) {
    const pctVal = total > 0 ? Math.round((value / total) * 100) : 0
    const cls = tone === 'ok' ? 'bg-emerald-500' : tone === 'warning' ? 'bg-amber-500' : 'bg-rose-500'
    return (
        <div>
            <div className="flex items-center justify-between text-xs text-ink/60">
                <span className="font-semibold">{label}</span>
                <span><span className="font-semibold text-ink">{value}</span> · {pctVal}%</span>
            </div>
            <div className="mt-1.5 h-2 rounded-full bg-brand-100 overflow-hidden">
                <div className={`h-2 ${cls} rounded-full`} style={{ width: `${clamp(pctVal, 0, 100)}%` }} />
            </div>
        </div>
    )
}

function LegendRow({ dot, label, value }) {
    return (
        <div className="flex items-center justify-between text-sm">
            <div className="flex items-center gap-2">
                <span className={`h-2.5 w-2.5 rounded-full ${dot}`} />
                <span className="text-ink/70">{label}</span>
            </div>
            <div className="font-extrabold text-ink">{value}</div>
        </div>
    )
}

function CodePill({ code, severity }) {
    const cls = severity === 'ok'
        ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
        : severity === 'warning'
            ? 'bg-amber-50 text-amber-800 border-amber-200'
            : 'bg-rose-50 text-rose-800 border-rose-200'

    return <span className={`px-2.5 py-1 rounded-full text-xs font-extrabold border ${cls}`}>{code}</span>
}

function barToneClass(sev) {
    return ['h-1.5 rounded-full', sev === 'ok' ? 'bg-emerald-500' : sev === 'warning' ? 'bg-amber-500' : 'bg-rose-500'].join(' ')
}

function pct(value, max) {
    if (!max) return 0
    return Math.max(8, Math.min(100, Math.round((value / max) * 100)))
}

function pctOk(ok, err) {
    const total = Math.max(1, (Number(ok) || 0) + (Number(err) || 0))
    const v = Math.round(((Number(ok) || 0) / total) * 100)
    return clamp(v, 0, 100)
}

function barPercent(count) {
    if (!count) return 12
    if (count >= 50) return 100
    return Math.max(18, Math.min(100, Math.round((count / 50) * 100)))
}

function clamp(v, min, max) {
    return Math.max(min, Math.min(max, Number(v) || 0))
}

function HealthGauge({ score = 0, label = '—' }) {
    const s = clamp(score, 0, 100)
    const status =
        s >= 90 ? { pill: 'bg-emerald-50 text-emerald-800 border-emerald-200' } :
            s >= 70 ? { pill: 'bg-amber-50 text-amber-800 border-amber-200' } :
                { pill: 'bg-rose-50 text-rose-800 border-rose-200' }

    const size = 220
    const stroke = 18
    const r = (size - stroke) / 2
    const c = size / 2
    const circumference = 2 * Math.PI * r
    const half = circumference / 2

    const progressLen = (s / 100) * half
    const dash = `${progressLen} ${half - progressLen}`

    return (
        <div className="bg-white rounded-xl2 shadow-soft border border-brand-200/60 p-5">
            <div className="flex items-center justify-between">
                <h2 className="text-sm font-extrabold tracking-wide text-ink uppercase">Health Score</h2>
                <span className={`px-2.5 py-1 rounded-full text-xs font-bold border ${status.pill}`}>{label || '—'}</span>
            </div>

            <div className="mt-4 flex items-center justify-center">
                <div className="relative" style={{ width: size, height: size / 2 + 24 }}>
                    <svg width={size} height={size / 2} className="block">
                        <g transform={`rotate(180 ${c} ${c})`}>
                            <circle
                                cx={c}
                                cy={c}
                                r={r}
                                fill="transparent"
                                stroke="currentColor"
                                strokeWidth={stroke}
                                strokeDasharray={`${half} ${half}`}
                                className="text-brand-100"
                                strokeLinecap="butt"
                            />
                            <circle
                                cx={c}
                                cy={c}
                                r={r}
                                fill="transparent"
                                stroke="currentColor"
                                strokeWidth={stroke}
                                strokeDasharray={dash}
                                strokeDashoffset={0}
                                className={s >= 90 ? 'text-emerald-500' : s >= 70 ? 'text-amber-500' : 'text-rose-500'}
                                strokeLinecap="butt"
                            />
                        </g>
                    </svg>

                    <div className="absolute inset-0 flex flex-col items-center justify-end pb-1">
                        <div className="text-5xl font-extrabold text-ink leading-none">{s}</div>
                        <div className="mt-2 text-xs text-ink/60">Health reflects overall DB quality</div>
                    </div>
                </div>
            </div>
        </div>
    )
}

function TopIssuesTable({ items, onOpen }) {
    const top = (items || []).slice(0, 8)

    return (
        <>
            <div className="flex items-center justify-between">
                <h2 className="text-sm font-extrabold tracking-wide text-ink uppercase">Top issues</h2>
            </div>

            {!top.length ? (
                <div className="mt-3 text-sm text-ink/60">Немає даних.</div>
            ) : (
                <div className="mt-4 overflow-hidden rounded-lg border border-brand-200/60">
                    <table className="w-full text-sm">
                        <thead className="bg-brand-50 border-b border-brand-200/60">
                        <tr className="text-left text-ink/70">
                            <th className="px-4 py-3 font-semibold">Issue</th>
                            <th className="px-4 py-3 font-semibold text-right">Count</th>
                            <th className="px-4 py-3 font-semibold text-right">Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        {top.map((x) => {
                            const status = x.count === 0 ? 'ok' : 'warning'
                            return (
                                <tr
                                    key={x.key}
                                    className="border-b border-brand-100 last:border-b-0 hover:bg-brand-50/60 cursor-pointer"
                                    onClick={() => onOpen?.(x)}
                                    title="Клік → деталізація"
                                >
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <IssueIcon status={status} />
                                            <div>
                                                <div className="font-semibold text-ink">{x.title}</div>
                                                <div className="text-xs text-ink/50">key: {x.key}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-right font-extrabold text-ink">{x.count}</td>
                                    <td className="px-4 py-3 text-right">
                      <span className={`px-2.5 py-1 rounded-full text-xs font-bold border ${statusPill(status)}`}>
                        {status.toUpperCase()}
                      </span>
                                    </td>
                                </tr>
                            )
                        })}
                        </tbody>
                    </table>
                </div>
            )}
        </>
    )
}

function IssueIcon({ status }) {
    const cls =
        status === 'ok' ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
            : status === 'warning' ? 'bg-amber-50 text-amber-700 border-amber-200'
                : 'bg-rose-50 text-rose-700 border-rose-200'

    const glyph = status === 'ok' ? '✓' : status === 'warning' ? '!' : '✕'

    return (
        <span className={`inline-flex h-8 w-8 items-center justify-center rounded-full border text-sm font-extrabold ${cls}`}>
      {glyph}
    </span>
    )
}

function statusPill(status) {
    if (status === 'ok') return 'bg-emerald-50 text-emerald-800 border-emerald-200'
    if (status === 'warning') return 'bg-amber-50 text-amber-800 border-amber-200'
    return 'bg-rose-50 text-rose-800 border-rose-200'
}
