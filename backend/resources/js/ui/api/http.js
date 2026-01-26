async function parseJson(res) {
    const json = await res.json().catch(() => ({}))
    if (!res.ok) throw new Error(json?.message || 'Request failed')
    return json
}

export async function getJson(url) {
    const res = await fetch(url)
    const json = await res.json().catch(() => ({}))

    if (!res.ok) {
        const err = new Error(json?.message || 'Request failed')
        err.status = res.status
        err.code = json?.code
        err.payload = json
        throw err
    }

    return json
}

export async function postForm(url, formData) {
    const res = await fetch(url, { method: 'POST', body: formData })
    const json = await res.json().catch(() => ({}))

    if (!res.ok) {
        const err = new Error(json?.message || 'Request failed')
        err.status = res.status
        err.code = json?.code
        err.payload = json
        throw err
    }

    return json
}

export async function postJson(url, body = {}) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    })
    return parseJson(res)
}

export async function putJson(url, body = {}) {
    const res = await fetch(url, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    })
    return parseJson(res)
}

export function qs(params = {}) {
    const sp = new URLSearchParams()
    Object.entries(params).forEach(([k, v]) => {
        if (v === undefined || v === null || v === '' || v === 'all') return
        sp.set(k, String(v))
    })
    const s = sp.toString()
    return s ? `?${s}` : ''
}
