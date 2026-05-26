/**
 * Preview iframe template for rendering draft pages
 */

import { getTemplateSync, loadTemplate } from './loader.js';

export function getPreviewIframeHTML(postUrl, editUrl) {
    const template = getTemplateSync('preview-iframe');
    return template
        .replace('{{POST_URL}}', postUrl)
        .replace('{{EDIT_URL}}', editUrl);
}

export function setupIframeScaling() {
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
}

// Pre-load template
loadTemplate('preview-iframe');
