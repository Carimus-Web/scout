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
<div class="flex flex-col w-full max-w-4xl mx-auto h-[calc(100vh-120px)] bg-gradient-to-br from-slate-50 via-white to-blue-50 relative overflow-hidden">
  <div class="text-center px-6 py-8 border-b border-cyan-200/50 glass z-10">
    <h2 class="text-3xl font-bold gradient-text mb-2">✦ Sputnik ✦</h2>
    <p class="text-gray-600 text-sm tracking-wide">AI Content Draft Generator</p>
  </div>
  
  <div class="flex flex-col items-center justify-center flex-1 px-5 py-10 z-10" id="selectorPhase">
    <label for="postType" class="block mb-3 font-semibold text-gray-800 text-base">Select Content Type:</label>
    <div class="max-w-md text-center mb-8 text-gray-700 text-sm leading-relaxed">
      Choose the type of page you want to create. Sputnik will use AI to generate a first draft using your site's available content blocks.
    </div>
    <select id="postType" class="w-full max-w-sm px-4 py-3.5 mb-5 border-2 border-gray-300 rounded-xl text-base bg-white text-gray-900 cursor-pointer transition-all shadow-sm hover:border-cyan-400 hover:shadow-[0_0_20px_rgba(0,217,255,0.15)] focus:border-cyan-400 focus:shadow-[0_0_20px_rgba(0,217,255,0.25)] focus:outline-none">
      <option value="">Choose a content type...</option>
    </select>
    <button id="selectButton" class="w-full max-w-sm px-10 py-3.5 bg-gradient-to-r from-cyan-400 to-cyan-500 text-white rounded-xl font-bold uppercase tracking-wider transition-all shadow-lg hover:shadow-2xl hover:-translate-y-0.5 active:translate-y-0 relative overflow-hidden">
      <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/15 to-transparent animate-shine"></span>
      <span class="relative">Begin</span>
    </button>
  </div>
  
  <div class="flex flex-col flex-1 bg-transparent overflow-hidden z-10" id="chatPhase" style="display: none;">
    <div class="px-6 py-4 bg-gradient-to-r from-cyan-50/50 to-purple-50/30 border-b border-cyan-200/30 text-xs text-gray-600 font-bold uppercase tracking-wider glass" id="typeBadge"></div>
    <div id="chat" class="flex-1 overflow-y-auto px-6 py-6 flex flex-col gap-4 bg-transparent"></div>
    <div class="px-6 py-5 border-t border-cyan-200/30 bg-gradient-to-r from-slate-100/40 to-white/50 flex gap-2.5 items-end glass z-10">
      <div class="flex-1 relative">
        <textarea id="input" class="w-full px-4 py-3.5 pr-14 border-2 border-gray-300 rounded-xl font-normal text-sm leading-relaxed resize-none max-h-[120px] min-h-[44px] bg-white text-gray-900 transition-all shadow-sm hover:border-cyan-400 hover:shadow-[0_0_20px_rgba(0,217,255,0.15)] focus:border-cyan-400 focus:shadow-[0_0_20px_rgba(0,217,255,0.25)] focus:outline-none" placeholder="Describe the content you want..."></textarea>
        <button id="send" class="absolute right-3 bottom-3 w-8 h-8 p-0 bg-gradient-to-r from-cyan-400 to-cyan-500 text-white rounded-lg font-bold text-lg flex items-center justify-center transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 relative overflow-hidden disabled:opacity-60 disabled:cursor-not-allowed">
          <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/15 to-transparent animate-shine"></span>
          <span class="relative">↓</span>
        </button>
      </div>
    </div>
  </div>
  
  <div class="hidden items-center justify-center px-5 py-5 text-gray-600 gap-2 z-10" id="loading">
    <p class="text-sm font-medium tracking-wide">Generating draft...</p>
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
        `<strong class="text-cyan-500">📄 Content Type:</strong> <span class="text-gray-700">${selectedLabel}</span>`;

    // Initial AI greeting with helpful context
    addMessage(
        'assistant',
        `Perfect! I'll help you create a first draft for a <strong class="text-cyan-500 font-semibold">${selectedLabel}</strong>. Just describe what you'd like this page to include—topics, sections, tone, or any specific information. I'll generate content using only your site's available blocks.`,
    );
};

function addMessage(role, content, isError = false) {
    const chatDiv = document.getElementById('chat');
    const msgDiv = document.createElement('div');

    // Base message classes
    let baseClasses = 'flex items-start gap-3 animate-slide-in';
    let labelClasses =
        'flex-shrink-0 font-bold text-xs uppercase tracking-wider mt-1';
    let contentClasses = 'flex-1 leading-relaxed text-sm break-words';

    // Role-specific classes
    if (role === 'assistant') {
        msgDiv.classList.add(...baseClasses.split(' '), 'justify-start');
        labelClasses += ' text-cyan-500';
        contentClasses += ' message-assistant';
    } else if (role === 'user') {
        msgDiv.classList.add(...baseClasses.split(' '), 'justify-end');
        labelClasses += ' text-cyan-600 order-2 ml-2';
        contentClasses += ' message-user order-1';
    }

    if (isError) {
        msgDiv.classList.add('justify-start');
        labelClasses = labelClasses.replace('text-cyan-500', 'text-red-600');
        contentClasses = contentClasses.replace(
            'message-assistant',
            'message-error',
        );
    }

    msgDiv.className = msgDiv.className + ' ' + baseClasses;
    if (role === 'assistant' && !isError) {
        msgDiv.className = msgDiv.className.replace(
            baseClasses,
            baseClasses + ' justify-start',
        );
    } else if (role === 'user') {
        msgDiv.className = msgDiv.className.replace(
            baseClasses,
            baseClasses + ' justify-end',
        );
    } else if (isError) {
        msgDiv.className = msgDiv.className.replace(
            baseClasses,
            baseClasses + ' justify-start',
        );
    }

    const label = role === 'assistant' ? 'Sputnik' : 'You';
    const labelEl = document.createElement('strong');
    labelEl.className = labelClasses;
    labelEl.textContent = label;

    const contentEl = document.createElement('span');
    contentEl.className = contentClasses;
    contentEl.innerHTML = content;

    msgDiv.appendChild(labelEl);
    msgDiv.appendChild(contentEl);

    chatDiv.appendChild(msgDiv);
    chatDiv.scrollTop = chatDiv.scrollHeight;
}

document.getElementById('send').onclick = async () => {
    if (draftCreated) {
        alert('Draft created! Preparing editor...');
        return;
    }

    const input = document.getElementById('input');
    const text = input.value.trim();

    if (!text) {
        alert('Please describe what you want in your page');
        return;
    }

    if (!selectedPostType) {
        alert('No content type selected');
        return;
    }

    addMessage('user', text);
    messages.push({ role: 'user', content: text });

    const sendBtn = document.getElementById('send');
    const input_area = document.getElementById('input');
    sendBtn.disabled = true;
    input_area.disabled = true;
    const originalText = sendBtn.textContent;
    sendBtn.innerHTML =
        '<span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/15 to-transparent animate-shine"></span><span class="relative">⏳</span>';

    const res = await fetch(SPUTNIK.api, {
        method: 'POST',
        body: JSON.stringify({
            messages,
            postType: selectedPostType,
        }),
    });

    const data = await res.json();
    sendBtn.disabled = false;
    input_area.disabled = false;
    sendBtn.innerHTML =
        '<span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/15 to-transparent animate-shine"></span><span class="relative">↓</span>';

    if (data.error) {
        let errorMessage = escapeHtml(data.error);

        // Add helpful context for common errors
        if (data.error.includes('API key')) {
            errorMessage +=
                '<br><br><em class="block mt-2.5 italic text-red-600 text-xs">💡 <strong class="font-semibold">Tip:</strong> Go to <a href="' +
                SPUTNIK.settingsUrl +
                '" target="_blank" class="text-cyan-500 font-bold no-underline hover:underline"><strong class="font-bold">Sputnik → Settings</strong></a> to configure your AI provider and API key.</em>';
        } else if (data.error.includes('theme configuration')) {
            errorMessage +=
                '<br><br><em class="block mt-2.5 italic text-red-600 text-xs">💡 <strong class="font-semibold">Tip:</strong> Make sure the Carimus Backbone theme is active and properly configured.</em>';
        }

        addMessage('assistant', errorMessage, true);
        return;
    }

    messages.push(data.reply);
    addMessage('assistant', data.reply.content);
    input.value = '';

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
};
