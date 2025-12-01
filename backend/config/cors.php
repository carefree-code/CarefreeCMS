<?php
// CORS跨域配置
return [
    // 允许的来源白名单（从.env读取，多个用逗号分隔）
    // 空数组 [] 表示允许所有来源（开发环境）
    'allowed_origins' => !empty(env('cors.allowed_origins'))
        ? array_map('trim', explode(',', env('cors.allowed_origins')))
        : [],  // 允许所有来源（适合开发/测试）

    // 生产环境示例（取消注释并配置）：
    // 'allowed_origins' => [
    //     'https://your-frontend-domain.com',
    //     'http://localhost:5173',
    //     'http://localhost:3000',
    // ],

    // 允许的HTTP方法
    'allowed_methods' => 'GET, POST, PUT, DELETE, OPTIONS, PATCH',

    // 允许的请求头
    'allowed_headers' => 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-Token',

    // 预检请求的有效期（秒）
    'max_age' => 86400,

    // 允许暴露的响应头
    'expose_headers' => 'Content-Length, Content-Type',

    // 是否允许携带认证信息（cookies等）
    'allow_credentials' => true,
];
