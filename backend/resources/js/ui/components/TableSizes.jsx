import React, { useMemo, useState } from 'react'
import { useI18n } from '../i18n/I18nProvider.jsx'

const MAX_BARS = 12

/**
 * Row counts per table: magnitude across named categories, so bars on one
 * shared baseline. One series means one colour — shading each bar by its own
 * length would encode the same thing twice.
 */
export default function TableSizes ({ schema }) {
    const { t } = useI18n()
    const [view, setView] = useState('chart')

    const rows = useMemo(() => {
        const counts = schema?.row_counts ?? {}

        return Object.entries(counts)
            .map(([table, count]) => ({ table, count: Number(count) || 0 }))
            .sort((a, b) => b.count - a.count)
    }, [schema])

    if (rows.length === 0) return null

    const shown = view === 'chart' ? rows.slice(0, MAX_BARS) : rows
    const max = Math.max(...rows.map((r) => r.count), 1)
    const format = new Intl.NumberFormat()

    return (
        <section className="card overflow-hidden">
            <header className="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                <div>
                    <h2 className="text-sm font-semibold text-ink">{t('schema.title')}</h2>
                    <p className="mt-0.5 text-xs text-ink-muted">
                        {t('schema.tables', {
                            count: schema?.total_tables ?? rows.length,
                            rows: format.format(schema?.total_rows ?? 0),
                        })}
                    </p>
                </div>

                <div
                    role="group"
                    className="flex overflow-hidden rounded-lg border border-line text-xs"
                >
                    {['chart', 'table'].map((option) => (
                        <button
                            key={option}
                            type="button"
                            onClick={() => setView(option)}
                            aria-pressed={view === option}
                            className={[
                                'px-3 py-1.5 font-medium transition-colors',
                                view === option
                                    ? 'bg-brand text-brand-ink'
                                    : 'text-ink-secondary hover:bg-brand-wash',
                            ].join(' ')}
                        >
                            {t(option === 'chart' ? 'schema.viewChart' : 'schema.viewTable')}
                        </button>
                    ))}
                </div>
            </header>

            {view === 'chart' ? (
                <ul className="flex flex-col gap-3 px-5 py-5">
                    {shown.map(({ table, count }) => (
                        <li key={table} className="grid grid-cols-[minmax(0,9rem)_1fr] items-center gap-3">
                            <span className="truncate text-sm text-ink-secondary" title={table}>
                                {table}
                            </span>

                            <span className="flex items-center gap-2">
                                <span
                                    aria-hidden="true"
                                    className="h-2.5 rounded-r-[4px]"
                                    style={{
                                        width: `${Math.max((count / max) * 100, count > 0 ? 1.5 : 0)}%`,
                                        backgroundColor: 'var(--series-1)',
                                    }}
                                />
                                <span className="tnum text-sm text-ink">{format.format(count)}</span>
                            </span>
                        </li>
                    ))}
                </ul>
            ) : (
                <div className="max-h-96 overflow-auto">
                    <table className="w-full text-sm">
                        <thead className="sticky top-0 bg-surface">
                            <tr className="border-b border-line text-left text-ink-secondary">
                                <th scope="col" className="px-5 py-2.5 font-medium">{t('schema.table')}</th>
                                <th scope="col" className="px-5 py-2.5 text-right font-medium">
                                    {t('schema.rows')}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {shown.map(({ table, count }) => (
                                <tr key={table} className="border-b border-line last:border-0">
                                    <td className="px-5 py-2.5 text-ink">{table}</td>
                                    <td className="tnum px-5 py-2.5 text-right text-ink-secondary">
                                        {format.format(count)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </section>
    )
}
