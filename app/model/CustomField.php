<?php

namespace app\model;

use think\Model;

/**
 * 自定义字段模型
 */
class CustomField extends Model
{
    protected $name = 'custom_fields';

    protected $autoWriteTimestamp = true;

    protected $type = [
        'model_id'         => 'integer',
        'is_required'      => 'integer',
        'is_searchable'    => 'integer',
        'is_show_in_list'  => 'integer',
        'sort'             => 'integer',
        'status'           => 'integer',
    ];

    protected $json = ['options', 'validation_rules'];

    // 字段类型常量
    const TYPE_TEXT = 'text';           // 单行文本
    const TYPE_NUMBER = 'number';       // 数字
    const TYPE_DATE = 'date';           // 日期
    const TYPE_DATETIME = 'datetime';   // 日期时间
    const TYPE_SELECT = 'select';       // 下拉选择
    const TYPE_RADIO = 'radio';         // 单选按钮
    const TYPE_CHECKBOX = 'checkbox';   // 多选框
    const TYPE_TEXTAREA = 'textarea';   // 多行文本
    const TYPE_RICHTEXT = 'richtext';   // 富文本
    const TYPE_IMAGE = 'image';         // 图片
    const TYPE_FILE = 'file';           // 文件

    // 模型类型常量
    const MODEL_ARTICLE = 'article';
    const MODEL_CATEGORY = 'category';
    const MODEL_TAG = 'tag';
    const MODEL_PAGE = 'page';
    const MODEL_CUSTOM = 'custom';

    /**
     * 关联内容模型
     */
    public function contentModel()
    {
        return $this->belongsTo(ContentModel::class, 'model_id', 'id');
    }

    /**
     * 获取字段的所有值
     */
    public function values()
    {
        return $this->hasMany(CustomFieldValue::class, 'field_id', 'id');
    }

    /**
     * 搜索器：字段名称
     */
    public function searchNameAttr($query, $value)
    {
        $query->where('name', 'like', '%' . $value . '%');
    }

    /**
     * 搜索器：模型类型
     */
    public function searchModelTypeAttr($query, $value)
    {
        $query->where('model_type', $value);
    }

    /**
     * 搜索器：模型ID
     */
    public function searchModelIdAttr($query, $value)
    {
        $query->where('model_id', $value);
    }

    /**
     * 搜索器：字段组
     */
    public function searchGroupNameAttr($query, $value)
    {
        $query->where('group_name', $value);
    }

    /**
     * 搜索器：状态
     */
    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }

    /**
     * 获取器：字段类型文本
     */
    public function getFieldTypeTextAttr($value, $data)
    {
        $types = [
            'text'     => '单行文本',
            'number'   => '数字',
            'date'     => '日期',
            'datetime' => '日期时间',
            'select'   => '下拉选择',
            'radio'    => '单选按钮',
            'checkbox' => '多选框',
            'textarea' => '多行文本',
            'richtext' => '富文本',
            'image'    => '图片上传',
            'file'     => '文件上传',
        ];
        return $types[$data['field_type']] ?? '未知';
    }

    /**
     * 获取器：模型类型文本
     */
    public function getModelTypeTextAttr($value, $data)
    {
        $types = [
            'article'  => '文章',
            'category' => '分类',
            'tag'      => '标签',
            'page'     => '单页',
            'custom'   => '自定义模型',
        ];
        return $types[$data['model_type']] ?? '未知';
    }

    /**
     * 获取所有字段类型
     */
    public static function getFieldTypes()
    {
        return [
            ['value' => 'text', 'label' => '单行文本'],
            ['value' => 'number', 'label' => '数字'],
            ['value' => 'date', 'label' => '日期'],
            ['value' => 'datetime', 'label' => '日期时间'],
            ['value' => 'select', 'label' => '下拉选择'],
            ['value' => 'radio', 'label' => '单选按钮'],
            ['value' => 'checkbox', 'label' => '多选框'],
            ['value' => 'textarea', 'label' => '多行文本'],
            ['value' => 'richtext', 'label' => '富文本'],
            ['value' => 'image', 'label' => '图片上传'],
            ['value' => 'file', 'label' => '文件上传'],
        ];
    }

    /**
     * 获取所有模型类型
     */
    public static function getModelTypes()
    {
        return [
            ['value' => 'article', 'label' => '文章'],
            ['value' => 'category', 'label' => '分类'],
            ['value' => 'tag', 'label' => '标签'],
            ['value' => 'page', 'label' => '单页'],
            ['value' => 'custom', 'label' => '自定义模型'],
        ];
    }
}
