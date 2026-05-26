/**
 * Template loader utility
 * Loads and caches HTML templates from separate files or pre-loaded cache
 */

const templateCache = {};

// Initialize cache from server-provided templates (if available)
function initializeCache() {
    if (window.SCOUT_TEMPLATES) {
        Object.assign(templateCache, window.SCOUT_TEMPLATES);
    }
}

export async function loadTemplate(templateName) {
    // Check cache first
    if (templateCache[templateName]) {
        return templateCache[templateName];
    }

    // If no templates path available (shouldn't happen), return empty
    if (!window.SCOUT_TEMPLATE_PATH) {
        console.error('Template path not configured');
        return '';
    }

    try {
        const response = await fetch(
            `${window.SCOUT_TEMPLATE_PATH}/${templateName}.html`,
        );
        if (!response.ok) {
            throw new Error(`Failed to load template: ${templateName}`);
        }
        const html = await response.text();
        templateCache[templateName] = html;
        return html;
    } catch (error) {
        console.error(`Error loading template ${templateName}:`, error);
        return '';
    }
}

export function getTemplateSync(templateName) {
    return templateCache[templateName] || '';
}

// Initialize on module load
initializeCache();
