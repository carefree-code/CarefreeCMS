/**
 * Main.js - 全局JavaScript
 * 包含AOS动画初始化、导航栏效果、回到顶部等功能
 */

// 初始化AOS动画
AOS.init({
    duration: 1000,
    once: true,
    offset: 100
});

// 导航栏滚动效果
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    const backToTop = document.querySelector('.back-to-top');

    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
        if (backToTop) {
            backToTop.classList.add('show');
        }
    } else {
        navbar.classList.remove('scrolled');
        if (backToTop) {
            backToTop.classList.remove('show');
        }
    }
});

// 回到顶部
const backToTopBtn = document.querySelector('.back-to-top');
if (backToTopBtn) {
    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// 平滑滚动
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
