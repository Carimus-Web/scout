/**
 * Main application layout template
 */

import { getTemplateSync } from './loader.js';

export function getMainLayoutHTML() {
    return getTemplateSync('main-layout');
}

// Pre-load the template
import { loadTemplate } from './loader.js';
loadTemplate('main-layout');
