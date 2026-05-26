/**
 * Preview window state templates (idle, loading, error)
 */

import { getTemplateSync, loadTemplate } from './loader.js';

export function getPreviewIdleHTML() {
    return getTemplateSync('preview-idle');
}

export function getPreviewLoadingHTML() {
    return getTemplateSync('preview-loading');
}

export function getPreviewErrorHTML() {
    return getTemplateSync('preview-error');
}

export function getPreviewRecreateHTML() {
    return `
        ${getTemplateSync('preview-idle')}
        ${getTemplateSync('preview-loading')}
        ${getTemplateSync('preview-error')}
    `;
}

// Pre-load templates
loadTemplate('preview-idle');
loadTemplate('preview-loading');
loadTemplate('preview-error');
