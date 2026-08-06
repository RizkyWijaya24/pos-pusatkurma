{{-- resources/views/shop/partials/chatbot.blade.php --}}
<div id="shop-chatbot-widget" class="shop-chatbot-widget">
    {{-- Floating Toggle Button --}}
    <button type="button" id="chatbot-toggle-btn" class="chatbot-toggle-btn" aria-label="Tanya Asisten Pusat Kurma" title="Tanya Asisten Pusat Kurma">
        <div class="chatbot-badge-pulse"></div>
        <span class="chatbot-icon-closed">
            <i class="fa-solid fa-comments"></i>
        </span>
        <span class="chatbot-icon-opened" style="display: none;">
            <i class="fa-solid fa-xmark"></i>
        </span>
        <span class="chatbot-btn-label">Tanya Asisten</span>
    </button>

    {{-- Chat Window Container --}}
    <div id="chatbot-window" class="chatbot-window" style="display: none;">
        {{-- Header --}}
        <div class="chatbot-header">
            <div class="chatbot-header-info">
                <div class="chatbot-avatar">
                    <i class="fa-solid fa-robot"></i>
                    <span class="chatbot-status-online" title="Online"></span>
                </div>
                <div class="chatbot-header-text">
                    <h4>Asisten Pusat Kurma</h4>
                    <p><span class="online-dot"></span> Siap memberi saran & bantuan</p>
                </div>
            </div>
            <div class="chatbot-header-actions">
                <button type="button" id="chatbot-reset-btn" class="chatbot-header-btn" title="Mulai Ulang Chat">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
                <button type="button" id="chatbot-close-btn" class="chatbot-header-btn" title="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        {{-- Messages Body --}}
        <div id="chatbot-messages" class="chatbot-messages">
            {{-- Welcome Message --}}
            <div class="chat-msg msg-bot">
                <div class="msg-avatar"><i class="fa-solid fa-robot"></i></div>
                <div class="msg-content">
                    <div class="msg-bubble">
                        Assalamualaikum! 👋 Selamat datang di <strong>Pusat Kurma Cianjur</strong>.<br><br>
                        Saya adalah **Asisten Rekomendasi**. Bingung memilih kurma yang tepat untuk keluarga, bumil, atau oleh-oleh? Silakan ketik pertanyaan Anda atau pilih topik di bawah ini:
                    </div>
                    <span class="msg-time">{{ date('H:i') }}</span>
                </div>
            </div>
        </div>

        {{-- Quick Reply Chips --}}
        <div id="chatbot-quick-replies" class="chatbot-quick-replies">
            <button type="button" class="chip-btn" data-query="Rekomendasi Kurma">🌴 Rekomendasi Kurma</button>
            <button type="button" class="chip-btn" data-query="Ibu Hamil & Promil">🥑 Ibu Hamil & Promil</button>
            <button type="button" class="chip-btn" data-query="Oleh-oleh & Hampers">🎁 Oleh-oleh & Hampers</button>
            <button type="button" class="chip-btn" data-query="Kurma Ajwa">👑 Kurma Ajwa</button>
            <button type="button" class="chip-btn" data-query="Kurma Sukari">🍯 Kurma Sukari</button>
            <button type="button" class="chip-btn" data-query="Lokasi Toko">📍 Lokasi Toko</button>
            <button type="button" class="chip-btn" data-query="Info Ongkir">🚚 Info Ongkir</button>
        </div>

        {{-- Footer Input Form --}}
        <form id="chatbot-form" class="chatbot-footer" onsubmit="submitChatbotMessage(event)">
            <input type="text" id="chatbot-input" class="chatbot-input" placeholder="Tulis pertanyaan / minta saran..." autocomplete="off" maxlength="200">
            <button type="submit" id="chatbot-send-btn" class="chatbot-send-btn" aria-label="Kirim">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<style>
/* ═════════════════════════════════════════════════════════════
   CHATBOT WIDGET STYLES — Emerald Gold Theme
   ═════════════════════════════════════════════════════════════ */
.shop-chatbot-widget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    font-family: var(--font-body, 'Outfit', sans-serif);
}

/* Floating Action Button */
.chatbot-toggle-btn {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    border-radius: 99px;
    background: linear-gradient(135deg, #065f46 0%, #059669 100%);
    color: #ffffff;
    border: 2px solid rgba(251, 191, 36, 0.4);
    box-shadow: 0 8px 25px rgba(6, 95, 70, 0.4);
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.chatbot-toggle-btn:hover {
    transform: translateY(-3px) scale(1.03);
    box-shadow: 0 12px 30px rgba(6, 95, 70, 0.5);
    background: linear-gradient(135deg, #044e39 0%, #047857 100%);
}
.chatbot-badge-pulse {
    position: absolute;
    top: -3px;
    right: -3px;
    width: 14px;
    height: 14px;
    background-color: #fbbf24;
    border: 2px solid #065f46;
    border-radius: 50%;
    animation: chatbot-pulse 2s infinite;
}
@keyframes chatbot-pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(251, 191, 36, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(251, 191, 36, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(251, 191, 36, 0); }
}
.chatbot-icon-closed, .chatbot-icon-opened {
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.chatbot-btn-label {
    font-weight: 700;
    font-size: 14px;
    letter-spacing: 0.3px;
}

/* Chat Window Modal */
.chatbot-window {
    position: fixed;
    bottom: 85px;
    right: 24px;
    width: 380px;
    max-width: calc(100vw - 32px);
    height: min(540px, calc(100vh - 105px));
    max-height: calc(100vh - 105px);
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.22);
    border: 1px solid rgba(6, 95, 70, 0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: chatbot-pop-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
@keyframes chatbot-pop-in {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* Window Header */
.chatbot-header {
    background: linear-gradient(135deg, #022c22 0%, #065f46 100%);
    color: #ffffff;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(251, 191, 36, 0.2);
}
.chatbot-header-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.chatbot-avatar {
    position: relative;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    border: 1.5px solid #fbbf24;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #fbbf24;
}
.chatbot-status-online {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 10px;
    height: 10px;
    background: #10b981;
    border: 2px solid #022c22;
    border-radius: 50%;
}
.chatbot-header-text h4 {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    line-height: 1.2;
}
.chatbot-header-text p {
    margin: 2px 0 0 0;
    font-size: 11px;
    color: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    gap: 5px;
}
.online-dot {
    width: 6px;
    height: 6px;
    background: #10b981;
    border-radius: 50%;
    display: inline-block;
}
.chatbot-header-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}
.chatbot-header-btn {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: #ffffff;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: background 0.2s;
}
.chatbot-header-btn:hover {
    background: rgba(255, 255, 255, 0.25);
}

/* Chat Messages Container */
.chatbot-messages {
    flex: 1;
    padding: 16px;
    overflow-y: auto;
    background: #fdfaf5;
    display: flex;
    flex-direction: column;
    gap: 14px;
    scroll-behavior: smooth;
}
.chatbot-messages::-webkit-scrollbar { width: 5px; }
.chatbot-messages::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }

/* Messages Bubble */
.chat-msg {
    display: flex;
    gap: 10px;
    max-width: 88%;
}
.chat-msg.msg-bot {
    align-self: flex-start;
}
.chat-msg.msg-user {
    align-self: flex-end;
    flex-direction: row-reverse;
}
.msg-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #065f46;
    color: #fbbf24;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
    margin-top: 2px;
}
.msg-content {
    display: flex;
    flex-direction: column;
}
.msg-bubble {
    padding: 11px 15px;
    border-radius: 16px;
    font-size: 13.5px;
    line-height: 1.5;
    word-break: break-word;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.msg-bot .msg-bubble {
    background: #ffffff;
    color: #1f2937;
    border-top-left-radius: 4px;
    border: 1px solid #e5e7eb;
}
.msg-user .msg-bubble {
    background: linear-gradient(135deg, #065f46, #059669);
    color: #ffffff;
    border-top-right-radius: 4px;
}
.msg-time {
    font-size: 10px;
    color: #9ca3af;
    margin-top: 4px;
}
.msg-user .msg-time { text-align: right; }

/* Typing Indicator */
.typing-bubble {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 12px 16px;
}
.typing-dot {
    width: 6px;
    height: 6px;
    background: #059669;
    border-radius: 50%;
    animation: typing-bounce 1.4s infinite ease-in-out both;
}
.typing-dot:nth-child(1) { animation-delay: -0.32s; }
.typing-dot:nth-child(2) { animation-delay: -0.16s; }
@keyframes typing-bounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

/* Action Button inside Chat */
.chat-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
    padding: 9px 16px;
    background: #25d366;
    color: #ffffff;
    font-weight: 700;
    font-size: 12.5px;
    border-radius: 10px;
    text-decoration: none;
    transition: background 0.2s;
    box-shadow: 0 3px 10px rgba(37, 211, 102, 0.3);
}
.chat-action-btn:hover { background: #128c7e; color: #fff; }

/* Product Cards Container inside Chat */
.chat-products-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 10px;
}
.chat-product-card {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 8px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
}
.chat-product-card:hover {
    border-color: #059669;
    transform: translateX(3px);
    box-shadow: 0 4px 12px rgba(6, 95, 70, 0.1);
}
.chat-prod-img {
    width: 52px;
    height: 52px;
    border-radius: 8px;
    object-fit: cover;
    background: #f3f4f6;
    flex-shrink: 0;
}
.chat-prod-img-placeholder {
    width: 52px;
    height: 52px;
    border-radius: 8px;
    background: #d1fae5;
    color: #065f46;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.chat-prod-info {
    flex: 1;
    min-width: 0;
}
.chat-prod-name {
    font-size: 12.5px;
    font-weight: 700;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.chat-prod-price {
    font-size: 12px;
    font-weight: 800;
    color: #d97706;
    margin-top: 2px;
}
.chat-prod-btn {
    padding: 6px 10px;
    background: #065f46;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    border-radius: 6px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Quick Reply Chips */
.chatbot-quick-replies {
    display: flex;
    gap: 6px;
    padding: 10px 14px;
    overflow-x: auto;
    background: #ffffff;
    border-top: 1px solid #f3f4f6;
    scroll-behavior: smooth;
}
.chatbot-quick-replies::-webkit-scrollbar { height: 4px; }
.chatbot-quick-replies::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }
.chip-btn {
    white-space: nowrap;
    padding: 6px 12px;
    background: #f1f5f0;
    border: 1px solid #d1d5db;
    border-radius: 99px;
    font-size: 11.5px;
    font-weight: 600;
    color: #065f46;
    cursor: pointer;
    transition: all 0.2s ease;
}
.chip-btn:hover {
    background: #065f46;
    color: #ffffff;
    border-color: #065f46;
}

/* Input Footer */
.chatbot-footer {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: #ffffff;
    border-top: 1px solid #e5e7eb;
}
.chatbot-input {
    flex: 1;
    padding: 10px 14px;
    border: 1.5px solid #e5e7eb;
    border-radius: 99px;
    font-size: 13px;
    outline: none;
    transition: border-color 0.2s;
    font-family: inherit;
}
.chatbot-input:focus {
    border-color: #059669;
}
.chatbot-send-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #065f46, #059669);
    color: #ffffff;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: transform 0.2s, background 0.2s;
    flex-shrink: 0;
}
.chatbot-send-btn:hover {
    transform: scale(1.06);
    background: linear-gradient(135deg, #044e39, #047857);
}

/* Responsive adjustment for small mobile screens */
@media (max-width: 480px) {
    .shop-chatbot-widget {
        bottom: 16px;
        right: 16px;
    }
    .chatbot-window {
        position: fixed;
        bottom: 72px;
        right: 12px;
        left: 12px;
        width: auto;
        max-width: none;
        height: min(480px, calc(100vh - 88px));
        max-height: calc(100vh - 88px);
    }
    .chatbot-btn-label {
        display: none;
    }
    .chatbot-toggle-btn {
        padding: 14px;
        border-radius: 50%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('chatbot-toggle-btn');
    const closeBtn = document.getElementById('chatbot-close-btn');
    const resetBtn = document.getElementById('chatbot-reset-btn');
    const windowEl = document.getElementById('chatbot-window');
    const messagesEl = document.getElementById('chatbot-messages');
    const inputEl = document.getElementById('chatbot-input');
    const iconClosed = toggleBtn.querySelector('.chatbot-icon-closed');
    const iconOpened = toggleBtn.querySelector('.chatbot-icon-opened');
    const quickRepliesContainer = document.getElementById('chatbot-quick-replies');

    // CSRF Token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Toggle Chat Window
    function toggleChatbot() {
        const isOpen = windowEl.style.display !== 'none';
        if (isOpen) {
            windowEl.style.display = 'none';
            iconClosed.style.display = 'flex';
            iconOpened.style.display = 'none';
        } else {
            windowEl.style.display = 'flex';
            iconClosed.style.display = 'none';
            iconOpened.style.display = 'flex';
            inputEl.focus();
            scrollToBottom();
        }
    }

    toggleBtn.addEventListener('click', toggleChatbot);
    closeBtn.addEventListener('click', toggleChatbot);

    // Reset Chat
    resetBtn.addEventListener('click', function () {
        messagesEl.innerHTML = `
            <div class="chat-msg msg-bot">
                <div class="msg-avatar"><i class="fa-solid fa-robot"></i></div>
                <div class="msg-content">
                    <div class="msg-bubble">
                        Assalamualaikum! 👋 Chat telah diperbarui.<br>Silakan tanyakan sesuatu atau pilih topik di bawah ini:
                    </div>
                    <span class="msg-time">${getCurrentTime()}</span>
                </div>
            </div>
        `;
        renderQuickReplies(['🌴 Rekomendasi Kurma', '🥑 Ibu Hamil & Promil', '🎁 Oleh-oleh & Hampers', '📍 Lokasi Toko', '🚚 Info Ongkir']);
    });

    // Quick Reply Buttons Listener
    quickRepliesContainer.addEventListener('click', function (e) {
        const chip = e.target.closest('.chip-btn');
        if (chip) {
            const query = chip.getAttribute('data-query');
            if (query) {
                sendMessage(query);
            }
        }
    });

    // Helper formatting markdown-like bold (**text**) & links ([text](url))
    function formatMessageText(text) {
        if (!text) return '';
        let formatted = text.replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank" style="color:#059669;font-weight:700;text-decoration:underline;">$1 <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i></a>');
        formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        formatted = formatted.replace(/_(.*?)_/g, '<em>$1</em>');
        formatted = formatted.replace(/\n/g, '<br>');
        return formatted;
    }

    function getCurrentTime() {
        const now = new Date();
        return now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    }

    function scrollToBottom() {
        setTimeout(() => {
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }, 50);
    }

    // Append User Message to UI
    function appendUserMessage(text) {
        const timeStr = getCurrentTime();
        const html = `
            <div class="chat-msg msg-user">
                <div class="msg-content">
                    <div class="msg-bubble">${escapeHtml(text)}</div>
                    <span class="msg-time">${timeStr}</span>
                </div>
            </div>
        `;
        messagesEl.insertAdjacentHTML('beforeend', html);
        scrollToBottom();
    }

    // Append Typing Indicator
    function showTypingIndicator() {
        const id = 'typing-indicator-' + Date.now();
        const html = `
            <div class="chat-msg msg-bot" id="${id}">
                <div class="msg-avatar"><i class="fa-solid fa-robot"></i></div>
                <div class="msg-content">
                    <div class="msg-bubble typing-bubble">
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                    </div>
                </div>
            </div>
        `;
        messagesEl.insertAdjacentHTML('beforeend', html);
        scrollToBottom();
        return id;
    }

    function removeTypingIndicator(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    // Escape HTML string
    function escapeHtml(str) {
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    // Render Quick Replies Chips
    function renderQuickReplies(replies) {
        if (!replies || replies.length === 0) return;
        quickRepliesContainer.innerHTML = replies.map(r => `
            <button type="button" class="chip-btn" data-query="${escapeHtml(r.replace(/^[^\w\s]+/, '').trim())}">${escapeHtml(r)}</button>
        `).join('');
    }

    // Append Bot Message to UI
    function appendBotMessage(data) {
        const timeStr = getCurrentTime();

        let productsHtml = '';
        if (data.products && data.products.length > 0) {
            productsHtml = `<div class="chat-products-list">` + data.products.map(p => `
                <a href="${p.detail_url}" class="chat-product-card" target="_blank">
                    ${p.image_url
                        ? `<img src="${p.image_url}" alt="${escapeHtml(p.name)}" class="chat-prod-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                           <div class="chat-prod-img-placeholder" style="display:none;"><i class="fa-solid fa-seedling"></i></div>`
                        : `<div class="chat-prod-img-placeholder"><i class="fa-solid fa-seedling"></i></div>`
                    }
                    <div class="chat-prod-info">
                        <div class="chat-prod-name">${escapeHtml(p.name)}</div>
                        <div class="chat-prod-price">${p.formatted_price} <span style="font-size:10px;color:#6b7280;font-weight:400;">/ ${p.price_unit}</span></div>
                    </div>
                    <div class="chat-prod-btn">Detail <i class="fa-solid fa-chevron-right" style="font-size:9px;"></i></div>
                </a>
            `).join('') + `</div>`;
        }

        let actionBtnHtml = '';
        if (data.action_buttons && Array.isArray(data.action_buttons) && data.action_buttons.length > 0) {
            actionBtnHtml = `<div style="display:flex; flex-direction:column; gap:6px; margin-top:8px;">` + 
                data.action_buttons.map(btn => `
                    <a href="${btn.url}" target="_blank" class="chat-action-btn" style="margin-top:0;">
                        <i class="${btn.icon || 'fa-solid fa-map-location-dot'}"></i> ${escapeHtml(btn.label)}
                    </a>
                `).join('') + `</div>`;
        } else if (data.action_button) {
            actionBtnHtml = `
                <a href="${data.action_button.url}" target="_blank" class="chat-action-btn">
                    <i class="${data.action_button.icon || 'fa-solid fa-arrow-right'}"></i> ${escapeHtml(data.action_button.label)}
                </a>
            `;
        }

        const formattedReply = formatMessageText(data.reply);

        const html = `
            <div class="chat-msg msg-bot">
                <div class="msg-avatar"><i class="fa-solid fa-robot"></i></div>
                <div class="msg-content">
                    <div class="msg-bubble">
                        ${formattedReply}
                        ${actionBtnHtml}
                        ${productsHtml}
                    </div>
                    <span class="msg-time">${timeStr}</span>
                </div>
            </div>
        `;

        messagesEl.insertAdjacentHTML('beforeend', html);
        scrollToBottom();

        if (data.quick_replies) {
            renderQuickReplies(data.quick_replies);
        }
    }

    // Core Send Message AJAX Logic
    window.sendMessage = function (messageText) {
        const text = messageText || inputEl.value.trim();
        if (!text) return;

        appendUserMessage(text);
        inputEl.value = '';

        const typingId = showTypingIndicator();

        fetch('/shop/chatbot/query', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ message: text })
        })
        .then(res => {
            if (!res.ok) throw new Error('Gagal menghubungi asisten');
            return res.json();
        })
        .then(data => {
            removeTypingIndicator(typingId);
            appendBotMessage(data);
        })
        .catch(err => {
            removeTypingIndicator(typingId);
            appendBotMessage({
                reply: 'Mohon maaf, terjadi kendala koneksi. Anda juga dapat langsung bertanya ke Customer Service kami via WhatsApp.',
                action_button: {
                    label: 'Chat WhatsApp CS',
                    url: 'https://wa.me/6281234567890?text=Halo%20Admin%20Pusat%20Kurma',
                    icon: 'fa-brands fa-whatsapp'
                },
                quick_replies: ['Rekomendasi Kurma', 'Cek Ongkir']
            });
        });
    };

    // Form Submit Handler
    window.submitChatbotMessage = function (e) {
        e.preventDefault();
        sendMessage();
    };
});
</script>
