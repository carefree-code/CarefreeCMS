/**
 * notifications.js - 消息通知页面脚本
 */

// 页面加载时检查登录状态
document.addEventListener('DOMContentLoaded', function() {
    const userStr = localStorage.getItem('user');
    if (!userStr) {
        window.location.href = '/login.html?redirect=' + encodeURIComponent(window.location.pathname);
    }
});
