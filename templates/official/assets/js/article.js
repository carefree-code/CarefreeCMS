/**
 * article.js - 文章详情页面脚本
 */

// 复制链接
document.querySelector('.share-btn.link').addEventListener('click', function(e) {
    e.preventDefault();
    var url = window.location.href;
    navigator.clipboard.writeText(url).then(function() {
        alert('链接已复制到剪贴板！');
    });
});

// 初始化评论系统
document.addEventListener('DOMContentLoaded', function() {
    // 从评论区元素获取文章ID
    var commentsSection = document.getElementById('comments');
    if (commentsSection) {
        var articleId = parseInt(commentsSection.getAttribute('data-article-id'));
        if (articleId && typeof CommentSystem !== 'undefined') {
            CommentSystem.init(articleId);
        }
    }
});
