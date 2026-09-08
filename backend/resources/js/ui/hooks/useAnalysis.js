import { useEffect, useState } from 'react'
import { getJson } from '../api/http.js'

const FINISHED = ['success', 'error']

/**
 * Follows one analysis to completion over server-sent events, falling back to
 * polling where the stream cannot be established (a buffering proxy, say).
 */
export default function useAnalysis (analysisId) {
    const [analysis, setAnalysis] = useState(null)
    const [error, setError] = useState(null)

    useEffect(() => {
        if (!analysisId) {
            setAnalysis(null)
            setError(null)

            return
        }

        let cancelled = false
        let source = null
        let pollTimer = null

        const accept = (data) => {
            if (cancelled || !data) return false

            setAnalysis(data)

            return FINISHED.includes(data.status)
        }

        const poll = async () => {
            try {
                const { data } = await getJson(`/api/analyses/${analysisId}`)

                if (accept(data)) {
                    clearInterval(pollTimer)
                }
            } catch (e) {
                if (!cancelled) setError(e)
                clearInterval(pollTimer)
            }
        }

        const startPolling = () => {
            if (pollTimer || cancelled) return

            pollTimer = setInterval(poll, 1500)
            void poll()
        }

        try {
            source = new EventSource(`/api/analyses/${analysisId}/events`)

            source.addEventListener('analysis', (event) => {
                if (accept(JSON.parse(event.data))) {
                    source.close()
                }
            })

            source.onerror = () => {
                source.close()
                startPolling()
            }
        } catch {
            startPolling()
        }

        // Seed immediately so the first paint does not wait on the stream.
        void getJson(`/api/analyses/${analysisId}`)
            .then(({ data }) => accept(data))
            .catch((e) => {
                if (!cancelled) setError(e)
            })

        return () => {
            cancelled = true
            source?.close()
            clearInterval(pollTimer)
        }
    }, [analysisId])

    return { analysis, error }
}
