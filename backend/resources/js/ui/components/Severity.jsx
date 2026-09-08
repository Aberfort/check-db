import React from 'react'
import { useI18n } from '../i18n/I18nProvider.jsx'

/**
 * Status colour never carries meaning on its own — every severity ships with a
 * glyph and a written label, which is also what keeps the light-mode warning
 * step usable at its documented sub-3:1 contrast.
 */
const TOKENS = {
    critical: { color: 'var(--status-critical)', glyph: '!' },
    warning: { color: 'var(--status-warning)', glyph: '▲' },
    info: { color: 'var(--status-info)', glyph: 'i' },
    good: { color: 'var(--status-good)', glyph: '✓' },
}

export function SeverityDot ({ severity }) {
    const token = TOKENS[severity] ?? TOKENS.info

    return (
        <span
            aria-hidden="true"
            className="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] font-bold text-white"
            style={{ backgroundColor: token.color }}
        >
            {token.glyph}
        </span>
    )
}

export function SeverityLabel ({ severity }) {
    const { t } = useI18n()

    return (
        <span className="inline-flex items-center gap-1.5">
            <SeverityDot severity={severity} />
            <span className="text-sm text-ink-secondary">{t(`severity.${severity}`)}</span>
        </span>
    )
}

export function severityColor (severity) {
    return (TOKENS[severity] ?? TOKENS.info).color
}
