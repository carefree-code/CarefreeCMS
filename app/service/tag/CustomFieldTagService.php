<?php
namespace app\service\tag;

use app\model\CustomField;
use app\model\CustomFieldValue;

/**
 * 自定义字段标签服务类
 * 处理自定义字段标签的数据查询
 */
class CustomFieldTagService
{
    /**
     * 获取自定义字段值
     *
     * @param int $entityId 实体ID（文章ID、单页ID等）
     * @param string $fieldName 字段键名
     * @param string $modelType 模型类型（article, page, category等）
     * @return string|array|null 字段值
     */
    public static function getValue($entityId, $fieldName, $modelType = 'article')
    {
        if (empty($entityId) || empty($fieldName)) {
            return null;
        }

        // 查找字段定义
        $field = CustomField::where('field_key', $fieldName)
            ->where('model_type', $modelType)
            ->where('status', 1)
            ->find();

        if (!$field) {
            return null;
        }

        // 查找字段值
        $fieldValue = CustomFieldValue::where('field_id', $field->id)
            ->where('entity_type', $modelType)
            ->where('entity_id', $entityId)
            ->find();

        if (!$fieldValue) {
            return null;
        }

        $value = $fieldValue->field_value;

        // 如果是JSON数据，尝试解码
        if (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }

    /**
     * 获取实体的所有自定义字段
     *
     * @param int $entityId 实体ID
     * @param string $modelType 模型类型
     * @return array 字段键值对数组
     */
    public static function getAll($entityId, $modelType = 'article')
    {
        if (empty($entityId)) {
            return [];
        }

        return CustomFieldValue::getEntityValues($modelType, $entityId);
    }
}
