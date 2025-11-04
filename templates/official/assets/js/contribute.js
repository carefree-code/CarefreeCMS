/**
 * contribute.js - 投稿中心页面脚本
 */

// 页面加载
document.addEventListener('DOMContentLoaded', function() {
    // 检查登录状态
    const userStr = localStorage.getItem('user');
    if (!userStr) {
        window.location.href = '/login.html?redirect=' + encodeURIComponent(window.location.pathname);
        return;
    }

    // 字符计数
    document.getElementById('title').addEventListener('input', function() {
        document.getElementById('titleCount').textContent = this.value.length;
    });

    document.getElementById('summary').addEventListener('input', function() {
        document.getElementById('summaryCount').textContent = this.value.length;
    });

    document.getElementById('content').addEventListener('input', function() {
        document.getElementById('contentCount').textContent = this.value.length;
    });

    // 表单提交
    document.getElementById('contributeForm').addEventListener('submit', handleSubmit);

    // 检查是否是编辑模式
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('id');
    if (editId) {
        loadContribution(editId);
    }
});

// 加载投稿数据（编辑模式）
async function loadContribution(id) {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(`/api/front/contribution/detail/${id}`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        const result = await response.json();

        if (result.code === 200) {
            const contrib = result.data;
            document.getElementById('title').value = contrib.title || '';
            document.getElementById('category').value = contrib.category_id || '';
            document.getElementById('tags').value = contrib.tags || '';
            document.getElementById('cover').value = contrib.cover_image || '';
            document.getElementById('summary').value = contrib.summary || '';
            document.getElementById('content').value = contrib.content || '';
            document.getElementById('seo_title').value = contrib.seo_title || '';
            document.getElementById('seo_keywords').value = contrib.seo_keywords || '';
            document.getElementById('seo_description').value = contrib.seo_description || '';

            // 更新字符计数
            document.getElementById('titleCount').textContent = contrib.title.length;
            document.getElementById('summaryCount').textContent = contrib.summary.length;
            document.getElementById('contentCount').textContent = contrib.content.length;

            // 更新表单标题
            document.querySelector('.contribute-hero h1').innerHTML =
                '<i class="bi bi-pencil-square"></i> 编辑投稿';
        }
    } catch (error) {
        console.error('加载投稿详情失败:', error);
    }
}

// 处理表单提交
async function handleSubmit(e) {
    e.preventDefault();

    const formData = {
        title: document.getElementById('title').value.trim(),
        category_id: document.getElementById('category').value,
        tags: document.getElementById('tags').value.trim(),
        cover_image: document.getElementById('cover').value.trim(),
        summary: document.getElementById('summary').value.trim(),
        content: document.getElementById('content').value.trim(),
        seo_title: document.getElementById('seo_title').value.trim(),
        seo_keywords: document.getElementById('seo_keywords').value.trim(),
        seo_description: document.getElementById('seo_description').value.trim()
    };

    // 验证
    if (!formData.title) {
        alert('请输入文章标题');
        return;
    }

    if (!formData.category_id) {
        alert('请选择文章分类');
        return;
    }

    if (!formData.summary) {
        alert('请输入文章摘要');
        return;
    }

    if (!formData.content) {
        alert('请输入文章内容');
        return;
    }

    if (formData.content.length < 100) {
        alert('文章内容不能少于100字');
        return;
    }

    // 提交
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('id');

    try {
        const token = localStorage.getItem('token');
        const url = editId
            ? `/api/front/contribution/update/${editId}`
            : '/api/front/contribution/create';
        const method = editId ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.code === 200) {
            alert(editId ? '投稿更新成功！' : '投稿提交成功！将在审核通过后发布。');
            window.location.href = '/contributions.html';
        } else {
            alert('提交失败：' + result.message);
        }
    } catch (error) {
        console.error('提交出错:', error);
        alert('提交失败，请稍后重试');
    }
}
