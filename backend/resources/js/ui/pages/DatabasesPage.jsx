import React, { useEffect, useMemo, useState } from 'react'
import toast from 'react-hot-toast'
import { Link } from 'react-router-dom'
import { getJson, postForm, qs } from '../api/http.js'

import Drawer from '../components/Drawer.jsx'

import Container from '../components/Container.jsx'

export default function DatabasesPage () {
    const [items, setItems] = useState([])
    const [loading, setLoading] = useState(false)

    const [remoteDisabled, setRemoteDisabled] = useState(false)
    const [remoteMsg, setRemoteMsg] = useState('')

    const [q, setQ] = useState('')
    const [sort, setSort] = useState('name') // name|new
    const [selected, setSelected] = useState(null) // db item
    const [drawerOpen, setDrawerOpen] = useState(false)

    const [running, setRunning] = useState(false)

    useEffect(() => {
        let stop = false

        async function load () {
            setLoading(true)
            setRemoteDisabled(false)
            setRemoteMsg('')
            try {
                const res = await getJson('/api/remote-dbs')
                if (stop) return
                setItems(Array.isArray(res?.data) ? res.data : [])
            } catch (e) {
                if (stop) return

                if (e?.status === 422 && e?.code === 'REMOTE_API_DISABLED') {
                    setRemoteDisabled(true)
                    setRemoteMsg(e?.message || 'Remote API вимкнено в налаштуваннях.')
                    setItems([])
                    return
                }

                toast.error(e?.message || 'Не вдалося завантажити список баз')
            } finally {
                if (!stop) setLoading(false)
            }
        }

        load()
        return () => { stop = true }
    }, [])

    const filtered = useMemo(() => {
        const needle = q.trim().toLowerCase()
        let out = [...(items || [])]

        if (needle) {
            out = out.filter((x) => {
                const hay = `${x?.name || ''} ${x?.id || ''} ${x?.path || ''}`.toLowerCase()
                return hay.includes(needle)
            })
        }

        if (sort === 'name') {
            out.sort((a, b) => String(a?.name || a?.id || '').localeCompare(String(b?.name || b?.id || '')))
        } else if (sort === 'new') {
            // якщо API дає updated_at/created_at — сортуємо ними, інакше лишаємо як є
            out.sort((a, b) => {
                const ta = Date.parse(a?.updated_at || a?.created_at || '') || 0
                const tb = Date.parse(b?.updated_at || b?.created_at || '') || 0
                return tb - ta
            })
        }

        return out
    }, [items, q, sort])

    function openDb (x) {
        setSelected(x)
        setDrawerOpen(true)
    }

    async function runAnalysisForSelected () {
        if (!selected) return
        setRunning(true)
        const t = toast.loading('Стартую перевірку…')

        try {
            // бек: POST /api/analyses/remote-by-id  { id: "..." }
            const fd = new FormData()
            fd.append('id', String(selected?.id || selected?.name || ''))

            const res = await postForm('/api/analyses/remote-by-id', fd)
            const id = res?.data?.id

            toast.success('Аналіз запущено. Переходжу на Overview…', { id: t })

            // перекидаємо на головну з analysis id в query
            if (id) {
                window.location.href = `/${qs({ analysis: id }).replace('?', '?')}`
            } else {
                // якщо бек не повернув id — просто оновимо сторінку
                window.location.href = '/'
            }
        } catch (e) {
            toast.error(e?.message || 'Не вдалося запустити аналіз', { id: t })
        } finally {
            setRunning(false)
        }
    }

    return (
        <div className="min-h-screen">
            <header className="border-b border-brand-200/70 bg-brand-50/60 backdrop-blur">
                <div className="mx-auto max-w-[1600px] px-6 py-5 flex items-center justify-between gap-4">
                    <div>
                        <div className="text-sm text-brand-800/70">Remote databases</div>
                        <h1 className="text-2xl font-extrabold tracking-tight text-ink">
                            check-db <span className="text-brand">databases</span>
                        </h1>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            to="/"
                            className="rounded-lg border border-brand-200/60 px-3 py-2 text-sm font-semibold text-ink/70 hover:bg-brand-50"
                        >
                            Overview
                        </Link>
                        <Link
                            to="/settings"
                            className="rounded-lg bg-ink text-white px-3 py-2 text-sm font-semibold hover:bg-ink/90"
                        >
                            Settings
                        </Link>
                    </div>
                </div>
            </header>

            <main className="py-6">
                <Container className="space-y-6">
                    {remoteDisabled ? (
                        <div className="bg-white rounded-xl2 shadow-soft border border-brand-200/60 p-6">
                            <div className="text-sm font-extrabold text-ink">Remote API вимкнено</div>
                            <div className="mt-2 text-sm text-ink/60">{remoteMsg}</div>

                            <div className="mt-4 flex flex-wrap gap-2">
                                <Link
                                    to="/settings"
                                    className="rounded-lg bg-ink text-white px-3 py-2 text-sm font-semibold hover:bg-ink/90"
                                >
                                    Відкрити Settings
                                </Link>

                                <button
                                    onClick={() => window.location.reload()}
                                    className="rounded-lg border border-brand-200/60 px-3 py-2 text-sm font-semibold text-ink/70 hover:bg-brand-50"
                                >
                                    Retry
                                </button>
                            </div>
                        </div>
                    ) : (
                        <>
                            <section className="bg-white rounded-xl2 shadow-soft border border-brand-200/60 p-5">
                                <div className="flex flex-col lg:flex-row lg:items-center gap-3">
                                    <div className="min-w-0 flex-1">
                                        <div className="text-sm font-extrabold text-ink">Список баз</div>
                                        <div className="text-xs text-ink/50">
                                            {loading ? 'Завантаження…' : `${filtered.length} items`}
                                        </div>
                                    </div>

                                    <div className="flex flex-col sm:flex-row gap-2 sm:items-center">
                                        <input
                                            value={q}
                                            onChange={(e) => setQ(e.target.value)}
                                            placeholder="Пошук по name/id…"
                                            className="w-full sm:w-[320px] rounded-lg border border-brand-200/60 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-brand/30"
                                        />

                                        <select
                                            value={sort}
                                            onChange={(e) => setSort(e.target.value)}
                                            className="w-full sm:w-[160px] rounded-lg border border-brand-200/60 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-brand/30"
                                        >
                                            <option value="name">Sort: Name</option>
                                            <option value="new">Sort: Newest</option>
                                        </select>

                                        <button
                                            onClick={() => window.location.reload()}
                                            className="rounded-lg border border-brand-200/60 px-3 py-2 text-sm font-semibold text-ink/70 hover:bg-brand-50"
                                        >
                                            Refresh
                                        </button>
                                    </div>
                                </div>

                                {!loading && !filtered.length ? (
                                    <div className="mt-4 text-sm text-ink/60">Немає доступних баз.</div>
                                ) : (
                                    <div className="mt-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                                        {(loading ? Array.from({ length: 6 }).map((_, i) => ({ __sk: i })) : filtered).map((x) => {
                                            if (x.__sk !== undefined) {
                                                return (
                                                    <div
                                                        key={`sk-${x.__sk}`}
                                                        className="rounded-xl border border-brand-200/60 bg-white p-4 animate-pulse"
                                                    >
                                                        <div className="h-4 w-2/3 bg-brand-100 rounded"/>
                                                        <div className="mt-3 h-3 w-1/2 bg-brand-100 rounded"/>
                                                        <div className="mt-4 h-8 w-full bg-brand-100 rounded"/>
                                                    </div>
                                                )
                                            }

                                            const name = x?.name || x?.id || '—'
                                            const id = x?.id ? String(x.id) : ''
                                            const updated = x?.updated_at || x?.created_at || null

                                            return (
                                                <button
                                                    key={id || name}
                                                    onClick={() => openDb(x)}
                                                    className="text-left rounded-xl border border-brand-200/60 bg-white p-4 hover:bg-brand-50/50 transition shadow-sm"
                                                    title="Клік → деталі"
                                                >
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div className="min-w-0">
                                                            <div className="text-sm font-extrabold text-ink truncate">{name}</div>
                                                            {id ?
                                                                <div className="mt-1 text-xs text-ink/50 truncate">id: {id}</div> : null}
                                                            {updated ? (
                                                                <div className="mt-1 text-xs text-ink/50 truncate">updated: {String(updated)}</div>
                                                            ) : null}
                                                        </div>

                                                        <span className="px-2.5 py-1 rounded-full text-xs font-bold border bg-brand-100 text-brand-800 border-brand-200">
                                                        Select
                                                    </span>
                                                    </div>

                                                    <div className="mt-3 h-1.5 rounded-full bg-brand-100 overflow-hidden">
                                                        <div className="h-1.5 w-2/3 bg-brand/70 rounded-full"/>
                                                    </div>
                                                </button>
                                            )
                                        })}
                                    </div>
                                )}
                            </section>

                            <section className="text-xs text-ink/50">
                                Порада: якщо бачиш <span className="font-semibold">REMOTE_API_DISABLED</span> — ввімкни Remote API в Settings.
                            </section>
                        </>
                    )}

                    <Drawer
                        open={drawerOpen}
                        onClose={() => {
                            setDrawerOpen(false)
                            setSelected(null)
                        }}
                        title={selected ? `Database: ${selected?.name || selected?.id || '—'}` : 'Database'}
                        widthClass="w-full sm:w-[660px] lg:max-w-[50vw]"
                        actions={
                            <>
                                <button
                                    onClick={() => {
                                        setDrawerOpen(false)
                                        setSelected(null)
                                    }}
                                    className="rounded-lg border border-brand-200/60 px-3 py-2 text-sm font-semibold text-ink/70 hover:bg-brand-50"
                                >
                                    Close
                                </button>

                                <button
                                    disabled={!selected || running}
                                    onClick={runAnalysisForSelected}
                                    className={[
                                        'rounded-lg px-3 py-2 text-sm font-semibold transition',
                                        running
                                            ? 'bg-brand-100 text-brand-800 border border-brand-200 cursor-wait'
                                            : 'bg-ink text-white hover:bg-ink/90',
                                    ].join(' ')}
                                >
                                    {running ? 'Starting…' : 'Run analysis'}
                                </button>
                            </>
                        }
                    >
                        {!selected ? (
                            <div className="text-sm text-ink/60">Нічого не вибрано.</div>
                        ) : (
                            <div className="space-y-3">
                                <InfoRow label="Name" value={String(selected?.name || '—')}/>
                                <InfoRow label="ID" value={String(selected?.id || '—')}/>
                                {selected?.path ?
                                    <InfoRow label="Path" value={String(selected?.path)}/> : null}
                                {selected?.size ?
                                    <InfoRow label="Size" value={String(selected?.size)}/> : null}
                                {selected?.updated_at ?
                                    <InfoRow label="Updated" value={String(selected?.updated_at)}/> : null}
                                {selected?.created_at ?
                                    <InfoRow label="Created" value={String(selected?.created_at)}/> : null}

                                {selected?.meta ? (
                                    <div className="rounded-lg border border-brand-200/60 p-3 bg-brand-50/40">
                                        <div className="text-xs uppercase tracking-wide text-ink/60 font-semibold">Meta</div>
                                        <pre className="mt-2 text-xs text-ink/70 whitespace-pre-wrap break-words">
                                        {safeJsonPretty(selected.meta)}
                                    </pre>
                                    </div>
                                ) : null}

                                <div className="pt-2 text-xs text-ink/50">
                                    Натисни <span className="font-semibold">Run analysis</span>, щоб завантажити БД з remote і запустити перевірку.
                                </div>
                            </div>
                        )}
                    </Drawer>
                </Container>
            </main>
        </div>
    )
}

/* ---- tiny atoms ---- */
function InfoRow ({ label, value }) {
    return (
        <div className="flex items-start justify-between gap-3">
            <div className="text-xs text-ink/50">{label}</div>
            <div className="text-sm font-semibold text-ink text-right break-words">{value}</div>
        </div>
    )
}

function safeJsonPretty (obj) {
    try { return JSON.stringify(obj, null, 2) } catch { return String(obj) }
}
