import React, { useEffect, useState } from 'react'
import { getJson, qs } from '../api/http.js'
import { useI18n } from '../i18n/I18nProvider.jsx'
import { SeverityDot } from './Severity.jsx'

export default function FindingsDrawer ({ analysisId, check, onClose }) {
    const { t, locale } = useI18n()

    const [severity, setSeverity] = useState('all')
    const [search, setSearch] = useState('')
    const [page, setPage] = useState(1)
    const [result, setResult] = useState(null)
    const [loading, setLoading] = useState(false)

    const open = Boolean(check)

    useEffect(() => {
        setSeverity('all')
        setSearch('')
        setPage(1)
    }, [check?.key])

    useEffect(() => {
        if (!open) return

        const handler = (event) => {
            if (event.key === 'Escape') onClose()
        }

        document.addEventListener('keydown', handler)

        return () => document.removeEventListener('keydown', handler)
    }, [open, onClose])

    useEffect(() => {
        if (!open || !analysisId) return

        let cancelled = false
        setLoading(true)

        const timer = setTimeout(async () => {
            try {
                const query = qs({
                    check_key: check.key,
                    severity: severity === 'all' ? '' : severity,
                    q: search,
                    page,
                    per_page: 25,
                    lang: locale,
                })

                const response = await getJson(`/api/analyses/${analysisId}/findings${query}`)

                if (!cancelled) setResult(response)
            } finally {
                if (!cancelled) setLoading(false)
            }
        }, search ? 250 : 0)

        return () => {
            cancelled = true
            clearTimeout(timer)
        }
    }, [open, analysisId, check?.key, severity, search, page, locale])

    if (!open) return null

    const exportUrl = `/api/analyses/${analysisId}/findings/export${qs({
        check_key: check.key,
        severity: severity === 'all' ? '' : severity,
        q: search,
        lang: locale,
    })}`

    const items = result?.data ?? []
    const meta = result?.meta

    return (
        <div className="fixed inset-0 z-40 flex justify-end">
            <button
                type="button"
                aria-label={t('findings.close')}
                onClick={onClose}
                className="absolute inset-0 bg-black/30"
            />

            <aside
                role="dialog"
                aria-modal="true"
                aria-label={check.title}
                className="relative flex h-full w-full max-w-2xl flex-col bg-surface shadow-xl"
            >
                <header className="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                    <div className="min-w-0">
                        <h2 className="truncate font-semibold text-ink">{check.title}</h2>
                        {meta && (
                            <p className="mt-0.5 text-xs text-ink-muted">
                                {t('checks.findings', { count: meta.total })}
                            </p>
                        )}
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg border border-line px-3 py-1.5 text-sm text-ink-secondary hover:bg-brand-wash"
                    >
                        {t('findings.close')}
                    </button>
                </header>

                <div className="flex flex-wrap items-center gap-2 border-b border-line px-5 py-3">
                    <select
                        aria-label={t('severity.all')}
                        value={severity}
                        onChange={(event) => {
                            setSeverity(event.target.value)
                            setPage(1)
                        }}
                        className="rounded-lg border border-line bg-surface px-2.5 py-1.5 text-sm text-ink"
                    >
                        <option value="all">{t('severity.all')}</option>
                        <option value="critical">{t('severity.critical')}</option>
                        <option value="warning">{t('severity.warning')}</option>
                        <option value="info">{t('severity.info')}</option>
                    </select>

                    <input
                        type="search"
                        value={search}
                        placeholder={t('findings.search')}
                        onChange={(event) => {
                            setSearch(event.target.value)
                            setPage(1)
                        }}
                        className="min-w-0 flex-1 rounded-lg border border-line bg-surface px-3 py-1.5 text-sm text-ink"
                    />

                    <a
                        href={exportUrl}
                        className="rounded-lg border border-line px-3 py-1.5 text-sm font-medium text-ink-secondary hover:bg-brand-wash"
                    >
                        {t('findings.export')}
                    </a>
                </div>

                <div className={`flex-1 overflow-auto ${loading ? 'opacity-60' : ''}`}>
                    {items.length === 0 && !loading ? (
                        <p className="px-5 py-10 text-center text-sm text-ink-muted">
                            {t('findings.empty')}
                        </p>
                    ) : (
                        <ul className="divide-y divide-line">
                            {items.map((finding) => (
                                <li key={finding.id} className="flex gap-3 px-5 py-3.5">
                                    <span className="mt-0.5">
                                        <SeverityDot severity={finding.severity} />
                                    </span>

                                    <div className="min-w-0">
                                        <p className="text-sm text-ink">{finding.message}</p>

                                        {(finding.table || finding.row_ref) && (
                                            <p className="mt-1 font-mono text-xs text-ink-muted">
                                                {[
                                                    finding.table,
                                                    finding.column,
                                                    finding.row_ref
                                                        ? Object.entries(finding.row_ref)
                                                            .map(([k, v]) => `${k}=${v}`)
                                                            .join(' ')
                                                        : null,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ')}
                                            </p>
                                        )}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                {meta && meta.last_page > 1 && (
                    <footer className="flex items-center justify-between border-t border-line px-5 py-3">
                        <button
                            type="button"
                            disabled={meta.current_page <= 1}
                            onClick={() => setPage((p) => Math.max(1, p - 1))}
                            className="rounded-lg border border-line px-3 py-1.5 text-sm text-ink-secondary disabled:opacity-40"
                        >
                            {t('findings.prev')}
                        </button>

                        <span className="tnum text-xs text-ink-muted">
                            {t('findings.page', { page: meta.current_page, last: meta.last_page })}
                        </span>

                        <button
                            type="button"
                            disabled={meta.current_page >= meta.last_page}
                            onClick={() => setPage((p) => p + 1)}
                            className="rounded-lg border border-line px-3 py-1.5 text-sm text-ink-secondary disabled:opacity-40"
                        >
                            {t('findings.next')}
                        </button>
                    </footer>
                )}
            </aside>
        </div>
    )
}
