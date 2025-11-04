<?php

namespace app\event;

/**
 * 用户行为事件
 * 当用户执行某些行为时触发，用于检查等级升级
 */
class UserAction
{
    /**
     * 用户ID
     * @var int
     */
    public $userId;

    /**
     * 行为类型
     * @var string (article_created, comment_created, points_changed)
     */
    public $actionType;

    /**
     * 行为数据
     * @var array
     */
    public $actionData;

    /**
     * 构造函数
     *
     * @param int $userId 用户ID
     * @param string $actionType 行为类型
     * @param array $actionData 行为数据
     */
    public function __construct(int $userId, string $actionType, array $actionData = [])
    {
        $this->userId = $userId;
        $this->actionType = $actionType;
        $this->actionData = $actionData;
    }
}
