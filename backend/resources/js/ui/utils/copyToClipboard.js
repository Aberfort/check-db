export async function copyToClipboard(text) {
    try {
        if (navigator?.clipboard?.writeText && window.isSecureContext) {
            await navigator.clipboard.writeText(text)
            return true
        }
    } catch (e) {
        // continue to fallback
    }

    // 2) fallback для не-HTTPS / Safari / політик
    try {
        const ta = document.createElement('textarea')
        ta.value = text
        ta.setAttribute('readonly', '')
        ta.style.position = 'fixed'
        ta.style.top = '-9999px'
        ta.style.left = '-9999px'
        ta.style.opacity = '0'
        document.body.appendChild(ta)
        ta.focus()
        ta.select()

        const ok = document.execCommand('copy')
        document.body.removeChild(ta)
        return ok
    } catch (e) {
        return false
    }
}
