import React from 'react'
import { useI18n } from '../i18n/I18nProvider.jsx'
import { locales } from '../i18n/dictionaries.js'
import useTheme from '../hooks/useTheme.js'

export default function AppHeader () {
    const { t, locale, setLocale } = useI18n()
    const { theme, toggle } = useTheme()

    return (
        <header className="border-b border-line bg-surface">
            <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <div className="min-w-0">
                    <p className="text-lg font-semibold tracking-tight text-ink">{t('app.name')}</p>
                    <p className="truncate text-xs text-ink-muted">{t('app.tagline')}</p>
                </div>

                <div className="flex items-center gap-2">
                    <div
                        role="group"
                        aria-label={t('nav.language')}
                        className="flex overflow-hidden rounded-lg border border-line text-xs"
                    >
                        {locales.map((code) => (
                            <button
                                key={code}
                                type="button"
                                onClick={() => setLocale(code)}
                                aria-pressed={locale === code}
                                className={[
                                    'px-2.5 py-1.5 font-semibold uppercase transition-colors',
                                    locale === code
                                        ? 'bg-brand text-brand-ink'
                                        : 'text-ink-secondary hover:bg-brand-wash',
                                ].join(' ')}
                            >
                                {code}
                            </button>
                        ))}
                    </div>

                    <button
                        type="button"
                        onClick={toggle}
                        aria-label={t(theme === 'dark' ? 'nav.theme.light' : 'nav.theme.dark')}
                        className="rounded-lg border border-line px-2.5 py-1.5 text-sm text-ink-secondary hover:bg-brand-wash"
                    >
                        <span aria-hidden="true">{theme === 'dark' ? '☀' : '☾'}</span>
                    </button>
                </div>
            </div>
        </header>
    )
}
