/**
 * contributions.js - 我的投稿页面脚本
 */

// 页面加载时检查登录状态
document.addEventListener('DOMContentLoaded', function() {
    const userStr = localStorage.getItem('user');
    if (!userStr) {
        window.location.href = '/login.html?redirect=' + encodeURIComponent(window.location.pathname);
    }
});

// 删除投稿
async function deleteContribution(id) {
    if (!confirm('确定要删除这篇投稿吗？')) {
        return;
    }

    try {
        const token = localStorage.getItem('token');
        const response = await fetch(`/api/front/contribution/delete/${id}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });

        const result = await response.json();

        if (result.code === 200) {
            alert('删除成功');
            location.reload();
        } else {
            alert('删除失败：' + result.message);
        }
    } catch (error) {
        console.error('删除出错:', error);
        alert('删除失败');
    }
}
