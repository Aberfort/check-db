import { useEffect } from 'react'

export default function useHotkeys(map) {
    useEffect(() => {
        const onKeyDown = (e) => {
            const key = (e.key || '').toLowerCase()
            const combo = [
                e.metaKey ? 'meta' : '',
                e.ctrlKey ? 'ctrl' : '',
                e.shiftKey ? 'shift' : '',
                e.altKey ? 'alt' : '',
                key,
            ].filter(Boolean).join('+')

            if (map[combo]) {
                e.preventDefault()
                map[combo]?.(e)
            }
        }

        window.addEventListener('keydown', onKeyDown)
        return () => window.removeEventListener('keydown', onKeyDown)
    }, [map])
}
