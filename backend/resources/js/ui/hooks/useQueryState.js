import { useCallback, useEffect, useMemo, useState } from 'react'

function readParams() {
    const sp = new URLSearchParams(window.location.search)
    const obj = {}
    for (const [k, v] of sp.entries()) obj[k] = v
    return obj
}

function writeParams(next, { replace = true } = {}) {
    const sp = new URLSearchParams(window.location.search)
    Object.entries(next).forEach(([k, v]) => {
        if (v === null || v === undefined || v === '') sp.delete(k)
        else sp.set(k, String(v))
    })

    const url = `${window.location.pathname}?${sp.toString()}${window.location.hash || ''}`
    if (replace) window.history.replaceState({}, '', url)
    else window.history.pushState({}, '', url)
}

export default function useQueryState(defaults = {}) {
    const [params, setParams] = useState(() => ({ ...defaults, ...readParams() }))

    // sync when user hits back/forward
    useEffect(() => {
        const onPop = () => setParams({ ...defaults, ...readParams() })
        window.addEventListener('popstate', onPop)
        return () => window.removeEventListener('popstate', onPop)
    }, [defaults])

    const setQuery = useCallback((patch, opts) => {
        writeParams(patch, opts)
        setParams((prev) => ({ ...prev, ...patch }))
    }, [])

    const get = useCallback((key) => params[key], [params])

    const api = useMemo(() => ({ params, setQuery, get }), [params, setQuery, get])

    return api
}
