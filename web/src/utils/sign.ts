import type { AxiosRequestConfig } from 'axios'

/**
 * 会员端接口签名
 * 规范化字符串：METHOD\nPATH\nQUERY\nTIMESTAMP\nNONCE\nsha256(RAW_BODY)
 * 签名算法：HMAC-SHA256(规范化字符串, 密钥)，结果与后端 SignService 保持一致
 */

const encoder = new TextEncoder()

/** 生成 32 位随机串 */
export function randomNonce(): string {
    const bytes = new Uint8Array(16)
    window.crypto.getRandomValues(bytes)
    return Array.from(bytes)
        .map((byte) => byte.toString(16).padStart(2, '0'))
        .join('')
}

/** 按 RFC3986 编码，与 PHP rawurlencode 对齐 */
function rfc3986(value: string): string {
    return encodeURIComponent(value).replace(/[!'()*]/g, (char) => '%' + char.charCodeAt(0).toString(16).toUpperCase())
}

/** 数组缓冲区转小写十六进制 */
function toHex(buffer: ArrayBuffer): string {
    return Array.from(new Uint8Array(buffer))
        .map((byte) => byte.toString(16).padStart(2, '0'))
        .join('')
}

/** 对象转排序后的查询串，与后端 ksort + http_build_query 对齐 */
export function buildQuery(params: anyObj): string {
    const keys = Object.keys(params).sort()
    const pairs: string[] = []
    for (const key of keys) {
        const value = params[key]
        if (value === undefined || value === null || value === '') continue
        if (Array.isArray(value)) {
            for (const item of value) {
                pairs.push(rfc3986(key) + '=' + rfc3986(String(item)))
            }
        } else {
            pairs.push(rfc3986(key) + '=' + rfc3986(String(value)))
        }
    }
    return pairs.join('&')
}

/** 拆分请求地址，得到路径与查询串 */
function splitUrl(url: string): { path: string; query: string } {
    let path = url
    if (/^https?:\/\//i.test(url)) {
        const parsed = new URL(url)
        path = parsed.pathname + parsed.search
    }
    const index = path.indexOf('?')
    if (index === -1) return { path, query: '' }
    return { path: path.slice(0, index), query: path.slice(index + 1) }
}

/** 计算请求签名 */
export async function createSign(
    method: string,
    path: string,
    query: string,
    timestamp: string,
    nonce: string,
    body: string,
    secret: string
): Promise<string> {
    const bodyDigest = toHex(await window.crypto.subtle.digest('SHA-256', encoder.encode(body)))
    const canonical = [method.toUpperCase(), '/' + path.replace(/^\/+/, ''), query, timestamp, nonce, bodyDigest].join('\n')

    const key = await window.crypto.subtle.importKey('raw', encoder.encode(secret), { name: 'HMAC', hash: 'SHA-256' }, false, ['sign'])
    const signature = await window.crypto.subtle.sign('HMAC', key, encoder.encode(canonical))
    return toHex(signature)
}

/** 设置请求头（兼容 AxiosHeaders 与普通对象） */
function setHeader(config: AxiosRequestConfig, name: string, value: string): void {
    const headers = config.headers as any
    if (!headers) return
    if (typeof headers.set === 'function') {
        headers.set(name, value)
    } else {
        headers[name] = value
    }
}

function getHeader(config: AxiosRequestConfig, name: string): string {
    const headers = config.headers as any
    if (!headers) return ''
    if (typeof headers.get === 'function') return String(headers.get(name) || '')
    return String(headers[name] || '')
}

/**
 * 为会员端请求补充签名头
 * 仅处理 /api/ 开头且非文件上传的请求
 */
export async function applySignature<T extends AxiosRequestConfig>(config: T): Promise<T> {
    const secret = import.meta.env.VITE_API_SIGN_SECRET as string
    if (!secret || !config.url) return config
    if (config.data instanceof FormData) return config

    const urlParts = splitUrl(config.url)
    const path = urlParts.path
    let query = urlParts.query
    if (!path.startsWith('/api/')) return config

    // 查询参数自行拼装，保证签名串与实际请求一致
    if (config.params && typeof config.params === 'object') {
        const built = buildQuery(config.params as anyObj)
        query = [query, built].filter(Boolean).join('&')
        delete config.params
    }

    // 请求体自行序列化，保证后端哈希与签名串一致
    let body = ''
    if (typeof config.data === 'string') {
        body = config.data
        if (!getHeader(config, 'Content-Type')) setHeader(config, 'Content-Type', 'application/json')
    } else if (config.data !== undefined && config.data !== null) {
        body = JSON.stringify(config.data)
        config.data = body
        setHeader(config, 'Content-Type', 'application/json')
    }

    config.url = query ? path + '?' + query : path

    // 后端拿到的 pathinfo 不含多应用名（api）前缀
    const signPath = path.replace(/^\/api(?=\/)/, '')
    const timestamp = String(Math.floor(Date.now() / 1000))
    const nonce = randomNonce()
    const sign = await createSign(config.method || 'get', signPath, query, timestamp, nonce, body, secret)

    setHeader(config, 'X-Sign', sign)
    setHeader(config, 'X-Timestamp', timestamp)
    setHeader(config, 'X-Nonce', nonce)

    return config
}
