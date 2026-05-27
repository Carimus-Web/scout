import { escapeHtml, parseMarkdown } from './templates/utils.js';
import { getMainLayoutHTML } from './templates/mainLayout.js';
import {
    getPreviewIdleHTML,
    getPreviewLoadingHTML,
    getPreviewErrorHTML,
    getPreviewRecreateHTML,
} from './templates/previewStates.js';
import {
    getPreviewIframeHTML,
    setupIframeScaling,
} from './templates/previewIframe.js';

const app = document.getElementById('scout-app');

let messages = [];
let selectedPostType = null;
let draftCreated = false;
let currentPostId = null;
let isLoading = false;

// Initialize main layout
app.innerHTML = getMainLayoutHTML();

// Initialize preview area with ONLY idle state
const previewWindow = document.getElementById('previewWindow');
previewWindow.innerHTML = getPreviewIdleHTML();

const select = document.getElementById('postType');

SCOUT.postTypes.forEach((pt) => {
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
    let classes = 'flex flex-col gap-1 animate-pulse-slow';
    let labelClasses =
        'flex-shrink-0 font-bold text-xs uppercase tracking-widest mt-1';
    let contentClasses =
        'flex-1 leading-relaxed text-xs break-words overflow-hidden w-11/12';

    // Role-specific styling
    if (role === 'assistant' && !isError) {
        msgDiv.className = classes + ' justify-start items-start';
        contentClasses +=
            ' bg-gradient-to-r from-cyan-50 to-blue-50 border border-cyan-200 border-l-4 border-l-cyan-400 rounded-lg p-3 text-gray-800 shadow-md prose prose-sm max-w-none ';
        labelClasses += ' text-cyan-600';

        // Parse markdown for assistant messages
        content = parseMarkdown(content);
    } else if (role === 'user') {
        msgDiv.className = classes + ' justify-end items-end';
        contentClasses +=
            ' order-1 bg-gray-100/60 border border-gray-300/50 rounded-lg p-3 text-gray-900 shadow-sm whitespace-pre-wrap word-break break-words';
        labelClasses += ' text-cyan-700 order-2 ml-3';

        // Escape HTML and preserve line breaks for user messages
        content = escapeHtml(content).replace(/\n/g, '<br>');

        // Make links clickable and ensure they wrap
        content = content.replace(
            /https?:\/\/[^\s<]+/g,
            '<a href="$&" target="_blank" class="text-cyan-600 font-semibold underline hover:text-cyan-700 break-all">$&</a>',
        );
    } else if (isError) {
        msgDiv.className = classes + ' justify-start items-start';
        contentClasses +=
            ' bg-gradient-to-r from-red-50 to-orange-50 border border-red-200 border-l-4 border-l-red-500 rounded-lg p-3 text-red-900 shadow-md';
        labelClasses += ' text-red-600';

        // Escape HTML for error messages
        content = escapeHtml(content);
    }

    // Create label
    const label = role === 'assistant' ? 'Scout' : 'You';
    const labelEl = document.createElement('strong');
    labelEl.className = labelClasses;
    labelEl.textContent = label;

    // Create content container
    const contentEl = document.createElement('span');
    contentEl.className = contentClasses;
    contentEl.innerHTML = content;

    msgDiv.appendChild(contentEl);
    msgDiv.appendChild(labelEl);
    chatDiv.appendChild(msgDiv);
    chatDiv.scrollTop = chatDiv.scrollHeight;
}

// Helper to safely show loading overlay (works before and after iframe rendered)
function showPreviewLoading() {
    const previewDiv = document.getElementById('previewWindow');
    if (!previewDiv) return;

    let loadingEl = document.getElementById('previewLoading');
    let errorEl = document.getElementById('previewError');

    // If loading/error elements don't exist, create them fresh
    if (!loadingEl || !errorEl) {
        // Clear current preview content and rebuild with loading/error states
        previewDiv.innerHTML = getPreviewLoadingHTML() + getPreviewErrorHTML();
        loadingEl = document.getElementById('previewLoading');
        errorEl = document.getElementById('previewError');
    }

    // Hide other states and show loading
    const idle = document.getElementById('previewIdle');
    const iframe = document.getElementById('previewFrame');
    if (idle) idle.style.display = 'none';
    if (iframe) iframe.parentElement.style.display = 'none';
    if (errorEl) errorEl.style.display = 'none';
    if (loadingEl) loadingEl.style.display = 'flex';
}

// Helper to safely hide loading state
function hidePreviewLoading() {
    const loadingEl = document.getElementById('previewLoading');
    if (loadingEl) loadingEl.style.display = 'none';
}

// Helper to safely show error state
function showPreviewError(errorMessage) {
    const previewDiv = document.getElementById('previewWindow');
    if (!previewDiv) return;

    let errorEl = document.getElementById('previewError');
    let loadingEl = document.getElementById('previewLoading');

    // If error element doesn't exist, add it
    if (!errorEl) {
        const idle = document.getElementById('previewIdle');
        if (idle) {
            idle.insertAdjacentHTML('afterend', getPreviewErrorHTML());
        }
        errorEl = document.getElementById('previewError');
    }

    // Hide other states and show error
    const idle = document.getElementById('previewIdle');
    if (idle) idle.style.display = 'none';
    if (loadingEl) loadingEl.style.display = 'none';
    if (errorEl) {
        errorEl.style.display = 'flex';
        const errorMsg = document.getElementById('errorMessage');
        if (errorMsg) errorMsg.textContent = errorMessage;
    }
}

// Helper to safely show idle state
function showPreviewIdle() {
    const idle = document.getElementById('previewIdle');
    const loading = document.getElementById('previewLoading');
    const error = document.getElementById('previewError');

    if (idle) idle.style.display = 'flex';
    if (loading) loading.style.display = 'none';
    if (error) error.style.display = 'none';
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

    const res = await fetch(SCOUT.api, {
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
        const errorMsg = `Server error: HTTP ${res.status}. Please try again or check your configuration in Scout → Settings.`;
        addMessage('assistant', errorMsg, true);
        hidePreviewLoading();
        showPreviewError(
            `Server error: HTTP ${res.status}. Please check your settings.`,
        );
        input.value = '';
        return;
    }

    let data;
    try {
        data = await res.json();
    } catch (e) {
        addMessage(
            'assistant',
            'Error parsing server response. Please check Scout Settings and try again.',
            true,
        );
        hidePreviewLoading();
        showPreviewError('Error parsing server response. Please try again.');
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
                SCOUT.settingsUrl +
                '" target="_blank" class="text-cyan-600 font-bold underline hover:text-cyan-700"><strong class="font-bold">Scout → Settings</strong></a> to configure your AI provider and API key.</em>';
        } else if (errorMessage.includes('theme configuration')) {
            errorMessage +=
                '<br><br><em class="block mt-3 italic text-red-700 text-xs">💡 <strong class="font-semibold">Tip:</strong> Make sure the Carimus Backbone theme is active and properly configured.</em>';
        }

        addMessage('assistant', errorMessage, true);
        hidePreviewLoading();
        showPreviewError(data?.error || 'An error occurred. Please try again.');
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
            showPreviewIdle();
            return;
        }

        // Check if response is a JSON layout (page layout as string)
        let replyContent = data.reply.content;
        let isLayoutJson = false;
        let layoutData = null;

        try {
            let cleanContent = replyContent.trim();

            // First, try to extract JSON block from markdown code fences
            let jsonMatch = cleanContent.match(/```[\w]*\n?([\s\S]*?)\n?```/);
            if (jsonMatch && jsonMatch[1]) {
                cleanContent = jsonMatch[1].trim();
            } else {
                // Try single backticks as fallback
                jsonMatch = cleanContent.match(/`([\s\S]*?)`/);
                if (jsonMatch && jsonMatch[1]) {
                    cleanContent = jsonMatch[1].trim();
                } else {
                    // Try to find JSON object anywhere in the message using regex
                    jsonMatch = cleanContent.match(
                        /\{[\s\S]*"layout"[\s\S]*\}/,
                    );
                    if (jsonMatch) {
                        cleanContent = jsonMatch[0];
                    }
                }
            }

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
            }
        } catch (e) {
            // Not JSON, treat as regular message
        }

        if (isLayoutJson) {
            // Show loading state for page creation
            showPreviewLoading();
            const statusEl = document.getElementById('loadingStatus');
            if (statusEl) statusEl.textContent = 'Creating your page...';

            createPageWithBlocks(layoutData.layout, selectedPostType, layoutData.title);
        } else {
            // Regular message response
            addMessage('assistant', data.reply.content);
            hidePreviewLoading();
            showPreviewIdle();
        }

        input.value = '';
    }
}

// Create or update WordPress page with blocks from layout JSON
async function createPageWithBlocks(layout, postType, title) {
    try {
        const endpoint = SCOUT.api.replace('/chat', '/create-page');

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                layout: layout,
                postType: postType,
                title: title,
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
            showPreviewError(result.error);
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
        showPreviewError(error.message);
    }
}

// Render preview using iframe of the actual draft post
function renderPreviewIframe(postUrl, editUrl) {
    const previewDiv = document.getElementById('previewWindow');

    // Null check to prevent "Cannot read properties of null" errors
    if (!previewDiv) {
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

    // Hide all states and clear preview
    const idle = document.getElementById('previewIdle');
    const loading = document.getElementById('previewLoading');
    const error = document.getElementById('previewError');
    if (idle) idle.style.display = 'none';
    if (loading) loading.style.display = 'none';
    if (error) error.style.display = 'none';

    // Build iframe preview using template
    previewDiv.innerHTML = getPreviewIframeHTML(postUrl, editUrl);

    // Set up iframe scaling
    setupIframeScaling();

    // Show success message after preview is set up
    addMessage(
        'assistant',
        '✓ Done! Page preview loaded. Click "Edit in WordPress" to make changes or continue chatting to refine it.',
    );
}

function openPageInEditor(editUrl) {
    window.open(editUrl, '_blank');
}

// Make function globally accessible for inline onclick handlers
window.openPageInEditor = openPageInEditor;

// Send button click handler
document.getElementById('send').onclick = submitMessage;

// Textarea Enter key handler (Shift+Enter for soft returns)
document.getElementById('input').onkeydown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        submitMessage();
    }
};
