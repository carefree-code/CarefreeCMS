/**
 * login.js - 登录页面脚本
 */

// 页面加载完成后检查是否已登录
document.addEventListener('DOMContentLoaded', function() {
    var token = localStorage.getItem('userToken');
    if (token) {
        // 如果已登录，重定向到首页或个人中心
        var redirect = new URLSearchParams(window.location.search).get('redirect') || '/profile.html';
        window.location.href = redirect;
    }
});

// 登录表单提交
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();

    var email = document.getElementById('email').value;
    var password = document.getElementById('password').value;
    var remember = document.getElementById('remember').checked;

    var submitBtn = document.getElementById('loginSubmitBtn');
    var btnText = submitBtn.querySelector('.btn-text');
    var btnSpinner = submitBtn.querySelector('.spinner-border');

    // 显示加载状态
    submitBtn.disabled = true;
    btnText.textContent = '登录中...';
    btnSpinner.classList.remove('d-none');

    // 隐藏之前的错误提示
    document.getElementById('loginAlert').style.display = 'none';

    // 调用登录API
    fetch('/api/front/auth/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            email: email,
            password: password
        })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.code === 200) {
            // 登录成功
            localStorage.setItem('userToken', data.data.token);
            localStorage.setItem('userInfo', JSON.stringify(data.data.user_info));

            // 显示成功提示
            alert('登录成功！');

            // 跳转
            var redirect = new URLSearchParams(window.location.search).get('redirect') || '/index.html';
            window.location.href = redirect;
        } else {
            // 登录失败
            showError(data.message || '登录失败，请检查邮箱和密码');
        }
    })
    .catch(function(error) {
        showError('网络错误，请稍后重试');
        console.error('Login error:', error);
    })
    .finally(function() {
        // 恢复按钮状态
        submitBtn.disabled = false;
        btnText.textContent = '登录';
        btnSpinner.classList.add('d-none');
    });
});

function showError(message) {
    var alert = document.getElementById('loginAlert');
    document.getElementById('loginAlertMessage').textContent = message;
    alert.style.display = 'block';

    // 3秒后自动隐藏
    setTimeout(function() {
        alert.style.display = 'none';
    }, 3000);
}
