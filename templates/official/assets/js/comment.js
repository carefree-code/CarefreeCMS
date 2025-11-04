// comment.js - 评论系统
// 注意：使用传统function语法，避免箭头函数导致模板解析错误

var CommentSystem = {
    articleId: 0,
    page: 1,
    limit: 20,
    total: 0,

    // 初始化评论系统
    init: function(articleId) {
        this.articleId = articleId;
        this.loadComments();
        this.bindEvents();
    },

    // 加载评论列表
    loadComments: function() {
        var self = this;
        var url = '/api/front/comments?article_id=' + this.articleId +
                  '&page=' + this.page + '&limit=' + this.limit;

        fetch(url)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.code === 200) {
                    self.renderComments(data.data.list || []);
                    self.total = data.data.total || 0;
                    self.updateCommentCount();
                }
            })
            .catch(function(error) {
                console.error('Load comments error:', error);
            });
    },

    // 渲染评论列表
    renderComments: function(comments) {
        var container = document.getElementById('commentsList');
        if (!container) return;

        if (comments.length === 0) {
            container.innerHTML = '<div class="empty-state"><p>暂无评论，快来发表第一条评论吧！</p></div>';
            return;
        }

        var html = '';
        for (var i = 0; i < comments.length; i++) {
            html += this.renderCommentItem(comments[i]);
        }

        container.innerHTML = html;
    },

    // 渲染单个评论
    renderCommentItem: function(comment) {
        // 使用在线头像服务作为备用方案
        var defaultAvatar = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(comment.user_name || 'User') + '&size=200&background=6c757d&color=fff';
        var avatar = comment.user_avatar || defaultAvatar;
        var name = comment.is_guest ? comment.user_name : (comment.user_nickname || comment.user_username);
        var time = comment.create_time;
        var content = this.escapeHtml(comment.content);

        var html = '<div class="comment-item" data-id="' + comment.id + '">';
        html += '  <div class="comment-avatar">';
        html += '    <img src="' + avatar + '" alt="' + this.escapeHtml(name) + '">';
        html += '  </div>';
        html += '  <div class="comment-content-wrapper">';
        html += '    <div class="comment-header">';
        html += '      <span class="comment-author">' + this.escapeHtml(name) + '</span>';

        // 管理员标识
        if (comment.is_admin) {
            html += '      <span class="badge bg-danger ms-2">管理员</span>';
        }

        html += '      <span class="comment-time">' + time + '</span>';
        html += '    </div>';
        html += '    <div class="comment-content">' + content + '</div>';
        html += '    <div class="comment-actions">';
        html += '      <button class="btn-like" onclick="CommentSystem.likeComment(' + comment.id + ')">';
        html += '        <i class="bi bi-heart"></i> 点赞 (' + (comment.like_count || 0) + ')';
        html += '      </button>';
        html += '      <button class="btn-reply" onclick="CommentSystem.replyComment(' + comment.id + ', \'' + this.escapeHtml(name) + '\')">';
        html += '        <i class="bi bi-reply"></i> 回复';
        html += '      </button>';
        html += '    </div>';
        html += '  </div>';
        html += '</div>';

        // 渲染子评论
        if (comment.children && comment.children.length > 0) {
            html += '<div class="comment-replies">';
            for (var i = 0; i < comment.children.length; i++) {
                html += this.renderCommentItem(comment.children[i]);
            }
            html += '</div>';
        }

        return html;
    },

    // HTML转义，防止XSS
    escapeHtml: function(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    // 发表评论
    submitComment: function(content, parentId) {
        var token = getUserToken();
        var data = {
            article_id: this.articleId,
            content: content
        };

        if (parentId) {
            data.parent_id = parentId;
        }

        // 如果未登录，需要游客信息
        if (!token) {
            var guestName = prompt('请输入您的昵称：');
            if (!guestName || !guestName.trim()) {
                alert('请输入昵称');
                return;
            }

            var guestEmail = prompt('请输入您的邮箱：');
            if (!guestEmail || !guestEmail.trim()) {
                alert('请输入邮箱');
                return;
            }

            data.user_name = guestName;
            data.user_email = guestEmail;
        }

        var headers = {
            'Content-Type': 'application/json'
        };

        if (token) {
            headers['Authorization'] = 'Bearer ' + token;
        }

        fetch('/api/front/comments', {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(data)
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.code === 200) {
                var message = '评论发表成功';
                if (result.data.status === 0) {
                    message += '，等待审核';
                }
                alert(message);
                CommentSystem.loadComments();

                // 清空输入框
                var textarea = document.getElementById('commentContent');
                if (textarea) textarea.value = '';
            } else {
                alert(result.message || '评论失败');
            }
        })
        .catch(function(error) {
            console.error('Submit comment error:', error);
            alert('评论失败，请稍后重试');
        });
    },

    // 点赞评论
    likeComment: function(commentId) {
        var token = getUserToken();
        if (!token) {
            alert('请先登录');
            window.location.href = '/login.html?redirect=' + encodeURIComponent(window.location.pathname);
            return;
        }

        fetch('/api/front/comments/like', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ comment_id: commentId })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.code === 200) {
                alert('点赞成功');
                CommentSystem.loadComments();
            } else {
                alert(data.message || '点赞失败');
            }
        })
        .catch(function(error) {
            console.error('Like comment error:', error);
            alert('操作失败，请稍后重试');
        });
    },

    // 回复评论
    replyComment: function(commentId, authorName) {
        var content = prompt('回复 @' + authorName + '：');
        if (content && content.trim()) {
            this.submitComment(content.trim(), commentId);
        }
    },

    // 更新评论数
    updateCommentCount: function() {
        var countEl = document.getElementById('commentCount');
        if (countEl) {
            countEl.textContent = this.total;
        }
    },

    // 绑定事件
    bindEvents: function() {
        var submitBtn = document.getElementById('submitCommentBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function() {
                var textarea = document.getElementById('commentContent');
                if (!textarea) return;

                var content = textarea.value.trim();
                if (!content) {
                    alert('请输入评论内容');
                    return;
                }

                if (content.length < 5) {
                    alert('评论内容至少5个字符');
                    return;
                }

                if (content.length > 500) {
                    alert('评论内容不能超过500个字符');
                    return;
                }

                CommentSystem.submitComment(content, 0);
            });
        }
    }
};
