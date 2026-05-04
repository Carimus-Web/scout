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
<div class="sputnik-container">
  <div class="sputnik-header">
    <h2>✦ Sputnik ✦</h2>
    <p class="sputnik-subtitle">AI Content Draft Generator</p>
  </div>
  
  <div class="sputnik-selector" id="selectorPhase">
    <label for="postType">Select Content Type:</label>
    <div class="sputnik-selector-hint">
      Choose the type of page you want to create. Sputnik will use AI to generate a first draft using your site's available content blocks.
    </div>
    <select id="postType">
      <option value="">Choose a content type...</option>
    </select>
    <button id="selectButton">Begin</button>
  </div>
  
  <div class="sputnik-chat" id="chatPhase" style="display: none;">
    <div class="sputnik-type-badge" id="typeBadge"></div>
    <div id="chat" class="sputnik-messages"></div>
    <div class="sputnik-input-area">
      <div class="sputnik-input-wrapper">
        <textarea id="input" placeholder="Describe the content you want..."></textarea>
        <button id="send">↓</button>
      </div>
    </div>
  </div>
  
  <div class="sputnik-loading" id="loading" style="display: none;">
    <p>Generating draft...</p>
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
    document.getElementById('chatPhase').style.display = 'block';
    document.getElementById('typeBadge').innerHTML =
        `<strong>📄 Content Type:</strong> ${selectedLabel}`;

    // Initial AI greeting with helpful context
    addMessage(
        'assistant',
        `Perfect! I'll help you create a first draft for a <strong>${selectedLabel}</strong>. Just describe what you'd like this page to include—topics, sections, tone, or any specific information. I'll generate content using only your site's available blocks.`,
    );
};

function addMessage(role, content, isError = false) {
    const chatDiv = document.getElementById('chat');
    const msgDiv = document.createElement('div');
    msgDiv.className = `sputnik-message sputnik-${role}${isError ? ' sputnik-error' : ''}`;

    const label = role === 'assistant' ? 'Sputnik' : 'You';
    const labelEl = document.createElement('strong');
    labelEl.textContent = label;

    const contentEl = document.createElement('span');
    contentEl.innerHTML = content; // Allow HTML for tips/formatting

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
    sendBtn.textContent = '⏳';

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
    sendBtn.textContent = originalText;

    if (data.error) {
        let errorMessage = escapeHtml(data.error);

        // Add helpful context for common errors
        if (data.error.includes('API key')) {
            errorMessage +=
                '<br><br><em>💡 <strong>Tip:</strong> Go to <a href="' +
                SPUTNIK.settingsUrl +
                '" target="_blank"><strong>Sputnik → Settings</strong></a> to configure your AI provider and API key.</em>';
        } else if (data.error.includes('theme configuration')) {
            errorMessage +=
                '<br><br><em>💡 <strong>Tip:</strong> Make sure the Carimus Backbone theme is active and properly configured.</em>';
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
