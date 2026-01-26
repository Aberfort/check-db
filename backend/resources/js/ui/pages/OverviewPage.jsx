import React, { useEffect, useMemo, useState } from 'react'
import toast from 'react-hot-toast'
import { postForm, getJson, qs } from '../api/http.js'

import AppHeader from '../components/AppHeader.jsx'

import Container from '../components/Container.jsx'

import SidebarLayout from '../components/SidebarLayout.jsx'
import UploadCard from '../components/UploadCard.jsx'
import DashboardMain from '../components/DashboardMain.jsx'
import Drawer from '../components/Drawer.jsx'
import DrawerDetails from '../components/DrawerDetails.jsx'

import useQueryState from '../hooks/useQueryState.js'
import useHotkeys from '../hooks/useHotkeys.js'

import CommandPalette from '../components/CommandPalette.jsx'
import StickySummaryBar from '../components/StickySummaryBar.jsx'
import SidebarQuickActions from '../components/SidebarQuickActions.jsx'

import { copyToClipboard } from '../utils/copyToClipboard.js'

const HTTP_CHECK_KEY = 'http_errors'
const MAX_HTTP_FINDINGS = 5000
const HTTP_FETCH_PER_PAGE = 500

export default function OverviewPage () {
    const [file, setFile] = useState(null)
    const [uploading, setUploading] = useState(false)

    const [analysisId, setAnalysisId] = useState(null)
    const [analysis, setAnalysis] = useState(null)

    const [lastUpdatedAt, setLastUpdatedAt] = useState(null)

    // details drawer
    const [activeCheck, setActiveCheck] = useState(null) // { key, title }
    const [severityFilter, setSeverityFilter] = useState('all') // all|ok|warning|critical
    const [qText, setQText] = useState('')
    const [sort, setSort] = useState('new') // new|old|severity
    const [perPage, setPerPage] = useState(25)

    const [findings, setFindings] = useState(null)
    const [findingsLoading, setFindingsLoading] = useState(false)

    // http distribution (aggregated on front)
    const [httpDistLoading, setHttpDistLoading] = useState(false)
    const [httpCodeCounts, setHttpCodeCounts] = useState(null) // { total, codes:[{code,count,severity}], truncated, fetched }

    const [cmdkOpen, setCmdkOpen] = useState(false)

    const qsState = useQueryState({
        analysis: '',
        check: '',
        sev: 'all',
        q: '',
        sort: 'new',
        per: '25',
    })

    useHotkeys({
        'meta+k': () => setCmdkOpen(true),
        'ctrl+k': () => setCmdkOpen(true),
    })

    const canUpload = !!file && !uploading
    const status = analysis?.status ?? 'idle'
    const progress = analysis?.progress ?? 0
    const summary = analysis?.summary

    const issuesByType = summary?.issues_by_type || {}
    const severity = summary?.severity || { ok: 0, warning: 0, critical: 0 }

    const okCount = Number(severity.ok || 0)
    const warnCount = Number(severity.warning || 0)
    const critCount = Number(severity.critical || 0)
    const errorCount = warnCount + critCount

    const totalChecks = summary?.overview?.total_checks ?? 0
    const totalIssues = summary?.overview?.total_issues ?? '—'
    const tableCount = summary?.overview?.total_tables ?? '—'
    const totalRows = summary?.overview?.total_rows ?? '—'

    const errorPercentUi = useMemo(() => {
        const rows = Number(summary?.overview?.total_rows ?? 0)
        const issues = Number(summary?.overview?.total_issues ?? 0)
        if (!rows) return 0
        return Math.min(100, Math.round((issues / rows) * 100))
    }, [summary?.overview?.total_rows, summary?.overview?.total_issues])

    const totalHttpFindings = Number(issuesByType?.[HTTP_CHECK_KEY]?.count || 0)

    const statusBadge = useMemo(() => {
        switch (status) {
            case 'queued':
                return {
                    label: 'У черзі',
                    cls: 'bg-white text-ink border border-brand-200'
                }
            case 'processing':
                return {
                    label: 'Обробка',
                    cls: 'bg-brand-100 text-brand-800 border border-brand-200'
                }
            case 'success':
                return {
                    label: 'Готово',
                    cls: 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                }
            case 'error':
                return {
                    label: 'Помилка',
                    cls: 'bg-rose-50 text-rose-800 border border-rose-200'
                }
            default:
                return {
                    label: 'Очікування',
                    cls: 'bg-white text-ink border border-brand-200'
                }
        }
    }, [status])

    const exportUrl = analysisId && activeCheck?.key
        ? `/api/analyses/${analysisId}/findings/export${qs({
            check_key: activeCheck.key,
            severity: severityFilter,
            q: qText,
            sort,
        })}`
        : null

    // boot: pull analysis from URL
    useEffect(() => {
        const a = qsState.get('analysis')
        if (a && !analysisId) setAnalysisId(Number(a))
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [])

    // keep analysis in URL
    useEffect(() => {
        if (!analysisId) return
        qsState.setQuery({ analysis: analysisId }, { replace: true })
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [analysisId])

    async function onUpload () {
        if (!file) return

        setUploading(true)
        setAnalysis(null)
        setAnalysisId(null)

        // reset drilldowns
        setActiveCheck(null)
        setFindings(null)
        setHttpCodeCounts(null)

        const t = toast.loading('Завантаження файлу…')

        try {
            const fd = new FormData()
            fd.append('file', file)

            const res = await postForm('/api/analyses', fd)
            const id = res?.data?.id

            setAnalysisId(id)
            qsState.setQuery({
                analysis: id,
                check: null,
                sev: null,
                q: null,
                sort: null,
                per: null
            })
            toast.success('Файл прийнято. Стартує аналіз.', { id: t })
        } catch (e) {
            toast.error(e?.message || 'Помилка завантаження', { id: t })
        } finally {
            setUploading(false)
        }
    }

    // polling analysis status
    useEffect(() => {
        if (!analysisId) return

        let timer = null
        let stopped = false
        let lastStatus = null

        const tick = async () => {
            try {
                const res = await getJson(`/api/analyses/${analysisId}`)
                if (stopped) return

                const data = res?.data
                setAnalysis(data)
                setLastUpdatedAt(Date.now())

                if (data?.status !== lastStatus) {
                    lastStatus = data?.status
                    if (data?.status === 'success') toast.success('Аналіз завершено.')
                    if (data?.status === 'error') toast.error(data?.error_message || 'Помилка аналізу')
                }

                if (data?.status === 'success' || data?.status === 'error') {
                    clearInterval(timer)
                }
            } catch (e) {
                if (!stopped) toast.error(e?.message || 'Помилка статусу')
            }
        }

        tick()
        timer = setInterval(tick, 1000)

        return () => {
            stopped = true
            if (timer) clearInterval(timer)
        }
    }, [analysisId])

    // Restore drawer from URL after success
    useEffect(() => {
        if (!analysisId) return
        if (status !== 'success') return

        const check = qsState.get('check')
        if (!check) return

        const title = issuesByType?.[check]?.title || check
        setActiveCheck({ key: check, title })

        const sev = qsState.get('sev') || 'all'
        const q = qsState.get('q') || ''
        const sortQ = qsState.get('sort') || 'new'
        const per = Number(qsState.get('per') || 25)

        setSeverityFilter(sev)
        setQText(q)
        setSort(sortQ)
        setPerPage(Number.isFinite(per) ? per : 25)

        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [analysisId, status])

    // load findings when drawer filters change
    useEffect(() => {
        if (!analysisId || !activeCheck?.key) return
        loadFindings(1)
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [analysisId, activeCheck?.key, severityFilter, qText, sort, perPage])

    // Persist drawer state to URL (debounced)
    useEffect(() => {
        if (!activeCheck?.key) return

        const t = setTimeout(() => {
            qsState.setQuery({
                check: activeCheck.key,
                sev: severityFilter,
                q: qText,
                sort,
                per: perPage,
            }, { replace: true })
        }, 250)

        return () => clearTimeout(t)
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [activeCheck?.key, severityFilter, qText, sort, perPage])

    async function loadFindings (page = 1) {
        if (!analysisId || !activeCheck?.key) return

        setFindingsLoading(true)
        try {
            const url = `/api/analyses/${analysisId}/findings${qs({
                check_key: activeCheck.key,
                severity: severityFilter,
                q: qText,
                sort,
                per_page: perPage,
                page,
            })}`

            const res = await getJson(url)
            setFindings(res?.data)
        } catch (e) {
            toast.error(e?.message || 'Не вдалося завантажити деталізацію')
        } finally {
            setFindingsLoading(false)
        }
    }

    // Build HTTP distribution once analysis is successful
    useEffect(() => {
        if (!analysisId) return
        if (status !== 'success') return
        if (!issuesByType?.[HTTP_CHECK_KEY]) return

        if (totalHttpFindings <= 0) {
            setHttpCodeCounts({
                total: 0,
                codes: [],
                truncated: false,
                fetched: 0
            })
            return
        }

        void buildHttpCodesDistribution()
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [analysisId, status])

    async function buildHttpCodesDistribution () {
        setHttpDistLoading(true)
        try {
            const counts = new Map() // code -> count
            let fetched = 0
            let page = 1
            let lastPage = 1

            while (fetched < MAX_HTTP_FINDINGS && page <= lastPage) {
                const url = `/api/analyses/${analysisId}/findings${qs({
                    check_key: HTTP_CHECK_KEY,
                    severity: 'all',
                    sort: 'new',
                    per_page: HTTP_FETCH_PER_PAGE,
                    page,
                })}`

                const res = await getJson(url)
                const data = res?.data
                const items = data?.data || []
                lastPage = Number(data?.last_page || 1)

                for (const f of items) {
                    const code = extractHttpCode(f)
                    if (!code) continue
                    counts.set(code, (counts.get(code) || 0) + 1)
                }

                fetched += items.length
                if (!items.length) break
                page += 1
                if (page > 200) break
            }

            const codes = Array.from(counts.entries())
                .map(([code, count]) => ({
                    code,
                    count,
                    severity: httpSeverityForCode(code)
                }))
                .sort((a, b) => b.count - a.count)

            setHttpCodeCounts({
                total: totalHttpFindings,
                codes,
                truncated: fetched >= MAX_HTTP_FINDINGS,
                fetched,
            })
        } catch (e) {
            toast.error(e?.message || 'Не вдалося побудувати розподіл HTTP кодів')
            setHttpCodeCounts(null)
        } finally {
            setHttpDistLoading(false)
        }
    }

    function openCheck (key, title) {
        setActiveCheck({ key, title })
        setSeverityFilter('all')
        setQText('')
        setSort('new')
        setPerPage(25)

        qsState.setQuery({
            check: key,
            sev: 'all',
            q: '',
            sort: 'new',
            per: 25,
        })
    }

    function openHttpCode (code) {
        const title = issuesByType?.[HTTP_CHECK_KEY]?.title || 'HTTP errors'
        setActiveCheck({ key: HTTP_CHECK_KEY, title })
        setSeverityFilter('all')
        setQText(String(code))
        setSort('new')
        setPerPage(25)

        qsState.setQuery({
            check: HTTP_CHECK_KEY,
            sev: 'all',
            q: String(code),
            sort: 'new',
            per: 25,
        })
    }

    const showDetails = !!activeCheck?.key

    const commandItems = useMemo(() => {
        const base = Object.entries(issuesByType || {}).map(([k, v]) => ({
            id: `check:${k}`,
            title: v?.title || k,
            subtitle: `Open check (key: ${k})`,
            action: () => openCheck(k, v?.title || k),
        }))

        base.unshift(
            {
                id: 'go:issues',
                title: 'Go to Issues section',
                subtitle: 'Scroll to issues',
                action: () => document.getElementById('issues-section')?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                }),
            },
            {
                id: 'go:tables',
                title: 'Go to Tables section',
                subtitle: 'Scroll to tables',
                action: () => document.getElementById('tables-section')?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                }),
            },
        )

        return base
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [issuesByType])

    return (
        <div className="min-h-screen">
            <AppHeader/>

            <main className="py-6">
                <Container className="space-y-4">
                    <StickySummaryBar
                        statusBadge={statusBadge}
                        progress={progress}
                        lastUpdatedAt={lastUpdatedAt}
                        tableCount={tableCount}
                        totalRows={totalRows}
                        totalIssues={totalIssues}
                        errorPercentUi={errorPercentUi}
                        onOpenIssues={() => document.getElementById('issues-section')?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        })}
                        onOpenTables={() => document.getElementById('tables-section')?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        })}
                        onOpenCommand={() => setCmdkOpen(true)}
                    />

                    <SidebarLayout
                        sidebar={
                            <div className="space-y-6">
                                <UploadCard
                                    file={file}
                                    setFile={setFile}
                                    uploading={uploading}
                                    canUpload={canUpload}
                                    onUpload={onUpload}
                                    status={status}
                                    progress={progress}
                                    totalChecks={totalChecks}
                                    originalName={analysis?.original_name}
                                />

                                <SidebarQuickActions
                                    analysisId={analysisId}
                                    status={status}
                                    onOpenCommand={() => setCmdkOpen(true)}
                                    onCopyLink={async () => {
                                        const ok = await copyToClipboard(window.location.href)
                                        if (ok) toast.success('Link copied!')
                                        else toast.error('Не вдалося скопіювати лінк')
                                    }}
                                />
                            </div>
                        }
                        content={
                            <DashboardMain
                                status={status}
                                summary={summary}
                                tableCount={tableCount}
                                totalRows={totalRows}
                                totalIssues={totalIssues}
                                errorPercentUi={errorPercentUi}
                                okCount={okCount}
                                warnCount={warnCount}
                                critCount={critCount}
                                errorCount={errorCount}
                                issuesByType={issuesByType}
                                httpCheckKey={HTTP_CHECK_KEY}
                                totalHttpFindings={totalHttpFindings}
                                httpDistLoading={httpDistLoading}
                                httpCodeCounts={httpCodeCounts}
                                onOpenCheck={openCheck}
                                onOpenHttpCode={openHttpCode}
                                activeCheckKey={activeCheck?.key}
                            />
                        }
                    />

                    <Drawer
                        open={showDetails}
                        onClose={() => {
                            setActiveCheck(null)
                            setFindings(null)
                            qsState.setQuery({
                                check: null,
                                sev: null,
                                q: null,
                                sort: null,
                                per: null
                            })
                        }}
                        title={activeCheck?.title ? `${activeCheck.title} (key: ${activeCheck.key})` : '—'}
                        widthClass="w-full sm:w-[660px] lg:max-w-[50vw]"
                        actions={
                            <>
                                <button
                                    onClick={() => {
                                        setSeverityFilter('all')
                                        setQText('')
                                        setSort('new')
                                        setPerPage(25)
                                    }}
                                    className="rounded-lg border border-brand-200/60 px-3 py-2 text-sm font-semibold text-ink/70 hover:bg-brand-50"
                                >
                                    Reset
                                </button>

                                <button
                                    onClick={async () => {
                                        const ok = await copyToClipboard(window.location.href)
                                        if (ok) toast.success('Link copied!')
                                        else toast.error('Не вдалося скопіювати лінк')
                                    }}
                                    className="rounded-lg border border-brand-200/60 px-3 py-2 text-sm font-semibold text-ink/70 hover:bg-brand-50"
                                >
                                    Copy link
                                </button>

                                {exportUrl ? (
                                    <a
                                        href={exportUrl}
                                        className="rounded-lg bg-ink text-white px-3 py-2 text-sm font-semibold hover:bg-ink/90"
                                    >
                                        Export
                                    </a>
                                ) : null}
                            </>
                        }
                    >
                        <DrawerDetails
                            status={status}
                            exportUrl={exportUrl}
                            severityFilter={severityFilter}
                            setSeverityFilter={setSeverityFilter}
                            qText={qText}
                            setQText={setQText}
                            sort={sort}
                            setSort={setSort}
                            perPage={perPage}
                            setPerPage={setPerPage}
                            findingsLoading={findingsLoading}
                            findings={findings}
                            onPrev={() => loadFindings(Math.max(1, (findings?.current_page || 1) - 1))}
                            onNext={() => loadFindings((findings?.current_page || 1) + 1)}
                        />
                    </Drawer>

                    <CommandPalette
                        open={cmdkOpen}
                        onClose={() => setCmdkOpen(false)}
                        items={commandItems}
                    />

                    <footer className="mt-6 text-xs text-ink/50">
                        Primary color: <span className="font-semibold text-brand">#3f5850</span>
                    </footer>
                </Container>
            </main>
        </div>
    )
}

/* helpers */
function extractHttpCode (finding) {
    const metaCode = finding?.meta?.http_code
    if (Number.isFinite(metaCode)) return Number(metaCode)

    const m = String(finding?.message || '').match(/\b(\d{3})\b/)
    if (!m) return null
    return Number(m[1])
}

function httpSeverityForCode (code) {
    const c = Number(code)
    if (!Number.isFinite(c)) return 'critical'
    if (c >= 200 && c <= 299) return 'ok'
    if (c >= 300 && c <= 399) return 'warning'
    return 'critical'
}
