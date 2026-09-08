export function qs (params = {}) {
    const search = new URLSearchParams()

    for (const [key, value] of Object.entries(params)) {
        if (value === undefined || value === null || value === '') continue
        search.set(key, String(value))
    }

    const query = search.toString()

    return query ? `?${query}` : ''
}

async function unwrap (response) {
    const body = await response.json().catch(() => ({}))

    if (!response.ok) {
        const error = new Error(body?.error?.message || 'Request failed')
        error.status = response.status
        error.code = body?.error?.code
        error.validation = body?.errors
        throw error
    }

    return body
}

export async function getJson (url) {
    return unwrap(await fetch(url, { headers: { Accept: 'application/json' } }))
}

export async function postForm (url, formData) {
    return unwrap(await fetch(url, {
        method: 'POST',
        headers: { Accept: 'application/json' },
        body: formData,
    }))
}
