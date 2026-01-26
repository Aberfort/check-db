import React, { useEffect, useMemo, useRef, useState } from 'react'

export default function CommandPalette({
    open,
    onClose,
    items = [], // [{id,title,subtitle,action}]
}) {
    const [q, setQ] = useState('')
    const inputRef = useRef(null)
    const [active, setActive] = useState(0)

    useEffect(() => {
        if (!open) return
        setQ('')
        setActive(0)
        const t = setTimeout(() => inputRef.current?.focus(), 0)
        return () => clearTimeout(t)
    }, [open])

    const filtered = useMemo(() => {
        const needle = q.trim().toLowerCase()
        if (!needle) return items
        return items.filter((x) => {
            const hay = `${x.title} ${x.subtitle || ''}`.toLowerCase()
            return hay.includes(needle)
        })
    }, [items, q])

    function onKeyDown(e) {
        if (e.key === 'Escape') onClose?.()
        if (e.key === 'ArrowDown') setActive((i) => Math.min(filtered.length - 1, i + 1))
        if (e.key === 'ArrowUp') setActive((i) => Math.max(0, i - 1))
        if (e.key === 'Enter') {
            const item = filtered[active]
            if (item?.action) item.action()
            onClose?.()
        }
    }

    if (!open) return null

    return (
        <div className="fixed inset-0 z-[80]">
            <button
                className="absolute inset-0 bg-black/30 backdrop-blur-sm"
                onClick={onClose}
                aria-label="Close"
            />

            <div className="absolute left-1/2 top-[14%] w-[92vw] max-w-[720px] -translate-x-1/2">
                <div className="rounded-2xl bg-white shadow-soft border border-brand-200/60 overflow-hidden">
                    <div className="px-4 py-3 border-b border-brand-200/60">
                        <div className="flex items-center gap-3">
                            <div className="text-xs font-extrabold tracking-wide text-ink/70 uppercase">Command</div>
                            <div className="ml-auto text-xs text-ink/50">
                                Enter — відкрити • Esc — закрити
                            </div>
                        </div>
                    </div>

                    <div className="max-h-[420px] overflow-auto">
                        {!filtered.length ? (
                            <div className="p-4 text-sm text-ink/60">Нічого не знайдено.</div>
                        ) : (
                            <ul className="p-2">
                                {filtered.map((x, idx) => (
                                    <li key={x.id}>
                                        <button
                                            onMouseEnter={() => setActive(idx)}
                                            onClick={() => { x.action?.(); onClose?.() }}
                                            className={[
                                                'w-full text-left rounded-xl px-3 py-2 transition flex items-start gap-3',
                                                idx === active ? 'bg-brand-50 border border-brand-200/60' : 'hover:bg-brand-50/60',
                                            ].join(' ')}
                                        >
                                            <div className="min-w-0 flex-1">
                                                <div className="text-sm font-extrabold text-ink truncate">{x.title}</div>
                                                {x.subtitle ? (
                                                    <div className="text-xs text-ink/60 truncate">{x.subtitle}</div>
                                                ) : null}
                                            </div>

                                            <div className="text-xs text-ink/40 whitespace-nowrap">
                                                ↵
                                            </div>
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    <div className="px-4 py-3 border-t border-brand-200/60 text-xs text-ink/50">
                        Порада: CMD/CTRL+K — відкривати швидко
                    </div>
                </div>
            </div>
        </div>
    )
}
