import React from 'react'

export default function UploadCard({
    file,
    setFile,
    uploading,
    canUpload,
    onUpload,
    status,
    progress,
    totalChecks,
    originalName,
}) {
    return (
        <section className="bg-white rounded-xl2 shadow-soft border border-brand-200/60 p-5">
            <div className="text-xs uppercase tracking-wide text-ink/60 font-semibold">Upload</div>

            <div className="mt-3 space-y-3">
                <input
                    className="block w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-brand-600 file:px-4 file:py-2 file:text-white file:font-semibold hover:file:bg-brand-700"
                    type="file"
                    accept=".db,.sqlite,.sqlite3,.zip,.sql"
                    onChange={(e) => setFile(e.target.files?.[0] || null)}
                />

                <button
                    onClick={onUpload}
                    disabled={!canUpload}
                    className={[
                        'w-full inline-flex items-center justify-center rounded-lg px-4 py-2.5 font-semibold transition',
                        canUpload ? 'bg-ink text-white hover:bg-ink/90' : 'bg-ink/40 text-white cursor-not-allowed',
                    ].join(' ')}
                >
                    {uploading ? 'Завантаження…' : 'Завантажити та перевірити'}
                </button>

                <div className="text-sm text-ink/70">
                    {originalName ? (
                        <>Файл: <span className="font-semibold text-ink">{originalName}</span></>
                    ) : (
                        <span className="text-ink/50">Підтримка: SQLite (.db), .zip, .sql</span>
                    )}
                </div>
            </div>

            <div className="mt-4">
                <div className="h-2.5 w-full rounded-full bg-brand-100 overflow-hidden">
                    <div
                        className={[
                            'h-2.5 rounded-full transition-all',
                            status === 'error' ? 'bg-rose-500' : 'bg-brand',
                        ].join(' ')}
                        style={{ width: `${progress}%` }}
                    />
                </div>

                <div className="mt-2 text-xs text-ink/60 flex items-center justify-between">
                    <div>Прогрес: <span className="font-semibold text-ink">{progress}%</span></div>
                    <div className="text-ink/50">Checks: <span className="font-semibold text-ink">{totalChecks || '—'}</span></div>
                </div>
            </div>
        </section>
    )
}
