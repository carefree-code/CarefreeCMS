<?php

namespace app\model;

use think\Model;

/**
 * 自定义字段值模型
 */
class CustomFieldValue extends Model
{
    protected $name = 'custom_field_values';

    protected $autoWriteTimestamp = true;

    protected $type = [
        'field_id'   => 'integer',
        'entity_id'  => 'integer',
    ];

    /**
     * 关联字段定义
     */
    public function field()
    {
        return $this->belongsTo(CustomField::class, 'field_id', 'id');
    }

    /**
     * 根据实体获取所有字段值
     */
    public static function getEntityValues($entityType, $entityId)
    {
        $values = self::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->with('field')
            ->select();

        $result = [];
        foreach ($values as $value) {
            if ($value->field) {
                $result[$value->field->field_key] = $value->field_value;
            }
        }

        return $result;
    }

    /**
     * 保存实体的字段值
     */
    public static function saveEntityValues($entityType, $entityId, $fieldValues)
    {
        foreach ($fieldValues as $fieldKey => $value) {
            // 查找字段定义
            $field = CustomField::where('field_key', $fieldKey)
                ->where('model_type', $entityType)
                ->where('status', 1)
                ->find();

            if (!$field) {
                continue;
            }

            // 处理值
            $fieldValue = $value;
            if (is_array($value)) {
                $fieldValue = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            // 更新或创建值
            $existingValue = self::where('field_id', $field->id)
                ->where('entity_type', $entityType)
                ->where('entity_id', $entityId)
                ->find();

            if ($existingValue) {
                $existingValue->field_value = $fieldValue;
                $existingValue->save();
            } else {
                self::create([
                    'field_id'    => $field->id,
                    'entity_type' => $entityType,
                    'entity_id'   => $entityId,
                    'field_value' => $fieldValue,
                ]);
            }
        }
    }

    /**
     * 删除实体的所有字段值
     */
    public static function deleteEntityValues($entityType, $entityId)
    {
        self::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->delete();
    }
}
