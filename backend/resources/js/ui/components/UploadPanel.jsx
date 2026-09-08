import React, { useRef, useState } from 'react'
import { useI18n } from '../i18n/I18nProvider.jsx'

export default function UploadPanel ({ meta, busy, onSubmit, onSample }) {
    const { t } = useI18n()
    const inputRef = useRef(null)

    const [file, setFile] = useState(null)
    const [profile, setProfile] = useState(meta?.default_profile ?? 'standard')

    const profiles = meta?.profiles ?? []

    return (
        <section className="card p-6">
            <h2 className="text-sm font-semibold text-ink">{t('upload.title')}</h2>

            <form
                className="mt-4 flex flex-col gap-4"
                onSubmit={(event) => {
                    event.preventDefault()
                    if (file) onSubmit(file, profile)
                }}
            >
                <div>
                    <button
                        type="button"
                        onClick={() => inputRef.current?.click()}
                        className="w-full rounded-lg border border-dashed border-line-strong px-4 py-6 text-center transition-colors hover:bg-brand-wash"
                    >
                        <span className="block text-sm font-medium text-ink">
                            {file ? file.name : t('upload.choose')}
                        </span>
                        <span className="mt-1 block text-xs text-ink-muted">{t('upload.hint')}</span>
                    </button>

                    <input
                        ref={inputRef}
                        type="file"
                        accept=".db,.sqlite,.sqlite3,.sql,.zip"
                        className="sr-only"
                        onChange={(event) => setFile(event.target.files?.[0] ?? null)}
                    />
                </div>

                {profiles.length > 0 && (
                    <div>
                        <label
                            htmlFor="profile"
                            className="block text-xs font-medium text-ink-secondary"
                        >
                            {t('upload.profile')}
                        </label>

                        <select
                            id="profile"
                            value={profile}
                            onChange={(event) => setProfile(event.target.value)}
                            className="mt-1.5 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink"
                        >
                            {profiles.map((option) => (
                                <option key={option.key} value={option.key}>
                                    {t(`profile.${option.key}`)}
                                </option>
                            ))}
                        </select>
                    </div>
                )}

                <button
                    type="submit"
                    disabled={!file || busy}
                    className="rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-brand-ink transition-opacity disabled:opacity-40"
                >
                    {busy ? t('upload.submitting') : t('upload.submit')}
                </button>
            </form>

            <div className="mt-5 border-t border-line pt-5">
                <button
                    type="button"
                    onClick={() => onSample(profile)}
                    disabled={busy}
                    className="text-sm font-medium text-brand underline underline-offset-4 disabled:opacity-40"
                >
                    {t('upload.sample')}
                </button>

                <p className="mt-1 text-xs text-ink-muted">{t('upload.sampleHint')}</p>
            </div>
        </section>
    )
}
