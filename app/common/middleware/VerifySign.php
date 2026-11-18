<?php

declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;
use app\common\service\SignService;

/**
 * 会员端接口签名校验
 * 请求头：X-Sign、X-Timestamp、X-Nonce
 */
class VerifySign
{
    public function handle(Request $request, Closure $next): Response
    {
        $signService = new SignService();
        $path        = (string)$request->pathinfo();

        if ($signService->isExcluded($path)) {
            return $next($request);
        }

        $error = $signService->verify($request);
        if ($error !== '') {
            return json([
                'code' => 0,
                'msg'  => $error,
                'time' => time(),
                'data' => null,
            ], 200);
        }

        return $next($request);
    }
}
