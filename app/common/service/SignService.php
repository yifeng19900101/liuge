<?php

namespace app\common\service;

use think\facade\Cache;
use think\facade\Config;
use think\Request;

/**
 * 会员端接口签名
 * 规范化字符串：METHOD\nPATH\nQUERY\nTIMESTAMP\nNONCE\nsha256(RAW_BODY)
 * 签名算法：hash_hmac('sha256', 规范化字符串, 密钥)
 */
class SignService
{
    public const HEADER_SIGN      = 'x-sign';
    public const HEADER_TIMESTAMP = 'x-timestamp';
    public const HEADER_NONCE     = 'x-nonce';

    protected array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?: (array)Config::get('liu.api_sign', []);
    }

    /**
     * 签名密钥
     */
    public function secret(): string
    {
        return (string)($this->config['secret'] ?? '');
    }

    /**
     * 时间戳允许偏差（秒）
     */
    public function ttl(): int
    {
        $ttl = (int)($this->config['ttl'] ?? 300);
        return $ttl > 0 ? $ttl : 300;
    }

    /**
     * 该接口是否需要跳过签名
     * @param string $path 不含应用前缀的路径，如 api/chat/send
     */
    public function isExcluded(string $path): bool
    {
        $path = trim($path, '/');
        foreach ((array)($this->config['exclude'] ?? []) as $item) {
            $item = trim((string)$item, '/');
            if ($item === '') continue;
            if ($path === $item || str_starts_with($path, $item . '/') || str_ends_with($path, '/' . $item)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 生成规范化字符串
     */
    public function canonical(string $method, string $path, string $query, string $timestamp, string $nonce, string $body): string
    {
        return implode("\n", [
            strtoupper($method),
            '/' . ltrim($path, '/'),
            $query,
            $timestamp,
            $nonce,
            hash('sha256', $body),
        ]);
    }

    /**
     * 计算签名
     */
    public function sign(string $canonical, string $secret): string
    {
        return hash_hmac('sha256', $canonical, $secret);
    }

    /**
     * 校验请求签名
     * @return string 空字符串表示通过，否则为失败原因
     */
    public function verify(Request $request): string
    {
        $secret = $this->secret();
        if ($secret === '') {
            return '接口签名密钥未配置';
        }

        $sign      = (string)$request->header(self::HEADER_SIGN, '');
        $timestamp = (string)$request->header(self::HEADER_TIMESTAMP, '');
        $nonce     = (string)$request->header(self::HEADER_NONCE, '');

        if ($sign === '' || $timestamp === '' || $nonce === '') {
            return '缺少签名参数';
        }
        if (!ctype_digit($timestamp)) {
            return '签名时间戳无效';
        }
        if (abs(time() - (int)$timestamp) > $this->ttl()) {
            return '签名已过期';
        }
        if (strlen($nonce) < 8 || strlen($nonce) > 64) {
            return '签名随机串无效';
        }

        $nonceKey = 'api_sign_nonce:' . $nonce;
        if (Cache::has($nonceKey)) {
            return '签名随机串已使用';
        }

        $canonical = $this->canonical(
            $request->method(),
            $this->path($request),
            $this->query($request),
            $timestamp,
            $nonce,
            $request->getContent()
        );
        $expected  = $this->sign($canonical, $secret);
        if (!hash_equals($expected, strtolower($sign))) {
            return '签名校验失败';
        }

        Cache::set($nonceKey, 1, $this->ttl() * 2);
        return '';
    }

    /**
     * 当前请求路径（不含入口文件和查询串）
     */
    protected function path(Request $request): string
    {
        return (string)$request->pathinfo();
    }

    /**
     * 当前请求查询串（按 key 排序后重新拼装，保证前后端一致）
     */
    protected function query(Request $request): string
    {
        $query = (string)$request->server('QUERY_STRING', '');
        if ($query === '') {
            return '';
        }

        parse_str($query, $params);
        if (!$params) {
            return '';
        }
        ksort($params);
        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
