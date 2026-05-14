<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Online Chat</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Auth Screen -->
    <div id="auth-screen">
        <div class="auth-card">
            <div style="margin-bottom: 20px;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="12" fill="#3390ec"/>
                    <path d="M6.5 12L9.5 14.5L17.5 7.5" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <div id="login-form">
                <h2>Sign In</h2>
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" id="login-user" placeholder="Enter username">
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" id="login-pass" placeholder="Enter password">
                </div>
                <button class="btn" onclick="login()">Log In</button>
                <div class="toggle-link" onclick="toggleAuth('register')">Create an account</div>
            </div>

            <div id="register-form" class="hidden">
                <h2>Sign Up</h2>
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" id="reg-user" placeholder="Choose username">
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" id="reg-pass" placeholder="Choose password">
                </div>
                <button class="btn" onclick="register()">Register</button>
                <div class="toggle-link" onclick="toggleAuth('login')">Back to Login</div>
            </div>
        </div>
    </div>

    <!-- Main App -->
    <div id="app-layout" class="hidden">
        <!-- Sidebar -->
        <div id="sidebar">
            <div class="sidebar-header">Chats</div>
            <div class="user-list" id="user-list">
                <div style="padding:20px; text-align:center; color:#888; font-size:0.9rem;">Loading users...</div>
            </div>
            <div style="padding: 10px; border-top: 1px solid var(--tg-border);">
                <div class="user-item" onclick="logout()">
                    <div class="user-avatar" style="background:#ff5b5b">X</div>
                    <div class="user-info">Logout</div>
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <div id="chat-area">
            <div id="empty-view" class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <p style="margin-top:10px">Select a user to chat</p>
            </div>

            <div id="active-chat-view" style="display:none; height:100%; flex-direction:column;">
                <header>
                    <div style="display:flex; align-items:center;">
                        <div class="back-btn" onclick="closeChat()">
                            <svg class="icon" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
                        </div>
                        <div class="user-avatar-sm" id="header-avatar" style="margin-right:10px"></div>
                        <span id="header-username">User</span>
                    </div>
                </header>

                <main id="chat-container"></main>

                <footer>
                    <textarea id="msg-input" placeholder="Message..." rows="1"></textarea>
                    <button id="send-btn" onclick="sendMessage()">
                        <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                </footer>
            </div>
        </div>
    </div>

    <script>
        let currentUser = null;
        let currentTarget = null;
        let lastMessageTime = 0;
        let pollInterval = null;
        let usersCache = [];

        // Detect mobile device
        function isMobile() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }

        // RTL DETECTION HELPER
        function isPersian(text) {
            // Range for Persian, Arabic, and associated characters
            const persianRegex = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/;
            return persianRegex.test(text);
        }

        // Handle back button on mobile
        function handleBackButton() {
            if (isMobile() && currentTarget) {
                // If on mobile and in a chat, close chat instead of going back
                closeChat();
                // Prevent default back behavior by adding to history
                history.pushState(null, null, location.href);
            }
        }

        // Listen for browser back button
        window.addEventListener('popstate', function(event) {
            if (isMobile() && currentTarget) {
                closeChat();
                // Push a new state to stay on the page
                history.pushState(null, null, location.href);
            }
        });

        // Auto resize & RTL Toggle
        const tx = document.getElementsByTagName("textarea");
        for (let i = 0; i < tx.length; i++) {
            tx[i].setAttribute("style", "height:" + (tx[i].scrollHeight) + "px;overflow-y:hidden;");
            tx[i].addEventListener("input", function() {
                this.style.height = "auto";
                this.style.height = (this.scrollHeight) + "px";

                // Check for RTL
                if (isPersian(this.value)) {
                    this.setAttribute("dir", "rtl");
                    this.style.textAlign = "right";
                } else {
                    this.setAttribute("dir", "ltr");
                    this.style.textAlign = "left";
                }
            }, false);
        }

        // Helper function to escape HTML and preserve newlines
        function formatMessageText(text) {
            // Escape HTML special characters
            let escaped = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            // Replace newlines with <br> tags
            escaped = escaped.replace(/\n/g, '<br>');

            return escaped;
        }

        // --- AUTH ---
        function toggleAuth(s) {
            document.getElementById('login-form').classList.toggle('hidden', s !== 'login');
            document.getElementById('register-form').classList.toggle('hidden', s !== 'register');
        }

        async function checkSession() {
            try {
                const res = await fetch('api.php?action=me').then(r => r.json());
                if(res.success) {
                    currentUser = res.user;
                    initApp();
                } else {
                    document.getElementById('auth-screen').classList.remove('hidden');
                }
            } catch (e) { document.getElementById('auth-screen').classList.remove('hidden'); }
        }

        async function register() {
            const u = document.getElementById('reg-user').value.trim();
            const p = document.getElementById('reg-pass').value.trim();
            if(!u || !p) return alert('Fill all fields');
            const res = await fetch('api.php?action=register', { method: 'POST', body: JSON.stringify({username: u, password: p}) }).then(r => r.json());
            if(res.success) { currentUser = { username: res.username, color: res.color }; initApp(); } else { alert(res.message); }
        }

        async function login() {
            const u = document.getElementById('login-user').value.trim();
            const p = document.getElementById('login-pass').value.trim();
            if(!u || !p) return alert('Fill all fields');
            const res = await fetch('api.php?action=login', { method: 'POST', body: JSON.stringify({username: u, password: p}) }).then(r => r.json());
            if(res.success) { currentUser = { username: res.username, color: res.color }; initApp(); } else { alert(res.message); }
        }

        async function logout() { await fetch('api.php?action=logout'); window.location.reload(); }

        // --- APP ---
        function initApp() {
            document.getElementById('auth-screen').classList.add('hidden');
            document.getElementById('app-layout').classList.remove('hidden');
            requestNotificationPermission();
            loadUsers();
            setInterval(loadUsers, 5000);

            // Initialize history state for back button handling
            if (isMobile()) {
                history.pushState(null, null, location.href);
            }
        }

        async function loadUsers() {
            try {
                const res = await fetch('api.php?action=users');
                const data = await res.json();
                renderUserList(data);
            } catch (e) { console.error(e); }
        }

        function renderUserList(users) {
            const list = document.getElementById('user-list');
            list.innerHTML = '';
            if (users.length === 0) { list.innerHTML = '<div style="padding:20px; text-align:center; color:#888;">No other users.</div>'; return; }
            users.forEach(u => {
                const div = document.createElement('div');
                div.className = `user-item ${currentTarget === u.username ? 'active' : ''}`;
                div.onclick = () => openChat(u);
                let badgeHtml = u.unread > 0 ? `<div class="badge">${u.unread}</div>` : '';
                div.innerHTML = `
                    <div class="user-avatar" style="background:${u.avatar_color}">${u.username.substring(0,2).toUpperCase()}</div>
                    <div class="user-info"><div class="user-name">${u.username}</div></div>
                    ${badgeHtml}
                `;
                list.appendChild(div);
            });
            usersCache = users;
        }

        async function openChat(targetUser) {
            currentTarget = targetUser.username;
            document.getElementById('empty-view').style.display = 'none';
            document.getElementById('active-chat-view').style.display = 'flex';
            if(window.innerWidth <= 768) document.getElementById('chat-area').classList.add('active');
            document.getElementById('header-username').textContent = targetUser.username;
            const av = document.getElementById('header-avatar');
            av.style.backgroundColor = targetUser.avatar_color;
            av.textContent = targetUser.username.substring(0,2).toUpperCase();
            document.getElementById('chat-container').innerHTML = '';
            lastMessageTime = 0;

            await fetch('api.php?action=read', { method: 'POST', body: JSON.stringify({ target: currentTarget }) });
            loadUsers();
            if(pollInterval) clearInterval(pollInterval);
            pollMessages();
            pollInterval = setInterval(pollMessages, 3000);

            // Push new history state for back button handling on mobile
            if (isMobile()) {
                history.pushState({chat: currentTarget}, null, location.href);
            }
        }

        function closeChat() {
            document.getElementById('chat-area').classList.remove('active');
            setTimeout(() => {
                currentTarget = null;
                if(pollInterval) clearInterval(pollInterval);
                renderUserList(usersCache);
                document.getElementById('active-chat-view').style.display = 'none';
                document.getElementById('empty-view').style.display = 'flex';
            }, 300);

            // Clear the history state when closing chat
            if (isMobile()) {
                setTimeout(() => {
                    history.pushState(null, null, location.href);
                }, 100);
            }
        }

        async function sendMessage() {
            const input = document.getElementById('msg-input');
            const text = input.value;
            if(!text.trim() || !currentTarget) return;

            // Preserve the original text (including newlines) for sending
            input.value = '';
            input.style.height = 'auto';
            input.setAttribute("dir", "ltr"); // Reset input

            await fetch('api.php?action=send', {
                method: 'POST',
                body: JSON.stringify({ target: currentTarget, text: text })
            });
            pollMessages();
        }

        async function pollMessages() {
            if(!currentTarget) return;
            try {
                const res = await fetch(`api.php?action=poll&target=${currentTarget}&last=${lastMessageTime}`);
                const msgs = await res.json();
                if(msgs.length > 0) {
                    renderMessages(msgs);
                    loadUsers();
                }
            } catch(e) { console.error(e); }
        }

        function renderMessages(msgs) {
            const container = document.getElementById('chat-container');
            let shouldScroll = container.scrollTop + container.clientHeight >= container.scrollHeight - 50;

            msgs.forEach(m => {
                lastMessageTime = m.time;
                const isMe = m.sender === currentUser.username;
                const isRTL = isPersian(m.text);

                const div = document.createElement('div');
                div.className = `message ${isMe ? 'out' : 'in'} ${isRTL ? 'rtl-msg' : ''}`;
                div.setAttribute('dir', isRTL ? 'rtl' : 'ltr');

                // Format message with newlines preserved
                const formattedMessage = formatMessageText(m.text);

                div.innerHTML = `
                    <div class="message-text">${formattedMessage}</div>
                    <div class="msg-meta">
                        <span class="msg-time">${new Date(m.time * 1000).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                    </div>
                `;
                container.appendChild(div);

                if(!isMe && (document.hidden || (window.innerWidth <= 768 && !document.getElementById('chat-area').classList.contains('active')))) {
                    sendNotification(m.sender, m.text);
                }
            });

            if(shouldScroll) container.scrollTop = container.scrollHeight;
        }

        function requestNotificationPermission() {
            if ("Notification" in window && Notification.permission !== "granted") Notification.requestPermission();
        }

        function sendNotification(title, body) {
            if (Notification.permission === "granted") {
                try { const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2354/2354-preview.mp3'); audio.volume = 0.5; audio.play().catch(e => {}); } catch(e) {}
                new Notification("New Message from " + title, { body: body, icon: 'https://cdn-icons-png.flaticon.com/512/1041/1041916.png' });
            }
        }

        // Handle textarea input based on device
        const msgInput = document.getElementById('msg-input');
        if (msgInput) {
            msgInput.addEventListener('keydown', function(e) {
                if (isMobile()) {
                    // On mobile: Enter creates newline, no automatic send
                    if (e.key === 'Enter') {
                        // Allow default behavior - insert newline
                        return;
                    }
                } else {
                    // On desktop: Enter sends, Shift+Enter creates newline
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                }
            });
        }

        checkSession();
    </script>
</body>
</html>
