<?php

/**
 * Render the Scout settings page
 */
function scout_render_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    ?>
    <div class="wrap">
        <h1>Scout Settings</h1>
        <p>Configure your AI provider and API key for content generation.</p>
        
        <?php settings_errors(); ?>
        
        <hr style="margin: 20px 0;"/>
        
        <div style="margin-bottom: 30px; padding: 20px; background: #f5f5f5; border-radius: 5px;">
            <h3>API Key Information</h3>
            <ul>
                <li><strong>Anthropic Claude:</strong> Get your API key at <a href="https://console.anthropic.com" target="_blank">console.anthropic.com</a></li>
                <li><strong>OpenAI:</strong> Get your API key at <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com/api-keys</a></li>
                <li><strong>Google Gemini:</strong> Get your API key at <a href="https://aistudio.google.com/app/apikey" target="_blank">aistudio.google.com/app/apikey</a></li>
            </ul>
        </div>

        <form action="options.php" method="POST">
            <?php
            settings_fields('scout_settings');
            do_settings_sections('scout_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
