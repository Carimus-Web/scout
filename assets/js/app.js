// Helper to escape HTML special characters
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    };
    return text.replace(/[&<>"']/g, (m) => map[m]);
}

// Simple markdown parser for chat messages
function parseMarkdown(text) {
    // Escape HTML first to prevent injection
    text = escapeHtml(text);

    // Bold: **text** or __text__
    text = text.replace(
        /\*\*(.*?)\*\*/g,
        '<strong class="font-semibold">$1</strong>',
    );
    text = text.replace(
        /__(.*?)__/g,
        '<strong class="font-semibold">$1</strong>',
    );

    // Italic: *text* or _text_
    text = text.replace(/\*(.*?)\*/g, '<em class="italic">$1</em>');
    text = text.replace(/_(.*?)_/g, '<em class="italic">$1</em>');

    // Code inline: `text`
    text = text.replace(
        /`(.*?)`/g,
        '<code class="bg-gray-200 px-1 rounded text-xs font-mono">$1</code>',
    );

    // Links: [text](url)
    text = text.replace(
        /\[(.*?)\]\((.*?)\)/g,
        '<a href="$2" target="_blank" class="text-cyan-600 font-semibold underline hover:text-cyan-700 break-all">$1</a>',
    );

    // Numbered lists: 1. text
    text = text.replace(
        /^\d+\.\s+(.*?)$/gm,
        '<li class="ml-4 list-decimal">$1</li>',
    );

    // Bullet lists: - text or * text
    text = text.replace(
        /^[-*]\s+(.*?)$/gm,
        '<li class="ml-4 list-disc">$1</li>',
    );

    // Wrap consecutive list items in <ul>
    text = text.replace(/(<li.*?<\/li>[\s\n]*)+/g, function (match) {
        return '<ul class="space-y-1 my-2">' + match + '</ul>';
    });

    // Line breaks: preserve double newlines as paragraph breaks
    text = text.replace(/\n\n/g, '</p><p class="mt-2">');
    text = '<p>' + text + '</p>';

    // Single newlines become <br>
    text = text.replace(/\n/g, '<br>');

    return text;
}

const app = document.getElementById('sputnik-app');

let messages = [];
let selectedPostType = null;
let draftCreated = false;
let currentPostId = null;
let isLoading = false;

app.innerHTML = `
<div class="flex gap-3 items-stretch">
  <div class="flex flex-col w-full flex-shrink-0 max-w-lg h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 relative overflow-hidden rounded-lg shadow-lg" style="height: 94vh !important;">
    <!-- Header -->
    <div class="text-center px-6 py-4 border-b border-cyan-200/50 bg-white/70 backdrop-blur-sm z-10">
      <h2 class="text-4xl font-bold mb-0">
        <span class="bg-gradient-to-r from-cyan-400 via-cyan-500 to-blue-500 bg-clip-text text-transparent">Sputnik</span>
      </h2>
    </div>
    
    <!-- Selector Phase -->
    <div id="selectorPhase" class="flex flex-col items-center justify-center flex-1 px-3 py-10 z-10">
      <label for="postType" class="block mb-2 font-semibold text-gray-800 text-md">Select Content Type:</label>
      <div class="max-w-md text-center mb-8 text-gray-500 text-xs leading-relaxed">
        Choose the type of page you want to create. Sputnik will use AI to generate a first draft using your site's available content blocks. From there you will be able to customize and refine the content in the WordPress editor.
      </div>
      <select id="postType" class="!w-full !max-w-md !px-4 !py-3 !mb-6 !border-1 !border-gray-100 !rounded-xl !text-md !bg-white !appearance-none !text-gray-900 !cursor-pointer !transition-all !shadow-md !hover:border-cyan-400 !hover:shadow-lg !focus:border-cyan-400 !focus:shadow-xl !focus:outline-none">
        <option value="">Choose a content type...</option>
      </select>
      <button id="selectButton" class="w-full max-w-md px-10 py-3 bg-gradient-to-r from-cyan-400 to-cyan-600 text-white rounded-xl font-bold uppercase tracking-widest transition-all shadow-lg hover:shadow-2xl hover:-translate-y-1 active:translate-y-0 relative overflow-hidden group">
        <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent group-hover:animate-shimmer"></span>
        <span class="relative">Begin</span>
      </button>
    </div>
    
    <!-- Chat Phase -->
    <div id="chatPhase" class="flex flex-col flex-1 bg-transparent overflow-scroll z-10" style="display: none;">
      <div id="typeBadge" class="px-6 py-4 bg-gradient-to-r from-cyan-50/70 to-purple-50/50 border-b border-cyan-200/30 text-xs text-gray-600 font-bold uppercase tracking-widest bg-white/60 backdrop-blur-sm"></div>
      <div id="chat" class="flex-1 overflow-y-auto px-6 py-6 flex flex-col gap-4 bg-transparent"></div>
      <div class="px-6 py-5 border-t border-cyan-200/30 bg-white/70 backdrop-blur-sm z-10">
        <div class="flex gap-3 items-end">
          <div class="flex-1 relative">
            <textarea id="input" class="w-full px-4 py-3 pr-12 border-2 border-gray-300 rounded-xl font-normal text-xs leading-relaxed resize-none max-h-32 bg-white text-gray-900 transition-all shadow-sm hover:border-cyan-400 hover:shadow-md focus:border-cyan-400 focus:shadow-lg focus:outline-none" placeholder="Describe the content you want..."></textarea>
            <button id="send" class="absolute right-3 bottom-3 w-8 h-8 bg-gradient-to-r from-cyan-400 to-cyan-600 text-white rounded-full font-bold text-lg flex items-center justify-center transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0">↑</button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Loading State -->
    <div id="loading" class="hidden items-center justify-center px-5 py-5 text-gray-600 gap-2 z-10">
      <p class="text-sm font-medium tracking-widest">Generating draft...</p>
    </div>
  </div>
  <div id="previewWindow" class="flex flex-1 h-full flex-col items-center justify-center bg-gradient-to-br from-blue-50 via-cyan-50 to-blue-100 relative overflow-hidden rounded-lg shadow-lg" style="height: 94vh !important;">
    <!-- Idle State -->
    <div id="previewIdle" class="flex flex-col items-center justify-center gap-8 px-8 text-center z-10">
      <div class="animate-float">
        <div class="text-8xl drop-shadow-lg">🚀</div>
      </div>
      <div class="space-y-4 max-w-md">
        <h3 class="text-3xl font-bold bg-gradient-to-r from-cyan-600 to-blue-600 bg-clip-text text-transparent">Ready to Create</h3>
        <p class="text-gray-600 leading-relaxed text-sm">Describe your content in the chat panel and Sputnik will use AI to generate a first draft using your site's available blocks.</p>
        <div class="pt-4 space-y-3">
          <p class="text-xs text-gray-500 font-semibold uppercase tracking-widest">✨ Features</p>
          <ul class="text-xs text-gray-700 space-y-2">
            <li class="flex items-center justify-center gap-2"><span>🎨</span> Multi-turn conversations</li>
            <li class="flex items-center justify-center gap-2"><span>⚡</span> AI-powered first drafts</li>
            <li class="flex items-center justify-center gap-2"><span>🔧</span> Full editor customization</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div id="previewLoading" class="hidden flex flex-col items-center justify-center gap-8 z-10 rounded w-full h-full relative overflow-hidden" style="background: linear-gradient(-45deg, #ffffff, #f1f6fd, #e8eff8, #f3f8fc, #eff4fa); background-size: 300% 300%; animation: space-gradient 4s ease infinite;">
      <!-- Animated background elements -->
      <div class="absolute top-0 left-0 w-full h-full overflow-hidden">
        <div style="position: absolute; width: 2px; height: 2px; background: #0ea5e9; border-radius: 50%; opacity: 0.30; left: 10%; top: 20%; animation: floating-stars 15s linear infinite;"></div>
        <div style="position: absolute; width: 1.5px; height: 1.5px; background: #06b6d4; border-radius: 50%; opacity: 0.46; left: 20%; top: 10%; animation: floating-stars 20s linear infinite 2s;"></div>
        <div style="position: absolute; width: 1px; height: 1px; background: #0ea5e9; border-radius: 50%; opacity: 0.44; left: 30%; top: 30%; animation: floating-stars 18s linear infinite 1s;"></div>
        <div style="position: absolute; width: 2px; height: 2px; background: #06b6d4; border-radius: 50%; opacity: 0.49; left: 50%; top: 15%; animation: floating-stars 22s linear infinite 3s;"></div>
        <div style="position: absolute; width: 1.5px; height: 1.5px; background: #0ea5e9; border-radius: 50%; opacity: 0.45; left: 70%; top: 25%; animation: floating-stars 19s linear infinite 2.5s;"></div>
        <div style="position: absolute; width: 1px; height: 1px; background: #06b6d4; border-radius: 50%; opacity: 0.48; left: 80%; top: 40%; animation: floating-stars 17s linear infinite 1.5s;"></div>
      </div>

      <!-- Loading content -->
      <div class="space-y-8 relative z-10">
        <!-- Animated Spinner -->
        <div class="flex justify-center">
          <div class="animate-spin-gentle">
            <svg class="w-16 h-16 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="filter: drop-shadow(0 0 10px rgba(6, 182, 212, 0.4));">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1" opacity="0.3"></circle>
              <path d="M12 2a10 10 0 0 1 7.07 17.07M12 2a10 10 0 0 0 0 20" stroke="currentColor" stroke-width="2"></path>
            </svg>
          </div>
        </div>
        <div class="text-center space-y-2">
          <h3 class="text-lg font-bold text-gray-800">Sputnik is thinking...</h3>
          <p id="loadingStatus" class="text-sm text-gray-600">Generating your first draft</p>
          <div class="flex justify-center gap-1 mt-4">
            <div class="w-2 h-2 bg-cyan-400 rounded-full animate-bounce" style="animation-delay: 0s; box-shadow: 0 0 10px rgba(34, 211, 238, 0.66);"></div>
            <div class="w-2 h-2 bg-cyan-400 rounded-full animate-bounce" style="animation-delay: 0.2s; box-shadow: 0 0 10px rgba(34, 211, 238, 0.66);"></div>
            <div class="w-2 h-2 bg-cyan-400 rounded-full animate-bounce" style="animation-delay: 0.4s; box-shadow: 0 0 10px rgba(34, 211, 238, 0.66);"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div id="previewError" class="hidden flex flex-col items-center justify-center gap-6 px-8 z-10">
      <div class="animate-bounce-gentle">
        <div class="text-7xl drop-shadow-lg">⚠️</div>
      </div>
      <div class="space-y-4 max-w-md text-center">
        <h3 class="text-2xl font-bold text-red-600">Oops!</h3>
        <p id="errorMessage" class="text-sm text-gray-700 leading-relaxed">Something went wrong. Please check your settings and try again.</p>
      </div>
    </div>

    <!-- Background animation elements -->
    <div class="absolute top-10 right-10 w-40 h-40 bg-cyan-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-float" style="animation-delay: 0s;"></div>
    <div class="absolute -bottom-8 left-10 w-40 h-40 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-float" style="animation-delay: 2s;"></div>
    <div class="absolute top-1/2 left-1/2 w-40 h-40 bg-purple-200 rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-float" style="animation-delay: 4s;"></div>
  </div>
</div>
`;

const select = document.getElementById('postType');

SPUTNIK.postTypes.forEach((pt) => {
    const opt = document.createElement('option');
    opt.value = pt.value;
    opt.textContent = pt.label;
    select.appendChild(opt);
});

// Lock post type selection
document.getElementById('selectButton').onclick = () => {
    if (!select.value) {
        alert('Please select a content type');
        return;
    }

    selectedPostType = select.value;
    const selectedLabel = select.options[select.selectedIndex].text;

    document.getElementById('selectorPhase').style.display = 'none';
    document.getElementById('chatPhase').style.display = 'flex';
    document.getElementById('typeBadge').innerHTML =
        `<strong class="text-cyan-600">📄 Content Type:</strong> <span class="text-gray-700">${selectedLabel}</span>`;

    // Initial AI greeting with helpful context
    addMessage(
        'assistant',
        `Perfect! I'll help you create a first draft for a **${selectedLabel}**. Just describe what you'd like this page to include—topics, sections, tone, or any specific information. I'll generate content using only your site's available blocks.`,
    );
};

function addMessage(role, content, isError = false) {
    const chatDiv = document.getElementById('chat');
    const msgDiv = document.createElement('div');

    // Build class list
    let classes = 'flex items-start gap-3 animate-pulse-slow';
    let labelClasses =
        'flex-shrink-0 font-bold text-xs uppercase tracking-widest mt-1';
    let contentClasses =
        'flex-1 leading-relaxed text-xs break-words overflow-hidden';

    // Role-specific styling
    if (role === 'assistant' && !isError) {
        msgDiv.className = classes + ' justify-start';
        labelClasses += ' text-cyan-600';
        contentClasses +=
            ' bg-gradient-to-r from-cyan-50 to-blue-50 border border-cyan-200 border-l-4 border-l-cyan-400 rounded-lg p-3 text-gray-800 shadow-md prose prose-sm max-w-none';

        // Parse markdown for assistant messages
        content = parseMarkdown(content);
    } else if (role === 'user') {
        msgDiv.className = classes + ' justify-end';
        labelClasses += ' text-cyan-700 order-2 ml-3';
        contentClasses +=
            ' order-1 bg-cyan-100/60 border border-cyan-300/50 rounded-lg p-3 text-gray-900 shadow-sm whitespace-pre-wrap word-break break-words';

        // Escape HTML and preserve line breaks for user messages
        content = escapeHtml(content).replace(/\n/g, '<br>');

        // Make links clickable and ensure they wrap
        content = content.replace(
            /https?:\/\/[^\s<]+/g,
            '<a href="$&" target="_blank" class="text-cyan-600 font-semibold underline hover:text-cyan-700 break-all">$&</a>',
        );
    } else if (isError) {
        msgDiv.className = classes + ' justify-start';
        labelClasses += ' text-red-600';
        contentClasses +=
            ' bg-gradient-to-r from-red-50 to-orange-50 border border-red-200 border-l-4 border-l-red-500 rounded-lg p-3 text-red-900 shadow-md';

        // Escape HTML for error messages
        content = escapeHtml(content);
    }

    // Create label
    const label = role === 'assistant' ? 'Sputnik' : 'You';
    const labelEl = document.createElement('strong');
    labelEl.className = labelClasses;
    labelEl.textContent = label;

    // Create content container
    const contentEl = document.createElement('span');
    contentEl.className = contentClasses;
    contentEl.innerHTML = content;

    msgDiv.appendChild(labelEl);
    msgDiv.appendChild(contentEl);
    chatDiv.appendChild(msgDiv);
    chatDiv.scrollTop = chatDiv.scrollHeight;
}

// Helper to safely show loading overlay (works before and after iframe rendered)
function showPreviewLoading() {
    const previewDiv = document.getElementById('previewWindow');
    if (!previewDiv) return;

    // Check if loading element exists
    let loadingEl = document.getElementById('previewLoading');

    // If elements were replaced by iframe, recreate them
    if (!loadingEl) {
        previewDiv.innerHTML = `
            <div id="previewIdle" style="display: none;" class="flex flex-col items-center justify-center gap-8 px-8 text-center z-10"></div>
            <div id="previewLoading" class="flex flex-col items-center justify-center gap-8 z-10 rounded w-full h-full relative overflow-hidden" style="background: linear-gradient(-45deg, #ffffff, #f1f6fd, #e8eff8, #f3f8fc, #eff4fa); background-size: 300% 300%; animation: space-gradient 8s ease infinite;">
                <!-- Animated background elements -->
                <div class="absolute top-0 left-0 w-full h-full overflow-hidden">
                    <div style="position: absolute; width: 2px; height: 2px; background: #0ea5e9; border-radius: 50%; opacity: 0.30; left: 10%; top: 20%; animation: floating-stars 15s linear infinite;"></div>
                    <div style="position: absolute; width: 1.5px; height: 1.5px; background: #06b6d4; border-radius: 50%; opacity: 0.26; left: 20%; top: 10%; animation: floating-stars 20s linear infinite 2s;"></div>
                    <div style="position: absolute; width: 1px; height: 1px; background: #0ea5e9; border-radius: 50%; opacity: 0.24; left: 30%; top: 30%; animation: floating-stars 18s linear infinite 1s;"></div>
                    <div style="position: absolute; width: 2px; height: 2px; background: #06b6d4; border-radius: 50%; opacity: 0.29; left: 50%; top: 15%; animation: floating-stars 22s linear infinite 3s;"></div>
                    <div style="position: absolute; width: 1.5px; height: 1.5px; background: #0ea5e9; border-radius: 50%; opacity: 0.25; left: 70%; top: 25%; animation: floating-stars 19s linear infinite 2.5s;"></div>
                    <div style="position: absolute; width: 1px; height: 1px; background: #06b6d4; border-radius: 50%; opacity: 0.28; left: 80%; top: 40%; animation: floating-stars 17s linear infinite 1.5s;"></div>
                </div>

                <!-- Loading content -->
                <div class="space-y-8 relative z-10">
                    <div class="flex justify-center">
                        <div class="animate-spin-gentle">
                            <svg class="w-16 h-16 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="filter: drop-shadow(0 0 10px rgba(6, 182, 212, 0.4));">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1" opacity="0.3"></circle>
                                <path d="M12 2a10 10 0 0 1 7.07 17.07M12 2a10 10 0 0 0 0 20" stroke="currentColor" stroke-width="2"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="text-center space-y-2">
                        <h3 class="text-lg font-bold text-gray-800">Sputnik is thinking...</h3>
                        <p id="loadingStatus" class="text-sm text-gray-600">Generating your first draft</p>
                        <div class="flex justify-center gap-1 mt-4">
                            <div class="w-2 h-2 bg-cyan-400 rounded-full animate-bounce" style="animation-delay: 0s; box-shadow: 0 0 10px rgba(34, 211, 238, 0.66);"></div>
                            <div class="w-2 h-2 bg-cyan-400 rounded-full animate-bounce" style="animation-delay: 0.2s; box-shadow: 0 0 10px rgba(34, 211, 238, 0.66);"></div>
                            <div class="w-2 h-2 bg-cyan-400 rounded-full animate-bounce" style="animation-delay: 0.4s; box-shadow: 0 0 10px rgba(34, 211, 238, 0.66);"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="previewError" style="display: none;" class="flex flex-col items-center justify-center gap-6 px-8 z-10"></div>
        `;
        loadingEl = document.getElementById('previewLoading');
    }

    // Hide other states and show loading
    const idle = document.getElementById('previewIdle');
    const error = document.getElementById('previewError');
    if (idle) idle.style.display = 'none';
    if (error) error.style.display = 'none';
    if (loadingEl) loadingEl.style.display = 'flex';
}

// Helper to safely hide loading state
function hidePreviewLoading() {
    const loadingEl = document.getElementById('previewLoading');
    if (loadingEl) loadingEl.style.display = 'none';
}

async function submitMessage() {
    const input = document.getElementById('input');
    const text = input.value.trim();

    if (!text) {
        return;
    }

    addMessage('user', text);
    messages.push({ role: 'user', content: text });

    const sendBtn = document.getElementById('send');
    const input_area = document.getElementById('input');
    sendBtn.disabled = true;
    input_area.disabled = true;
    const originalText = sendBtn.textContent;
    sendBtn.textContent = '⏳';

    // Show loading state (safely handles both initial and refinement states)
    isLoading = true;
    showPreviewLoading();

    const res = await fetch(SPUTNIK.api, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            messages,
            postType: selectedPostType,
        }),
    });

    sendBtn.disabled = false;
    input_area.disabled = false;
    sendBtn.textContent = '↑';
    isLoading = false;

    // Handle HTTP errors
    if (!res.ok) {
        addMessage(
            'assistant',
            `Server error: HTTP ${res.status}. Please try again or check your configuration in Sputnik → Settings.`,
            true,
        );
        hidePreviewLoading();
        const errorEl = document.getElementById('previewError');
        if (errorEl) {
            errorEl.style.display = 'flex';
            const errorMsg = document.getElementById('errorMessage');
            if (errorMsg)
                errorMsg.textContent = `Server error: HTTP ${res.status}. Please check your settings.`;
        }
        input.value = '';
        return;
    }

    let data;
    try {
        data = await res.json();
    } catch (e) {
        addMessage(
            'assistant',
            'Error parsing server response. Please check Sputnik Settings and try again.',
            true,
        );
        hidePreviewLoading();
        const errorEl = document.getElementById('previewError');
        if (errorEl) {
            errorEl.style.display = 'flex';
            const errorMsg = document.getElementById('errorMessage');
            if (errorMsg)
                errorMsg.textContent =
                    'Error parsing server response. Please try again.';
        }
        input.value = '';
        return;
    }

    // Handle API errors
    if (!data || data.error) {
        let errorMessage = data?.error || 'Unknown error occurred';
        errorMessage = escapeHtml(errorMessage);

        // Add helpful context for common errors
        if (
            errorMessage.includes('API key') ||
            errorMessage.includes('not configured')
        ) {
            errorMessage +=
                '<br><br><em class="block mt-3 italic text-red-700 text-xs">💡 <strong class="font-semibold">Tip:</strong> Go to <a href="' +
                SPUTNIK.settingsUrl +
                '" target="_blank" class="text-cyan-600 font-bold underline hover:text-cyan-700"><strong class="font-bold">Sputnik → Settings</strong></a> to configure your AI provider and API key.</em>';
        } else if (errorMessage.includes('theme configuration')) {
            errorMessage +=
                '<br><br><em class="block mt-3 italic text-red-700 text-xs">💡 <strong class="font-semibold">Tip:</strong> Make sure the Carimus Backbone theme is active and properly configured.</em>';
        }

        addMessage('assistant', errorMessage, true);
        hidePreviewLoading();
        const errorEl = document.getElementById('previewError');
        if (errorEl) {
            errorEl.style.display = 'flex';
            const errorMsg = document.getElementById('errorMessage');
            if (errorMsg)
                errorMsg.textContent =
                    data?.error || 'An error occurred. Please try again.';
        }
        input.value = '';
        return;
    }

    // Handle successful response
    if (data.reply) {
        messages.push(data.reply);

        // Check if this is a draft completion (backend already created page)
        if (data.complete && data.edit_url) {
            addMessage(
                'assistant',
                '✓ Draft page created! Opening in the editor now...',
            );
            draftCreated = true;
            setTimeout(() => {
                window.location.href = data.edit_url;
            }, 1500);
            input.value = '';
            document.getElementById('previewLoading').style.display = 'none';
            document.getElementById('previewIdle').style.display = 'flex';
            return;
        }

        // Check if response is a JSON layout (page layout as string)
        let replyContent = data.reply.content;
        let isLayoutJson = false;
        let layoutData = null;

        try {
            let cleanContent = replyContent.trim();

            // Remove markdown code fence markers (```json ... ```)
            cleanContent = cleanContent.replace(/^```[\w]*\n?/i, ''); // Remove opening ```json or ```
            cleanContent = cleanContent.replace(/\n?```$/i, ''); // Remove closing ```

            // Also handle inline backticks
            cleanContent = cleanContent.replace(/^`[\w]*\n?/i, ''); // Remove opening backtick
            cleanContent = cleanContent.replace(/\n?`$/i, ''); // Remove closing backtick

            cleanContent = cleanContent.trim();

            // Check if content looks like JSON (starts with { or [)
            if (!cleanContent.match(/^\s*[\{\[]/)) {
                throw new Error('Not JSON format');
            }

            layoutData = JSON.parse(cleanContent);
            if (
                layoutData &&
                layoutData.layout &&
                Array.isArray(layoutData.layout)
            ) {
                isLayoutJson = true;
            } else {
                console.log(
                    'Parsed JSON but no layout array found:',
                    Object.keys(layoutData || {}),
                );
            }
        } catch (e) {
            // Not JSON, treat as regular message
            console.log('Reply is not JSON:', e.message);
            console.log(
                'First 200 chars of content:',
                replyContent.substring(0, 200),
            );
        }

        if (isLayoutJson) {
            // Show loading state for page creation
            showPreviewLoading();
            const statusEl = document.getElementById('loadingStatus');
            if (statusEl) statusEl.textContent = 'Creating your page...';

            createPageWithBlocks(layoutData.layout, selectedPostType);
        } else {
            // Regular message response
            addMessage('assistant', data.reply.content);
            hidePreviewLoading();
            const idle = document.getElementById('previewIdle');
            if (idle) idle.style.display = 'flex';
        }

        input.value = '';
    }
}

// Create or update WordPress page with blocks from layout JSON
async function createPageWithBlocks(layout, postType) {
    try {
        const endpoint = SPUTNIK.api.replace('/chat', '/create-page');

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                layout: layout,
                postType: postType,
                post_id: currentPostId, // Send existing post ID if refining
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const result = await response.json();
        if (result.error) {
            addMessage(
                'assistant',
                `Error creating page: ${result.error}`,
                true,
            );
            hidePreviewLoading();
            const errorEl = document.getElementById('previewError');
            if (errorEl) {
                errorEl.style.display = 'flex';
                const errorMsg = document.getElementById('errorMessage');
                if (errorMsg) errorMsg.textContent = result.error;
            }
            return;
        }

        // Page created successfully - store the post ID for future refinements
        draftCreated = true;
        currentPostId = result.post_id;

        // Show preview instead of redirecting
        renderPreviewIframe(result.post_url, result.edit_url);

        hidePreviewLoading();
    } catch (error) {
        addMessage('assistant', `Error creating page: ${error.message}`, true);
        hidePreviewLoading();
        const errorEl = document.getElementById('previewError');
        if (errorEl) {
            errorEl.style.display = 'flex';
            const errorMsg = document.getElementById('errorMessage');
            if (errorMsg) errorMsg.textContent = error.message;
        }
    }
}

// Render preview using iframe of the actual draft post
function renderPreviewIframe(postUrl, editUrl) {
    const previewDiv = document.getElementById('previewWindow');
    const previewIdle = document.getElementById('previewIdle');

    // Null checks to prevent "Cannot read properties of null" errors
    if (!previewDiv || !previewIdle) {
        addMessage(
            'assistant',
            'Error: Preview elements not found in page. Please refresh and try again.',
            true,
        );
        return;
    }

    if (!postUrl || !editUrl) {
        addMessage(
            'assistant',
            `Error: Invalid page URLs returned from server. Post URL: ${postUrl}, Edit URL: ${editUrl}`,
            true,
        );
        return;
    }

    previewIdle.style.display = 'none';

    // Build iframe preview
    let previewHTML = `
        <div class="flex flex-col w-full h-full bg-white">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-cyan-50 to-blue-50">
                <div>
                    <p class="text-xs text-gray-500 font-semibold uppercase tracking-widest">Preview</p>
                    <h2 class="text-lg font-bold text-gray-900">Draft Page</h2>
                </div>
                <div class="flex gap-2">
                    <button onclick="document.getElementById('previewFrame').contentWindow.location.reload()" class="px-3 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-semibold text-sm transition-all">
                        ↻ Refresh
                    </button>
                    <button onclick="openPageInEditor('${editUrl}')" class="px-4 py-2 bg-gradient-to-r from-cyan-400 to-cyan-600 text-white rounded-lg font-semibold text-sm transition-all hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0">
                        Edit in WordPress
                    </button>
                </div>
            </div>
            
            <!-- iframe container with responsive scaling for 1440px desktop preview -->
            <div class="flex-1 overflow-auto bg-gray-100 flex items-stretch justify-center">
                <div class="w-full h-full" id="iframeScaler">
                    <iframe id="previewFrame" src="${postUrl}" class="border-0 w-full" style="transform-origin: left top !important;"></iframe>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="px-6 py-3 border-t border-gray-200 bg-gray-50 text-center">
                <p class="text-xs text-gray-600">Previewing your draft page at 1440px width. Content scales to fit. Click "Refresh" to reload or "Edit in WordPress" to make changes.</p>
            </div>
        </div>
    `;

    previewDiv.innerHTML = previewHTML;

    // Set up iframe scaling to maintain 1440px width within parent container
    setTimeout(() => {
        const scaler = document.getElementById('iframeScaler');
        const frame = document.getElementById('previewFrame');
        if (scaler && frame) {
            const calculateScale = () => {
                const parentWidth = scaler.clientWidth;
                const parentHeight = scaler.clientHeight;
                const targetWidth = 1440;
                const scale = parentWidth / targetWidth;
                // Calculate height so that when scaled, it fills the parent height
                const unscaledHeight = parentHeight / Math.max(scale, 0.1);
                frame.style.width = targetWidth + 'px';
                frame.style.height = unscaledHeight + 'px';
                frame.style.transform = `scale(${Math.min(scale, 1)})`;
                frame.style.transformOrigin = 'left top';
            };
            calculateScale();
            window.addEventListener('resize', calculateScale);
        }
    }, 100);

    // Show success message after preview is set up
    addMessage(
        'assistant',
        '✓ Done! Page preview loaded. Click "Edit in WordPress" to make changes or continue chatting to refine it.',
    );
}

function openPageInEditor(editUrl) {
    window.location.href = editUrl;
}

// Send button click handler
document.getElementById('send').onclick = submitMessage;

// Textarea Enter key handler (Shift+Enter for soft returns)
document.getElementById('input').onkeydown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        submitMessage();
    }
};
