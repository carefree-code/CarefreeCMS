<template>
  <div class="ad-list">
    <el-tabs v-model="activeTab">
      <!-- 广告管理标签页 -->
      <el-tab-pane label="广告管理" name="ads">
        <el-card>
          <!-- 搜索栏 -->
          <el-form :inline="true" :model="searchForm" class="search-form">
            <el-form-item label="所属站点">
              <el-select v-model="searchForm.site_id" placeholder="选择站点" clearable style="width: 200px;">
                <el-option label="全部站点" :value="null" />
                <el-option
                  v-for="site in siteOptions"
                  :key="site.id"
                  :label="site.name"
                  :value="site.id"
                />
              </el-select>
            </el-form-item>
            <el-form-item label="关键词">
              <el-input
                v-model="searchForm.keyword"
                placeholder="广告名称"
                clearable
                @keyup.enter="handleSearch"
              />
            </el-form-item>
            <el-form-item label="广告位">
              <el-select v-model="searchForm.position_id" placeholder="请选择" clearable>
                <el-option
                  v-for="pos in positions"
                  :key="pos.id"
                  :label="pos.name"
                  :value="pos.id"
                />
              </el-select>
            </el-form-item>
            <el-form-item label="类型">
              <el-select v-model="searchForm.type" placeholder="请选择" clearable>
                <el-option label="图片广告" value="image" />
                <el-option label="代码广告" value="code" />
                <el-option label="轮播广告" value="carousel" />
              </el-select>
            </el-form-item>
            <el-form-item label="状态">
              <el-select v-model="searchForm.status" placeholder="请选择" clearable>
                <el-option label="启用" :value="1" />
                <el-option label="禁用" :value="0" />
              </el-select>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="handleSearch">搜索</el-button>
              <el-button @click="handleReset">重置</el-button>
              <el-button type="success" @click="handleCreate">新建广告</el-button>
            </el-form-item>
          </el-form>

          <!-- 数据表格 -->
          <el-table :data="list" border style="width: 100%">
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column prop="name" label="广告名称" min-width="150" />
            <el-table-column prop="position.name" label="广告位" width="120" />
            <el-table-column label="类型" width="100">
              <template #default="{ row }">
                <el-tag v-if="row.type === 'image'" type="success" size="small">图片</el-tag>
                <el-tag v-else-if="row.type === 'code'" type="warning" size="small">代码</el-tag>
                <el-tag v-else type="info" size="small">轮播</el-tag>
              </template>
            </el-table-column>
            <el-table-column label="时间段" min-width="180">
              <template #default="{ row }">
                <div v-if="row.start_time || row.end_time">
                  {{ row.start_time || '不限' }} ~ {{ row.end_time || '不限' }}
                </div>
                <div v-else style="color: #999;">不限</div>
              </template>
            </el-table-column>
            <el-table-column label="状态" width="80" align="center">
              <template #default="{ row }">
                <el-tag v-if="row.status === 1" type="success" size="small">启用</el-tag>
                <el-tag v-else type="danger" size="small">禁用</el-tag>
              </template>
            </el-table-column>
            <el-table-column label="统计" width="120" align="center">
              <template #default="{ row }">
                <div>展示: {{ row.view_count }}</div>
                <div>点击: {{ row.click_count }}</div>
              </template>
            </el-table-column>
            <el-table-column prop="sort" label="排序" width="70" align="center" />
            <el-table-column label="操作" width="280" fixed="right">
              <template #default="{ row }">
                <el-button size="small" type="success" @click="handleShowCode(row)">代码</el-button>
                <el-button size="small" type="info" @click="handleViewStats(row)">统计</el-button>
                <el-button size="small" type="primary" @click="handleEdit(row)">编辑</el-button>
                <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>

          <!-- 分页 -->
          <el-pagination
            v-model:current-page="pagination.page"
            v-model:page-size="pagination.pageSize"
            :page-sizes="[10, 20, 50, 100]"
            :total="pagination.total"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handlePageChange"
            style="margin-top: 20px; justify-content: flex-end;"
          />
        </el-card>
      </el-tab-pane>

      <!-- 广告位管理标签页 -->
      <el-tab-pane label="广告位管理" name="positions">
        <el-card>
          <el-button type="primary" @click="handleCreatePosition" style="margin-bottom: 20px;">新建广告位</el-button>

          <el-table :data="positions" border style="width: 100%">
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column prop="name" label="广告位名称" min-width="150" />
            <el-table-column prop="slug" label="广告位代码" min-width="150" />
            <el-table-column label="尺寸" width="120">
              <template #default="{ row }">
                {{ row.width || '-' }} × {{ row.height || '-' }}
              </template>
            </el-table-column>
            <el-table-column prop="description" label="描述" min-width="180" show-overflow-tooltip />
            <el-table-column label="状态" width="80" align="center">
              <template #default="{ row }">
                <el-tag v-if="row.status === 1" type="success" size="small">启用</el-tag>
                <el-tag v-else type="danger" size="small">禁用</el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="180" fixed="right">
              <template #default="{ row }">
                <el-button size="small" type="primary" @click="handleEditPosition(row)">编辑</el-button>
                <el-button size="small" type="danger" @click="handleDeletePosition(row)">删除</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-tab-pane>
    </el-tabs>

    <!-- 广告对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="700px"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
      >
        <el-form-item label="所属站点" prop="site_id">
          <el-select v-model="form.site_id" placeholder="请选择站点" style="width: 100%;">
            <el-option
              v-for="site in siteOptions"
              :key="site.id"
              :label="site.name"
              :value="site.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="广告名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入广告名称" />
        </el-form-item>

        <el-form-item label="广告位" prop="position_id">
          <el-select v-model="form.position_id" placeholder="请选择广告位" style="width: 100%;">
            <el-option
              v-for="pos in positions.filter(p => p.status === 1)"
              :key="pos.id"
              :label="pos.name"
              :value="pos.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="广告类型" prop="type">
          <el-radio-group v-model="form.type">
            <el-radio label="image">图片广告</el-radio>
            <el-radio label="code">代码广告</el-radio>
            <el-radio label="carousel">轮播广告</el-radio>
          </el-radio-group>
        </el-form-item>

        <!-- 图片广告 -->
        <el-form-item v-if="form.type === 'image'" label="广告图片" prop="content">
          <el-upload
            class="ad-uploader"
            :action="uploadAction"
            :headers="uploadHeaders"
            :show-file-list="false"
            :on-success="handleImageSuccess"
            :before-upload="beforeImageUpload"
            name="file"
          >
            <img v-if="adImageUrl" :src="adImageUrl" class="ad-image" />
            <el-icon v-else class="ad-uploader-icon"><Plus /></el-icon>
          </el-upload>
        </el-form-item>

        <!-- 代码广告 -->
        <el-form-item v-if="form.type === 'code'" label="广告代码" prop="content">
          <el-input
            v-model="form.content"
            type="textarea"
            :rows="6"
            placeholder="请输入广告HTML代码"
          />
        </el-form-item>

        <!-- 轮播广告 -->
        <el-form-item v-if="form.type === 'carousel'" label="轮播图片">
          <div class="carousel-images">
            <div
              v-for="(img, index) in form.images"
              :key="index"
              class="carousel-image-item"
            >
              <img :src="img.url" />
              <el-button
                size="small"
                type="danger"
                @click="removeCarouselImage(index)"
              >
                删除
              </el-button>
            </div>
            <el-upload
              class="carousel-uploader"
              :action="uploadAction"
              :headers="uploadHeaders"
              :show-file-list="false"
              :on-success="handleCarouselSuccess"
              :before-upload="beforeImageUpload"
              name="file"
            >
              <el-button size="small" type="primary">添加图片</el-button>
            </el-upload>
          </div>
        </el-form-item>

        <el-form-item label="链接地址">
          <el-input v-model="form.link_url" placeholder="https://example.com" />
        </el-form-item>

        <el-form-item label="开始时间">
          <el-date-picker
            v-model="form.start_time"
            type="datetime"
            placeholder="选择开始时间"
            style="width: 100%;"
          />
        </el-form-item>

        <el-form-item label="结束时间">
          <el-date-picker
            v-model="form.end_time"
            type="datetime"
            placeholder="选择结束时间"
            style="width: 100%;"
          />
        </el-form-item>

        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" />
        </el-form-item>

        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio :label="1">启用</el-radio>
            <el-radio :label="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 广告位对话框 -->
    <el-dialog
      v-model="positionDialogVisible"
      :title="positionDialogTitle"
      width="500px"
    >
      <el-form
        ref="positionFormRef"
        :model="positionForm"
        :rules="positionRules"
        label-width="100px"
      >
        <el-form-item label="所属站点" prop="site_id">
          <el-select v-model="positionForm.site_id" placeholder="请选择站点" style="width: 100%;">
            <el-option
              v-for="site in siteOptions"
              :key="site.id"
              :label="site.name"
              :value="site.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="广告位名称" prop="name">
          <el-input v-model="positionForm.name" placeholder="请输入广告位名称" />
        </el-form-item>

        <el-form-item label="广告位代码" prop="slug">
          <el-input v-model="positionForm.slug" placeholder="例如：home_top_banner" />
          <div style="margin-top: 5px; color: #909399; font-size: 12px;">
            只能包含小写字母、数字和下划线
          </div>
        </el-form-item>

        <el-form-item label="广告位描述">
          <el-input v-model="positionForm.description" type="textarea" :rows="3" />
        </el-form-item>

        <el-form-item label="宽度（像素）">
          <el-input-number v-model="positionForm.width" :min="0" />
        </el-form-item>

        <el-form-item label="高度（像素）">
          <el-input-number v-model="positionForm.height" :min="0" />
        </el-form-item>

        <el-form-item label="状态">
          <el-radio-group v-model="positionForm.status">
            <el-radio :label="1">启用</el-radio>
            <el-radio :label="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="positionDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmitPosition" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 统计对话框 -->
    <el-dialog
      v-model="statsDialogVisible"
      title="广告统计"
      width="500px"
    >
      <el-descriptions :column="2" border>
        <el-descriptions-item label="广告名称" :span="2">{{ currentAd?.name }}</el-descriptions-item>
        <el-descriptions-item label="展示次数">{{ statistics.view_count }}</el-descriptions-item>
        <el-descriptions-item label="点击次数">{{ statistics.click_count }}</el-descriptions-item>
        <el-descriptions-item label="点击率">{{ statistics.click_rate }}%</el-descriptions-item>
      </el-descriptions>
    </el-dialog>

    <!-- 调用代码对话框 -->
    <el-dialog
      v-model="codeDialogVisible"
      title="快捷调用代码"
      width="600px"
    >
      <div style="margin-bottom: 15px;">
        <el-alert
          title="使用说明"
          type="info"
          description="将以下代码复制到模板文件中，即可在前台展示此广告。"
          show-icon
          :closable="false"
        />
      </div>

      <div style="margin-bottom: 10px;">
        <strong>Carefree 标签调用：</strong>
      </div>
      <el-input
        v-model="callCode"
        type="textarea"
        :rows="3"
        readonly
        style="margin-bottom: 20px;"
      />

      <div style="margin-bottom: 10px;">
        <strong>按广告位ID调用：</strong>
      </div>
      <el-input
        v-model="callCodeByPosition"
        type="textarea"
        :rows="3"
        readonly
      />

      <template #footer>
        <el-button @click="codeDialogVisible = false">关闭</el-button>
        <el-button type="primary" @click="copyCode">复制代码</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import {
  getAdList,
  getAdDetail,
  createAd,
  updateAd,
  deleteAd,
  getAdStatistics
} from '@/api/ad'
import {
  getAllAdPositions,
  createAdPosition,
  updateAdPosition,
  deleteAdPosition
} from '@/api/adPosition'
import { getSiteOptions } from '@/api/site'
import { getToken } from '@/utils/auth'

const activeTab = ref('ads')
const siteOptions = ref([])

const searchForm = reactive({
  site_id: null,
  keyword: '',
  position_id: '',
  type: '',
  status: ''
})

const pagination = reactive({
  page: 1,
  pageSize: 10,
  total: 0
})

const list = ref([])
const positions = ref([])
const loading = ref(false)
const dialogVisible = ref(false)
const dialogTitle = ref('')
const positionDialogVisible = ref(false)
const positionDialogTitle = ref('')
const statsDialogVisible = ref(false)
const codeDialogVisible = ref(false)
const submitting = ref(false)
const formRef = ref(null)
const positionFormRef = ref(null)
const isEdit = ref(false)
const editId = ref(0)
const isPositionEdit = ref(false)
const editPositionId = ref(0)
const currentAd = ref(null)
const callCode = ref('')
const callCodeByPosition = ref('')

const statistics = reactive({
  view_count: 0,
  click_count: 0,
  click_rate: 0
})

const form = reactive({
  site_id: null,
  position_id: null,
  name: '',
  type: 'image',
  content: '',
  link_url: '',
  images: [],
  start_time: null,
  end_time: null,
  status: 1,
  sort: 0
})

const positionForm = reactive({
  site_id: null,
  name: '',
  slug: '',
  description: '',
  width: null,
  height: null,
  status: 1
})

const rules = {
  site_id: [{ required: true, message: '请选择所属站点', trigger: 'change' }],
  name: [{ required: true, message: '请输入广告名称', trigger: 'blur' }],
  position_id: [{ required: true, message: '请选择广告位', trigger: 'change' }],
  type: [{ required: true, message: '请选择广告类型', trigger: 'change' }]
}

const positionRules = {
  site_id: [{ required: true, message: '请选择所属站点', trigger: 'change' }],
  name: [{ required: true, message: '请输入广告位名称', trigger: 'blur' }],
  slug: [
    { required: true, message: '请输入广告位代码', trigger: 'blur' },
    { pattern: /^[a-z0-9_]+$/, message: '只能包含小写字母、数字和下划线', trigger: 'blur' }
  ]
}

// 上传配置
const uploadAction = computed(() => {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || ''
  return baseUrl + '/media/upload'
})

const uploadHeaders = computed(() => {
  const token = getToken() || ''
  return {
    Authorization: 'Bearer ' + token
  }
})

const adImageUrl = computed(() => {
  return form.type === 'image' ? form.content : ''
})

// 上传前校验
const beforeImageUpload = (file) => {
  const isImage = file.type.startsWith('image/')
  const isLt2M = file.size / 1024 / 1024 < 2

  if (!isImage) {
    ElMessage.error('只能上传图片文件!')
    return false
  }
  if (!isLt2M) {
    ElMessage.error('图片大小不能超过 2MB!')
    return false
  }
  return true
}

// 图片上传成功
const handleImageSuccess = (response) => {
  if (response.code === 200) {
    form.content = response.data.file_url || response.data.file_path
    ElMessage.success('图片上传成功')
  } else {
    ElMessage.error(response.message || '上传失败')
  }
}

// 轮播图片上传成功
const handleCarouselSuccess = (response) => {
  if (response.code === 200) {
    const url = response.data.file_url || response.data.file_path
    form.images.push({ url })
    ElMessage.success('图片上传成功')
  } else {
    ElMessage.error(response.message || '上传失败')
  }
}

// 删除轮播图片
const removeCarouselImage = (index) => {
  form.images.splice(index, 1)
}

// 加载广告位
const loadPositions = async () => {
  try {
    const res = await getAllAdPositions()
    positions.value = res.data.list || []
  } catch (error) {
    ElMessage.error(error.message || '加载失败')
  }
}

// 加载数据
const loadData = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.page,
      page_size: pagination.pageSize,
      ...searchForm
    }
    const res = await getAdList(params)
    list.value = res.data.list
    pagination.total = res.data.total
  } catch (error) {
    ElMessage.error(error.message || '加载失败')
  } finally {
    loading.value = false
  }
}

// 搜索
const handleSearch = () => {
  pagination.page = 1
  loadData()
}

// 重置
const handleReset = () => {
  searchForm.site_id = null
  searchForm.keyword = ''
  searchForm.position_id = ''
  searchForm.type = ''
  searchForm.status = ''
  pagination.page = 1
  loadData()
}

// 分页变化
const handleSizeChange = () => {
  loadData()
}

const handlePageChange = () => {
  loadData()
}

// 新建广告
const handleCreate = () => {
  isEdit.value = false
  dialogTitle.value = '新建广告'
  resetForm()
  dialogVisible.value = true
}

// 编辑广告
const handleEdit = async (row) => {
  isEdit.value = true
  editId.value = row.id
  dialogTitle.value = '编辑广告'

  try {
    const res = await getAdDetail(row.id)
    Object.assign(form, res.data)
    // 确保images是数组
    if (form.images && !Array.isArray(form.images)) {
      form.images = JSON.parse(form.images)
    }
    if (!form.images) {
      form.images = []
    }
    dialogVisible.value = true
  } catch (error) {
    ElMessage.error(error.message || '加载失败')
  }
}

// 删除广告
const handleDelete = (row) => {
  ElMessageBox.confirm('确定要删除该广告吗？', '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      await deleteAd(row.id)
      ElMessage.success('删除成功')
      loadData()
    } catch (error) {
      ElMessage.error(error.message || '删除失败')
    }
  }).catch(() => {})
}

// 查看统计
const handleViewStats = async (row) => {
  currentAd.value = row
  try {
    const res = await getAdStatistics(row.id, {})
    Object.assign(statistics, res.data)
    statsDialogVisible.value = true
  } catch (error) {
    ElMessage.error(error.message || '加载统计失败')
  }
}

// 显示调用代码
const handleShowCode = (row) => {
  currentAd.value = row
  // 按广告ID调用
  callCode.value = `{carefree:ad id="${row.id}" /}`
  // 按广告位ID调用
  if (row.position_id) {
    callCodeByPosition.value = `{carefree:ad position="${row.position_id}" limit="1" id="ad"}
  <!-- 广告内容 -->
  {if condition="$ad.type eq 'image'"}
    <a href="{$ad.link_url}" target="_blank">
      <img src="{$ad.image}" alt="{$ad.name}">
    </a>
  {elseif condition="$ad.type eq 'code'"/}
    {$ad.content|raw}
  {/if}
{/carefree:ad}`
  } else {
    callCodeByPosition.value = '此广告未关联广告位'
  }
  codeDialogVisible.value = true
}

// 复制调用代码
const copyCode = async () => {
  try {
    await navigator.clipboard.writeText(callCode.value)
    ElMessage.success('代码已复制到剪贴板')
  } catch (error) {
    ElMessage.error('复制失败，请手动复制')
  }
}

// 提交表单
const handleSubmit = async () => {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (valid) {
      submitting.value = true
      try {
        const data = { ...form }

        // 处理时间
        if (data.start_time) {
          data.start_time = new Date(data.start_time).toISOString().slice(0, 19).replace('T', ' ')
        }
        if (data.end_time) {
          data.end_time = new Date(data.end_time).toISOString().slice(0, 19).replace('T', ' ')
        }

        if (isEdit.value) {
          await updateAd(editId.value, data)
          ElMessage.success('更新成功')
        } else {
          await createAd(data)
          ElMessage.success('创建成功')
        }
        dialogVisible.value = false
        loadData()
      } catch (error) {
        ElMessage.error(error.message || '保存失败')
      } finally {
        submitting.value = false
      }
    }
  })
}

// 新建广告位
const handleCreatePosition = () => {
  isPositionEdit.value = false
  positionDialogTitle.value = '新建广告位'
  resetPositionForm()
  positionDialogVisible.value = true
}

// 编辑广告位
const handleEditPosition = (row) => {
  isPositionEdit.value = true
  editPositionId.value = row.id
  positionDialogTitle.value = '编辑广告位'
  Object.assign(positionForm, row)
  positionDialogVisible.value = true
}

// 删除广告位
const handleDeletePosition = (row) => {
  ElMessageBox.confirm('确定要删除该广告位吗？', '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      await deleteAdPosition(row.id)
      ElMessage.success('删除成功')
      loadPositions()
    } catch (error) {
      ElMessage.error(error.message || '删除失败')
    }
  }).catch(() => {})
}

// 提交广告位表单
const handleSubmitPosition = async () => {
  if (!positionFormRef.value) return

  await positionFormRef.value.validate(async (valid) => {
    if (valid) {
      submitting.value = true
      try {
        const data = { ...positionForm }

        if (isPositionEdit.value) {
          await updateAdPosition(editPositionId.value, data)
          ElMessage.success('更新成功')
        } else {
          await createAdPosition(data)
          ElMessage.success('创建成功')
        }
        positionDialogVisible.value = false
        loadPositions()
        loadData()
      } catch (error) {
        ElMessage.error(error.message || '保存失败')
      } finally {
        submitting.value = false
      }
    }
  })
}

// 重置表单
const resetForm = () => {
  form.site_id = null
  form.position_id = null
  form.name = ''
  form.type = 'image'
  form.content = ''
  form.link_url = ''
  form.images = []
  form.start_time = null
  form.end_time = null
  form.status = 1
  form.sort = 0
}

const resetPositionForm = () => {
  positionForm.site_id = null
  positionForm.name = ''
  positionForm.slug = ''
  positionForm.description = ''
  positionForm.width = null
  positionForm.height = null
  positionForm.status = 1
}

// 获取站点选项
const fetchSiteOptions = async () => {
  try {
    const res = await getSiteOptions()
    siteOptions.value = res.data || []
  } catch (error) {
    console.error('获取站点列表失败:', error)
  }
}

onMounted(async () => {
  await fetchSiteOptions()
  await loadPositions()
  await loadData()
})
</script>

<style scoped>
.ad-list {
  padding: 20px;
}

.search-form {
  margin-bottom: 20px;
}

.ad-uploader .ad-image {
  max-width: 300px;
  max-height: 200px;
  width: auto;
  height: auto;
  display: block;
  object-fit: contain;
  border-radius: 4px;
}

.ad-uploader :deep(.el-upload) {
  border: 1px dashed #d9d9d9;
  border-radius: 4px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: all 0.3s;
  width: 300px;
  height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #fafafa;
}

.ad-uploader :deep(.el-upload:hover) {
  border-color: #409eff;
}

.ad-uploader-icon {
  font-size: 28px;
  color: #8c939d;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.carousel-images {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.carousel-image-item {
  position: relative;
  width: 150px;
}

.carousel-image-item img {
  width: 100%;
  height: 100px;
  object-fit: cover;
  border-radius: 4px;
}

.carousel-image-item .el-button {
  margin-top: 5px;
  width: 100%;
}
</style>
