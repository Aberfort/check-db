import React, { useEffect, useMemo, useState } from 'react'
import toast from 'react-hot-toast'
import AppHeader from '../components/AppHeader.jsx'
import Container from '../components/Container.jsx'
import { getJson, putJson } from '../api/http.js'

const API_GET = '/api/settings/remote-db'
const API_SAVE = '/api/settings/remote-db'

export default function SettingsPage () {
    const [loading, setLoading] = useState(true)
    const [saving, setSaving] = useState(false)

    const [baseUrl, setBaseUrl] = useState('')
    const [authType, setAuthType] = useState('none') // none|bearer|basic
    const [bearer, setBearer] = useState('')
    const [basicUser, setBasicUser] = useState('')
    const [basicPass, setBasicPass] = useState('')

    const canSave = useMemo(() => {
        if (saving) return false
        if (authType === 'bearer') return bearer.trim().length > 0
        if (authType === 'basic') return basicUser.trim().length > 0
        return true
    }, [authType, bearer, basicUser, saving])

    useEffect(() => {
        let stop = false
        ;(async () => {
            try {
                setLoading(true)
                const res = await getJson(API_GET)
                if (stop) return

                const d = res?.data || {}
                setBaseUrl(String(d.base_url || ''))
                setAuthType(String(d.auth_type || 'none'))

                setBearer(String(d.bearer || ''))
                setBasicUser(String(d.basic_user || ''))
                setBasicPass(String(d.basic_pass || ''))
            } catch (e) {
                toast.error(e?.message || 'Не вдалося завантажити налаштування')
            } finally {
                if (!stop) setLoading(false)
            }
        })()
        return () => { stop = true }
    }, [])

    async function onSave () {
        setSaving(true)
        const t = toast.loading('Збереження…')
        try {
            const payload = {
                base_url: baseUrl.trim(),
                auth_type: authType,
                bearer: authType === 'bearer' ? bearer.trim() : '',
                basic_user: authType === 'basic' ? basicUser.trim() : '',
                basic_pass: authType === 'basic' ? basicPass : '',
            }

            await putJson(API_SAVE, payload)
            toast.success('Збережено', { id: t })
        } catch (e) {
            toast.error(e?.message || 'Не вдалося зберегти', { id: t })
        } finally {
            setSaving(false)
        }
    }

    return (
        <div className="min-h-screen">
            <AppHeader/>

            <main className="py-6">
                <Container>
                    <div className="bg-white rounded-xl2 shadow-soft border border-brand-200/60 p-6">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <h2 className="text-lg font-extrabold text-ink">Remote API settings</h2>
                                <p className="mt-1 text-sm text-ink/60">
                                    Тут задаються доступи до сервісу, де зберігаються бази (listing + download).
                                </p>
                            </div>

                            <button
                                disabled={!canSave || loading}
                                onClick={onSave}
                                className={[
                                    'rounded-lg px-4 py-2 text-sm font-semibold transition',
                                    (!canSave || loading)
                                        ? 'bg-brand-100 text-brand-800/40 cursor-not-allowed'
                                        : 'bg-ink text-white hover:bg-ink/90',
                                ].join(' ')}
                            >
                                Save
                            </button>
                        </div>

                        {loading ? (
                            <div className="mt-6 text-sm text-ink/60">Завантаження…</div>
                        ) : (
                            <div className="mt-6 grid grid-cols-1 gap-4">
                                <Field label="Base URL">
                                    <input
                                        value={baseUrl}
                                        onChange={(e) => setBaseUrl(e.target.value)}
                                        placeholder="https://remote.example.com"
                                        className="w-full rounded-lg border border-brand-200/60 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-brand/30"
                                    />
                                    <div className="mt-1 text-xs text-ink/50">
                                        Використовується бекендом як базовий хост для list/download.
                                    </div>
                                </Field>

                                <Field label="Auth type">
                                    <select
                                        value={authType}
                                        onChange={(e) => setAuthType(e.target.value)}
                                        className="w-full rounded-lg border border-brand-200/60 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-brand/30"
                                    >
                                        <option value="none">None</option>
                                        <option value="bearer">Bearer token</option>
                                        <option value="basic">Basic auth</option>
                                    </select>
                                </Field>

                                {authType === 'bearer' ? (
                                    <Field label="Bearer token">
                                        <input
                                            value={bearer}
                                            onChange={(e) => setBearer(e.target.value)}
                                            placeholder="eyJhbGciOi..."
                                            className="w-full rounded-lg border border-brand-200/60 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-brand/30"
                                        />
                                    </Field>
                                ) : null}

                                {authType === 'basic' ? (
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <Field label="Basic user">
                                            <input
                                                value={basicUser}
                                                onChange={(e) => setBasicUser(e.target.value)}
                                                placeholder="username"
                                                className="w-full rounded-lg border border-brand-200/60 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-brand/30"
                                            />
                                        </Field>

                                        <Field label="Basic password">
                                            <input
                                                type="password"
                                                value={basicPass}
                                                onChange={(e) => setBasicPass(e.target.value)}
                                                placeholder="password"
                                                className="w-full rounded-lg border border-brand-200/60 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-brand/30"
                                            />
                                        </Field>
                                    </div>
                                ) : null}

                                <div className="rounded-xl border border-brand-200/60 bg-brand-50/40 p-4">
                                    <div className="text-sm font-extrabold text-ink">Підказка</div>
                                    <div className="mt-1 text-sm text-ink/60">
                                        Після збереження — відкрий <span className="font-semibold">Databases</span> і перевір, що список підтягується.
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </Container>
            </main>
        </div>
    )
}

function Field ({ label, children }) {
    return (
        <div>
            <div className="text-xs uppercase tracking-wide text-ink/60 font-semibold">{label}</div>
            <div className="mt-2">{children}</div>
        </div>
    )
}
