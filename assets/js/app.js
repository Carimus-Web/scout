const app = document.getElementById('sputnik-app');

let messages = [];

app.innerHTML = `
<select id="postType"></select>
<div id="chat"></div>
<textarea id="input"></textarea>
<button id="send">Send</button>
`;

const select = document.getElementById('postType');

SPUTNIK.postTypes.forEach((pt) => {
    const opt = document.createElement('option');
    opt.value = pt.value;
    opt.textContent = pt.label;
    select.appendChild(opt);
});

document.getElementById('send').onclick = async () => {
    const input = document.getElementById('input');

    messages.push({ role: 'user', content: input.value });

    const res = await fetch(SPUTNIK.api, {
        method: 'POST',
        body: JSON.stringify({
            messages,
            postType: select.value,
        }),
    });

    const data = await res.json();

    messages.push(data.reply);

    document.getElementById('chat').innerHTML += `
    <div><strong>${data.reply.role}:</strong> ${data.reply.content}</div>
  `;

    input.value = '';

    if (data.complete) {
        window.location.href = data.edit_url;
    }
};
