<?php
return [
    \app\common\middleware\AllowCrossDomain::class,
    \app\common\middleware\VerifySign::class,
    \think\middleware\LoadLangPack::class,
];
