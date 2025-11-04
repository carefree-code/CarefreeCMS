/**
 * register.js - 注册页面脚本
 */

// 页面加载完成后检查是否已登录
document.addEventListener('DOMContentLoaded', function() {
    var token = localStorage.getItem('userToken');
    if (token) {
        window.location.href = '/profile.html';
    }
});

// 密码强度检测
document.getElementById('password').addEventListener('input', function() {
    var password = this.value;
    var strengthFill = document.getElementById('strengthFill');
    var strengthText = document.getElementById('strengthText');

    // 重置样式
    strengthFill.className = 'strength-fill';

    if (password.length === 0) {
        strengthText.textContent = '至少6个字符';
        return;
    }

    var strength = calculatePasswordStrength(password);

    if (strength < 2) {
        strengthFill.classList.add('strength-weak');
        strengthText.textContent = '密码强度：弱';
        strengthText.style.color = '#dc3545';
    } else if (strength < 4) {
        strengthFill.classList.add('strength-medium');
        strengthText.textContent = '密码强度：中';
        strengthText.style.color = '#ffc107';
    } else {
        strengthFill.classList.add('strength-strong');
        strengthText.textContent = '密码强度：强';
        strengthText.style.color = '#28a745';
    }
});

function calculatePasswordStrength(password) {
    var strength = 0;

    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;

    return strength;
}

// 注册表单提交
document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();

    var username = document.getElementById('username').value;
    var email = document.getElementById('email').value;
    var password = document.getElementById('password').value;
    var confirmPassword = document.getElementById('confirmPassword').value;

    // 验证密码匹配
    if (password !== confirmPassword) {
        showAlert('error', '两次输入的密码不一致');
        return;
    }

    var submitBtn = document.getElementById('registerSubmitBtn');
    var btnText = submitBtn.querySelector('.btn-text');
    var btnSpinner = submitBtn.querySelector('.spinner-border');

    // 显示加载状态
    submitBtn.disabled = true;
    btnText.textContent = '注册中...';
    btnSpinner.classList.remove('d-none');

    // 隐藏之前的提示
    document.getElementById('registerAlert').style.display = 'none';

    // 调用注册API
    fetch('/api/front/auth/register', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            username: username,
            email: email,
            password: password,
            password_confirm: confirmPassword
        })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.code === 200) {
            // 注册成功
            showAlert('success', '注册成功！2秒后跳转到登录页面...');

            setTimeout(function() {
                window.location.href = '/login.html';
            }, 2000);
        } else {
            // 注册失败
            showAlert('error', data.message || '注册失败，请稍后重试');
            submitBtn.disabled = false;
            btnText.textContent = '注册';
            btnSpinner.classList.add('d-none');
        }
    })
    .catch(function(error) {
        showAlert('error', '网络错误，请稍后重试');
        console.error('Register error:', error);

        submitBtn.disabled = false;
        btnText.textContent = '注册';
        btnSpinner.classList.add('d-none');
    });
});

function showAlert(type, message) {
    var alert = document.getElementById('registerAlert');
    var icon = alert.querySelector('i');
    var messageEl = document.getElementById('registerAlertMessage');

    // 重置样式
    alert.className = 'alert';

    if (type === 'success') {
        alert.classList.add('alert-success');
        icon.className = 'bi bi-check-circle-fill me-2';
    } else {
        alert.classList.add('alert-danger');
        icon.className = 'bi bi-exclamation-triangle-fill me-2';
    }

    messageEl.textContent = message;
    alert.style.display = 'block';

    // 5秒后自动隐藏
    setTimeout(function() {
        alert.style.display = 'none';
    }, 5000);
}
