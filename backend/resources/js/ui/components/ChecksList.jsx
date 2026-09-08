import React from 'react'
import { useI18n } from '../i18n/I18nProvider.jsx'
import { SeverityDot } from './Severity.jsx'

export default function ChecksList ({ checks, catalogue, onOpen, activeKey }) {
    const { t } = useI18n()

    const entries = Object.entries(checks ?? {})

    // Failures first, then passes, then the checks this schema never triggered.
    const rank = { failed: 0, passed: 1, skipped: 2 }
    entries.sort(([, a], [, b]) => (rank[a.status] ?? 3) - (rank[b.status] ?? 3) || b.findings - a.findings)

    return (
        <section className="card overflow-hidden">
            <header className="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 className="text-sm font-semibold text-ink">{t('checks.title')}</h2>
            </header>

            <ul className="divide-y divide-line">
                {entries.map(([key, check]) => {
                    const meta = catalogue?.[key]
                    const interactive = check.status === 'failed'
                    const isActive = activeKey === key

                    const Row = interactive ? 'button' : 'div'

                    return (
                        <li key={key}>
                            <Row
                                {...(interactive
                                    ? { type: 'button', onClick: () => onOpen(key, meta?.title ?? key) }
                                    : {})}
                                aria-current={isActive ? 'true' : undefined}
                                className={[
                                    'flex w-full items-start gap-3 px-5 py-4 text-left transition-colors',
                                    interactive ? 'cursor-pointer hover:bg-brand-wash' : '',
                                    isActive ? 'bg-brand-wash' : '',
                                ].join(' ')}
                            >
                                <span className="mt-0.5">
                                    <SeverityDot
                                        severity={check.status === 'failed' ? check.severity : 'good'}
                                    />
                                </span>

                                <span className="min-w-0 flex-1">
                                    <span className="flex flex-wrap items-baseline gap-x-2">
                                        <span className="font-medium text-ink">{meta?.title ?? key}</span>

                                        {check.status === 'skipped' && (
                                            <span className="text-xs text-ink-muted">
                                                · {t('checks.skipped')}
                                            </span>
                                        )}
                                    </span>

                                    <span className="mt-1 block text-sm text-ink-secondary">
                                        {check.status === 'skipped'
                                            ? t('checks.skippedHint')
                                            : meta?.description}
                                    </span>

                                    {check.truncated && (
                                        <span className="mt-1 block text-xs text-ink-muted">
                                            {t('checks.truncated', { limit: check.findings })}
                                        </span>
                                    )}
                                </span>

                                <span className="shrink-0 text-right">
                                    {check.status === 'failed' ? (
                                        <span className="tnum text-lg font-semibold text-ink">
                                            {check.findings}
                                        </span>
                                    ) : (
                                        <span className="text-xs text-ink-muted">
                                            {check.status === 'passed' ? t('checks.passed') : '—'}
                                        </span>
                                    )}
                                </span>
                            </Row>
                        </li>
                    )
                })}
            </ul>
        </section>
    )
}
