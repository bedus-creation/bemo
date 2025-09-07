import { ref } from "vue"

export function useHttp(baseURL = "") {
    const loading = ref(false)
    const error = ref(null)
    const data = ref(null)

    const request = async (endpoint, options = {}, { onSuccess, onError, onFinally } = {}) => {
        loading.value = true
        error.value = null
        data.value = null

        try {
            const res = await fetch(baseURL + endpoint, {
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    ...options.headers,
                },
                ...options,
            })

            if (!res.ok) {
                const errorText = await res.text().catch(() => "")
                throw new Error(`HTTP ${res.status} - ${res.statusText}: ${errorText}`)
            }

            const responseData = await res.json().catch(() => null)
            data.value = responseData

            if (onSuccess) onSuccess(responseData)
            return responseData
        } catch (err) {
            error.value = err
            if (onError) onError(err)
            else console.error(err)
            throw err
        } finally {
            loading.value = false
            if (onFinally) onFinally()
        }
    }

    const get = (endpoint, options = {}, callbacks = {}) => {
        return request(endpoint, { ...options, method: "GET" }, callbacks)
    }

    const post = (endpoint, body, options = {}, callbacks = {}) => {
        return request(endpoint, { ...options, method: "POST", body: JSON.stringify(body) }, callbacks)
    }

    const put = (endpoint, body, options = {}, callbacks = {}) => {
        return request(endpoint, { ...options, method: "PUT", body: JSON.stringify(body) }, callbacks)
    }

    const del = (endpoint, options = {}, callbacks = {}) => {
        return request(endpoint, { ...options, method: "DELETE" }, callbacks)
    }

    return {
        loading,
        error,
        data,
        get,
        post,
        put,
        del,
    }
}
