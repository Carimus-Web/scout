/**
 * HTML and text utilities
 */

export function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    };
    return text.replace(/[&<>"']/g, (m) => map[m]);
}

export function parseMarkdown(text) {
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
