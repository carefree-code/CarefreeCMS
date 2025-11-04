<?php

/**
 * 事件和监听器配置
 */
return [
    // 事件监听绑定
    'bind' => [
        // 用户行为事件 => 会员等级升级检查监听器
        'app\event\UserAction' => [
            'app\listener\CheckMemberLevelUpgrade',
        ],
    ],

    // 事件订阅者
    'subscribe' => [],
];
