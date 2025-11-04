/**
 * members.js - 会员中心页面脚本
 */

function switchRank(type) {
    // 切换按钮状态
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');

    // 切换显示内容
    if (type === 'points') {
        document.getElementById('points-rank').style.display = 'block';
        document.getElementById('vip-rank').style.display = 'none';
    } else if (type === 'vip') {
        document.getElementById('points-rank').style.display = 'none';
        document.getElementById('vip-rank').style.display = 'block';
    }
}
