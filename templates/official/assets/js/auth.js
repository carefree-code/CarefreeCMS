// auth.js - 用户认证功能
// 注意：使用传统function语法，避免箭头函数导致模板解析错误

// API基础URL
var API_BASE = '/api';

// 检查用户登录状态
function checkLoginStatus() {
    var token = localStorage.getItem('userToken');
    var userInfo = localStorage.getItem('userInfo');

    if (token && userInfo) {
        try {
            userInfo = JSON.parse(userInfo);
            updateUserUI(userInfo);
            return true;
        } catch (e) {
            console.error('Parse user info error:', e);
            logout();
            return false;
        }
    }

    showLoginButtons();
    return false;
}

// 更新用户界面
function updateUserUI(userInfo) {
    // 隐藏登录/注册按钮
    var loginBtn = document.getElementById('loginBtn');
    var registerBtn = document.getElementById('registerBtn');
    if (loginBtn) loginBtn.style.display = 'none';
    if (registerBtn) registerBtn.style.display = 'none';

    // 显示用户下拉菜单
    var userDropdown = document.getElementById('userDropdown');
    if (userDropdown) {
        userDropdown.style.display = 'block';
        var userName = document.getElementById('userName');
        if (userName) {
            userName.textContent = userInfo.nickname || userInfo.username;
        }
    }
}

// 显示登录按钮
function showLoginButtons() {
    var loginBtn = document.getElementById('loginBtn');
    var registerBtn = document.getElementById('registerBtn');
    if (loginBtn) loginBtn.style.display = 'block';
    if (registerBtn) registerBtn.style.display = 'block';

    var userDropdown = document.getElementById('userDropdown');
    if (userDropdown) userDropdown.style.display = 'none';
}

// 退出登录
function logout() {
    var token = localStorage.getItem('userToken');

    if (token) {
        // 调用后端退出API
        fetch(API_BASE + '/front/auth/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            console.log('Logout response:', data);
        })
        .catch(function(error) {
            console.error('Logout error:', error);
        });
    }

    // 清除本地存储
    localStorage.removeItem('userToken');
    localStorage.removeItem('userInfo');

    // 重定向到首页
    window.location.href = '/index.html';
}

// 获取用户Token
function getUserToken() {
    return localStorage.getItem('userToken');
}

// 获取用户信息
function getUserInfo() {
    try {
        var userInfo = localStorage.getItem('userInfo');
        return userInfo ? JSON.parse(userInfo) : null;
    } catch (e) {
        return null;
    }
}

// 页面加载时检查登录状态
document.addEventListener('DOMContentLoaded', function() {
    checkLoginStatus();

    // 绑定退出按钮
    var logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('确定要退出登录吗？')) {
                logout();
            }
        });
    }
});
