const app = document.getElementById('sputnik-app');

let messages = [];
let selectedPostType = null;
let draftCreated = false;

app.innerHTML = `
<div class="sputnik-container">
  <div class="sputnik-header">
    <h2>Sputnik - AI Content Draft Generator</h2>
    <p class="sputnik-subtitle">Create first drafts of pages using your site's block library</p>
  </div>
  
  <div class="sputnik-selector" id="selectorPhase">
    <label for="postType">Select Content Type:</label>
    <select id="postType">
      <option value="">Choose a content type...</option>
    </select>
    <button id="selectButton">Begin</button>
  </div>
  
  <div class="sputnik-chat" id="chatPhase" style="display: none;">
    <div class="sputnik-type-badge" id="typeBadge"></div>
    <div id="chat" class="sputnik-messages"></div>
    <div class="sputnik-input-area">
      <textarea id="input" placeholder="Describe the page content..."></textarea>
      <button id="send">Send</button>
    </div>
  </div>
  
  <div class="sputnik-loading" id="loading" style="display: none;">
    <p>Creating draft...</p>
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
        `<strong>Content Type:</strong> ${selectedLabel}`;

    // Initial AI greeting
    addMessage(
        'assistant',
        `I'll help you create a first draft for a ${selectedLabel}. Please describe what you'd like this page to contain.`,
    );
};

function addMessage(role, content) {
    const chatDiv = document.getElementById('chat');
    const msgDiv = document.createElement('div');
    msgDiv.className = `sputnik-message sputnik-${role}`;
    msgDiv.innerHTML = `<strong>${role === 'assistant' ? 'Sputnik' : 'You'}:</strong> ${content}`;
    chatDiv.appendChild(msgDiv);
    chatDiv.scrollTop = chatDiv.scrollHeight;
}

document.getElementById('send').onclick = async () => {
    if (draftCreated) {
        alert('Draft already created. Redirecting to editor...');
        return;
    }

    const input = document.getElementById('input');
    const text = input.value.trim();

    if (!text) {
        alert('Please enter a description');
        return;
    }

    if (!selectedPostType) {
        alert('No content type selected');
        return;
    }

    addMessage('user', text);
    messages.push({ role: 'user', content: text });

    document.getElementById('send').disabled = true;
    document.getElementById('loading').style.display = 'block';

    const res = await fetch(SPUTNIK.api, {
        method: 'POST',
        body: JSON.stringify({
            messages,
            postType: selectedPostType,
        }),
    });

    const data = await res.json();
    document.getElementById('send').disabled = false;
    document.getElementById('loading').style.display = 'none';

    if (data.error) {
        addMessage('assistant', `Error: ${data.error}`);
        return;
    }

    messages.push(data.reply);
    addMessage('assistant', data.reply.content);
    input.value = '';

    if (data.complete) {
        draftCreated = true;
        setTimeout(() => {
            window.location.href = data.edit_url;
        }, 1500);
    }
};
