<?php

/**
 * Render the Sputnik settings page
 */
function sputnik_render_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    ?>
    <div class="wrap">
        <h1>Sputnik Settings</h1>
        <p>Configure your AI provider and API key for content generation.</p>
        
        <form action="options.php" method="POST">
            <?php
            settings_fields('sputnik_settings');
            do_settings_sections('sputnik_settings');
            submit_button();
            ?>
        </form>
        
        <div style="margin-top: 30px; padding: 20px; background: #f5f5f5; border-radius: 5px;">
            <h3>API Key Information</h3>
            <ul>
                <li><strong>Anthropic Claude:</strong> Get your API key at <a href="https://console.anthropic.com" target="_blank">console.anthropic.com</a></li>
                <li><strong>OpenAI:</strong> Get your API key at <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com/api-keys</a></li>
                <li><strong>Google Gemini:</strong> Get your API key at <a href="https://aistudio.google.com/app/apikey" target="_blank">aistudio.google.com/app/apikey</a></li>
            </ul>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #e7f3ff; border-left: 4px solid #2271b1; border-radius: 5px;">
            <h3>Environment Variable Fallback</h3>
            <p>If no API key is set in WordPress, Sputnik will check for these environment variables:</p>
            <ul>
                <li><code>SPUTNIK_AI_PROVIDER</code> (default: "anthropic")</li>
                <li><code>ANTHROPIC_API_KEY</code></li>
                <li><code>OPENAI_API_KEY</code></li>
                <li><code>GOOGLE_API_KEY</code></li>
            </ul>
        </div>
    </div>
    <?php
}
