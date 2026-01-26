import React, { useEffect } from 'react'

export default function Drawer({
    open,
    onClose,
    title,
    widthClass = 'w-full sm:w-[560px] xl:w-[720px] xl:max-w-[45vw]',
    actions = null, // react node
    children,
}) {
    useEffect(() => {
        if (!open) return
        const onEsc = (e) => {
            if (e.key === 'Escape') onClose?.()
        }
        window.addEventListener('keydown', onEsc)
        return () => window.removeEventListener('keydown', onEsc)
    }, [open, onClose])

    if (!open) return null

    return (
        <div className="fixed inset-0 z-[70]">
            <button
                className="absolute inset-0 bg-black/30 backdrop-blur-sm"
                onClick={onClose}
                aria-label="Close drawer"
            />

            <aside
                className={[
                    'absolute space-y-4 px-4 right-0 top-0 h-full bg-white shadow-soft border-l border-brand-200/60',
                    'flex flex-col',
                    widthClass,
                ].join(' ')}
            >
                <div className="sticky top-0 z-10 bg-white/90 backdrop-blur border-b border-brand-200/60">
                    <div className="p-4 flex items-start gap-3">
                        <div className="min-w-0 flex-1">
                            <div className="text-xs uppercase tracking-wide text-ink/60 font-semibold">Details</div>
                            <div className="mt-1 text-sm font-extrabold text-ink truncate">{title}</div>
                        </div>

                        {actions ? (
                            <div className="hidden sm:flex items-center gap-2">
                                {actions}
                            </div>
                        ) : null}

                        <button
                            onClick={onClose}
                            className="rounded-lg border border-brand-200/60 px-3 py-1.5 text-sm font-semibold text-ink/70 hover:bg-brand-50"
                        >
                            Close
                        </button>
                    </div>

                    {actions ? (
                        <div className="sm:hidden px-4 pb-3 flex flex-wrap gap-2">
                            {actions}
                        </div>
                    ) : null}
                </div>

                <div className="flex-1 overflow-auto">
                    {children}
                </div>
            </aside>
        </div>
    )
}
