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

const app = document.getElementById('sputnik-app');

let messages = [];
let selectedPostType = null;
let draftCreated = false;

app.innerHTML = `
<div class="flex flex-col w-full max-w-xl h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 relative overflow-hidden rounded-lg shadow-lg" style="height: 90vh !important;">
  <!-- Header -->
  <div class="text-center px-6 py-4 border-b border-cyan-200/50 bg-white/70 backdrop-blur-sm z-10">
    <h2 class="text-4xl font-bold mb-0">
      <span class="bg-gradient-to-r from-cyan-400 via-cyan-500 to-blue-500 bg-clip-text text-transparent">Sputnik</span>
    </h2>
  </div>
  
  <!-- Selector Phase -->
  <div id="selectorPhase" class="flex flex-col items-center justify-center flex-1 px-5 py-10 z-10">
    <label for="postType" class="block mb-4 font-semibold text-gray-800 text-lg">Select Content Type:</label>
    <div class="max-w-md text-center mb-8 text-gray-700 text-sm leading-relaxed">
      Choose the type of page you want to create. Sputnik will use AI to generate a first draft using your site's available content blocks.
    </div>
    <select id="postType" class="!w-full !max-w-md !px-4 !py-3 !mb-6 !border-1 !border-gray-100 !rounded-xl !text-base !bg-white !appearance-none !text-gray-900 !cursor-pointer !transition-all !shadow-md !hover:border-cyan-400 !hover:shadow-lg !focus:border-cyan-400 !focus:shadow-xl !focus:outline-none">
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
          <textarea id="input" class="w-full px-4 py-3 pr-12 border-2 border-gray-300 rounded-xl font-normal text-sm leading-relaxed resize-none max-h-32 bg-white text-gray-900 transition-all shadow-sm hover:border-cyan-400 hover:shadow-md focus:border-cyan-400 focus:shadow-lg focus:outline-none" placeholder="Describe the content you want..."></textarea>
          <button id="send" class="absolute right-3 bottom-3 w-8 h-8 bg-gradient-to-r from-cyan-400 to-cyan-600 text-white rounded-full font-bold text-lg flex items-center justify-center transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed" disabled>↑</button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Loading State -->
  <div id="loading" class="hidden items-center justify-center px-5 py-5 text-gray-600 gap-2 z-10">
    <p class="text-sm font-medium tracking-widest">Generating draft...</p>
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
        `Perfect! I'll help you create a first draft for a <strong class="text-cyan-600">${selectedLabel}</strong>. Just describe what you'd like this page to include—topics, sections, tone, or any specific information. I'll generate content using only your site's available blocks.`,
    );
};

function addMessage(role, content, isError = false) {
    const chatDiv = document.getElementById('chat');
    const msgDiv = document.createElement('div');

    // Build class list
    let classes = 'flex items-start gap-3 animate-pulse-slow';
    let labelClasses =
        'flex-shrink-0 font-bold text-xs uppercase tracking-widest mt-1';
    let contentClasses = 'flex-1 leading-relaxed text-sm break-words';

    // Role-specific styling
    if (role === 'assistant' && !isError) {
        msgDiv.className = classes + ' justify-start';
        labelClasses += ' text-cyan-600';
        contentClasses +=
            ' bg-gradient-to-r from-cyan-50 to-blue-50 border border-cyan-200 border-l-4 border-l-cyan-400 rounded-lg p-3.5 text-gray-800 shadow-md';
    } else if (role === 'user') {
        msgDiv.className = classes + ' justify-end';
        labelClasses += ' text-cyan-700 order-2 ml-3';
        contentClasses +=
            ' order-1 bg-cyan-100/60 border border-cyan-300/50 rounded-lg p-3.5 text-gray-900 shadow-sm';
    } else if (isError) {
        msgDiv.className = classes + ' justify-start';
        labelClasses += ' text-red-600';
        contentClasses +=
            ' bg-gradient-to-r from-red-50 to-orange-50 border border-red-200 border-l-4 border-l-red-500 rounded-lg p-3.5 text-red-900 shadow-md';
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

async function submitMessage() {
    if (draftCreated) {
        alert('Draft created! Preparing editor...');
        return;
    }

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

    // Handle HTTP errors
    if (!res.ok) {
        addMessage(
            'assistant',
            `Server error: HTTP ${res.status}. Please try again or check your configuration in Sputnik → Settings.`,
            true,
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
            'Error parsing server response. Please check Sputnik Settings and try again.',
            true,
        );
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
        input.value = '';
        return;
    }

    // Handle successful response
    if (data.reply) {
        messages.push(data.reply);
        addMessage('assistant', data.reply.content);
        input.value = '';
    }

    if (data.complete) {
        draftCreated = true;
        addMessage(
            'assistant',
            '✦ Draft created successfully! Opening editor...',
        );
        setTimeout(() => {
            window.location.href = data.edit_url;
        }, 2000);
    }
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
