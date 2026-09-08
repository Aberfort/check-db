import React, { useCallback, useEffect, useState } from 'react'
import toast from 'react-hot-toast'

import AppHeader from '../components/AppHeader.jsx'
import ChecksList from '../components/ChecksList.jsx'
import FindingsDrawer from '../components/FindingsDrawer.jsx'
import ScoreHero from '../components/ScoreHero.jsx'
import TableSizes from '../components/TableSizes.jsx'
import UploadPanel from '../components/UploadPanel.jsx'

import useAnalysis from '../hooks/useAnalysis.js'
import { getJson, postForm, qs } from '../api/http.js'
import { useI18n } from '../i18n/I18nProvider.jsx'

export default function OverviewPage () {
    const { t, locale } = useI18n()

    const [meta, setMeta] = useState(null)
    const [analysisId, setAnalysisId] = useState(() => {
        const fromUrl = new URLSearchParams(window.location.search).get('analysis')

        return fromUrl ? Number(fromUrl) : null
    })
    const [busy, setBusy] = useState(false)
    const [activeCheck, setActiveCheck] = useState(null)

    const { analysis, error } = useAnalysis(analysisId)

    useEffect(() => {
        getJson(`/api/meta${qs({ lang: locale })}`)
            .then(({ data }) => setMeta(data))
            .catch(() => setMeta(null))
    }, [locale])

    useEffect(() => {
        const url = new URL(window.location.href)

        if (analysisId) {
            url.searchParams.set('analysis', String(analysisId))
        } else {
            url.searchParams.delete('analysis')
        }

        window.history.replaceState({}, '', url)
    }, [analysisId])

    const start = useCallback(async (request) => {
        setBusy(true)
        setActiveCheck(null)

        try {
            const { data } = await request()
            setAnalysisId(data.id)
        } catch (e) {
            toast.error(e?.message || t('error.generic'))
        } finally {
            setBusy(false)
        }
    }, [t])

    const onSubmit = (file, profile) => start(() => {
        const form = new FormData()
        form.append('file', file)
        form.append('profile', profile)

        return postForm('/api/analyses', form)
    })

    const onSample = (profile) => start(() => {
        const form = new FormData()
        form.append('profile', profile)

        return postForm('/api/analyses/sample', form)
    })

    const status = analysis?.status ?? 'idle'
    const summary = analysis?.summary

    return (
        <div className="min-h-screen">
            <AppHeader />

            <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6">
                <div className="grid gap-6 lg:grid-cols-[20rem_minmax(0,1fr)]">
                    <div className="flex flex-col gap-6">
                        <UploadPanel
                            meta={meta}
                            busy={busy || status === 'queued' || status === 'processing'}
                            onSubmit={onSubmit}
                            onSample={onSample}
                        />

                        {analysisId && (
                            <button
                                type="button"
                                onClick={() => {
                                    setAnalysisId(null)
                                    setActiveCheck(null)
                                }}
                                className="text-sm font-medium text-ink-secondary underline underline-offset-4"
                            >
                                {t('action.reset')}
                            </button>
                        )}
                    </div>

                    <div className="flex flex-col gap-6">
                        {!analysisId && <Introduction meta={meta} />}

                        {analysisId && (status === 'queued' || status === 'processing') && (
                            <Running progress={analysis?.progress ?? 0} />
                        )}

                        {status === 'error' && (
                            <Failure message={analysis?.error_message} />
                        )}

                        {error && !analysis && <Failure message={error.message} />}

                        {status === 'success' && summary && (
                            <>
                                <ScoreHero health={summary.health} severity={summary.severity} />

                                <ChecksList
                                    checks={summary.checks}
                                    catalogue={meta?.checks}
                                    activeKey={activeCheck?.key}
                                    onOpen={(key, title) => setActiveCheck({ key, title })}
                                />

                                <TableSizes schema={summary.schema} />
                            </>
                        )}
                    </div>
                </div>
            </main>

            <FindingsDrawer
                analysisId={analysisId}
                check={activeCheck}
                onClose={() => setActiveCheck(null)}
            />
        </div>
    )
}

function Introduction ({ meta }) {
    const { t } = useI18n()

    const checks = Object.values(meta?.checks ?? {})

    return (
        <>
            <section className="card p-6 sm:p-8">
                <h1 className="text-2xl font-semibold tracking-tight text-ink">{t('app.tagline')}</h1>
                <p className="mt-3 max-w-2xl text-ink-secondary">{t('app.intro')}</p>
            </section>

            {checks.length > 0 && (
                <section className="card p-6">
                    <h2 className="text-sm font-semibold text-ink">{t('catalogue.title')}</h2>

                    <ul className="mt-4 grid gap-4 sm:grid-cols-2">
                        {checks.map((check) => (
                            <li key={check.key}>
                                <p className="text-sm font-medium text-ink">{check.title}</p>
                                <p className="mt-0.5 text-sm text-ink-secondary">{check.description}</p>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </>
    )
}

function Running ({ progress }) {
    const { t } = useI18n()

    return (
        <section className="card p-6 sm:p-8">
            <h2 className="text-sm font-semibold text-ink">{t('running.title')}</h2>

            <div
                role="progressbar"
                aria-valuenow={progress}
                aria-valuemin={0}
                aria-valuemax={100}
                className="mt-4 h-2 overflow-hidden rounded-full"
                style={{ backgroundColor: 'var(--series-1-wash)' }}
            >
                <div
                    className="h-full rounded-full transition-[width] duration-500"
                    style={{ width: `${progress}%`, backgroundColor: 'var(--series-1)' }}
                />
            </div>

            <p className="mt-3 text-sm text-ink-muted">{t('running.hint')}</p>
        </section>
    )
}

function Failure ({ message }) {
    const { t } = useI18n()

    return (
        <section className="card p-6">
            <h2 className="text-sm font-semibold" style={{ color: 'var(--status-critical)' }}>
                {t('error.title')}
            </h2>
            <p className="mt-2 text-sm text-ink-secondary">{message || t('error.generic')}</p>
        </section>
    )
}
