<div id="wooai-widget" class="wooai-closed">
    <button id="wooai-toggle" class="wooai-button" aria-label="Toggle chat">
        <svg class="wooai-icon-open" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <svg class="wooai-icon-close" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
    </button>
    
    <div id="wooai-window" class="wooai-window">
        <div class="wooai-header">
            <div class="wooai-header-info">
                <div class="wooai-brand">
                    <div class="wooai-avatar">W</div>
                    <div>
                        <div class="wooai-title">WooAI Assistant</div>
                        <div class="wooai-status">
                            <span class="wooai-dot"></span>
                            Online • Replies instantly
                        </div>
                    </div>
                </div>
            </div>
            <button id="wooai-close" class="wooai-close-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        
        <div id="wooai-messages" class="wooai-messages">
            <div class="wooai-message wooai-ai">
                <div class="wooai-avatar-sm">W</div>
                <div class="wooai-bubble" id="wooai-greeting"></div>
            </div>
            
            <div class="wooai-quick-actions" id="wooai-actions"></div>
        </div>
        
        <div class="wooai-input-wrap">
            <input type="text" id="wooai-input" placeholder="Ask me anything..." />
            <button id="wooai-mic" class="wooai-mic-btn" aria-label="Voice input">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                    <line x1="12" y1="19" x2="12" y2="23"></line>
                    <line x1="8" y1="23" x2="16" y2="23"></line>
                </svg>
            </button>
            <button id="wooai-send" class="wooai-send-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
        </div>
        
        <div class="wooai-footer">Powered by WooAI Assistant</div>
    </div>
</div>
