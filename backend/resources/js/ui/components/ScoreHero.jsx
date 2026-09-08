import React from 'react'
import { useI18n } from '../i18n/I18nProvider.jsx'
import { SeverityDot } from './Severity.jsx'

const GRADE_COLOR = {
    good: 'var(--status-good)',
    fair: 'var(--status-warning)',
    poor: 'var(--status-critical)',
}

/**
 * The one number the report leads with. It is a figure, not a chart — a gauge
 * for a single value adds chrome without adding information.
 */
export default function ScoreHero ({ health, severity }) {
    const { t } = useI18n()

    const score = health?.score ?? 0
    const grade = health?.grade ?? 'poor'

    const counts = [
        ['critical', severity?.critical ?? 0],
        ['warning', severity?.warning ?? 0],
        ['info', severity?.info ?? 0],
    ]

    return (
        <section className="card p-6 sm:p-8">
            <div className="flex flex-col gap-8 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-xs font-semibold uppercase tracking-wider text-ink-muted">
                        {t('score.title')}
                    </h2>

                    <div className="mt-3 flex items-baseline gap-3">
                        <span
                            className="text-6xl font-semibold leading-none"
                            style={{ color: GRADE_COLOR[grade] }}
                        >
                            {score}
                        </span>

                        <span className="text-lg font-medium text-ink-secondary">
                            {t(`grade.${grade}`)}
                        </span>
                    </div>

                    <p className="mt-3 max-w-sm text-sm text-ink-secondary">
                        {t('score.checks', {
                            passed: health?.checks_passed ?? 0,
                            run: health?.checks_run ?? 0,
                        })}
                    </p>

                    <p className="mt-1 max-w-sm text-xs text-ink-muted">{t('score.caption')}</p>
                </div>

                <dl className="grid grid-cols-3 gap-4 sm:gap-6">
                    {counts.map(([key, value]) => (
                        <div key={key} className="min-w-20">
                            <dt className="flex items-center gap-1.5 text-xs text-ink-secondary">
                                <SeverityDot severity={key} />
                                {t(`severity.${key}`)}
                            </dt>
                            <dd className="mt-1.5 text-3xl font-semibold text-ink">{value}</dd>
                        </div>
                    ))}
                </dl>
            </div>
        </section>
    )
}
