# Sputnik – AI Content Draft Generator

Sputnik is a WordPress plugin that uses AI to generate first drafts of pages using only the blocks and post types available in your Carimus Backbone theme. It acts as a **content writer**, not a designer—creating initial page content that your team can refine and perfect. Sputnik reads block definitions directly from the WordPress block registry and strictly enforces that only allowed blocks and fields are used. The result is always saved as a draft, giving you full control over the final version.

## Features

- **Content Writer, Not Designer** – Generates first-draft page content; respects your block structure
- **Strict Block Enforcement** – Uses ONLY the blocks available in your Carimus Backbone theme
- **Multi-Provider AI** – Works with Claude, OpenAI, Gemini, and more (choose what works best for you)
- **Dynamic Block Discovery** – Automatically discovers and reads available blocks from the Carimus Backbone theme
- **ACF Integration** – Understands ACF block structures and generates valid field data
- **Post Type Selection** – Lock in your content type before starting (page, customer-story, post, resource, etc.)
- **Smart Validation** – Rejects any blocks or fields outside the allowed set
- **Draft-Only Creation** – Always saves as draft for human review and refinement
- **Conversational Interface** – Multi-turn chat allows AI to ask clarifying questions

## How It Works

1. **Select Content Type** – Admin locks in the post type they want to create
2. **Initial Prompt** – Admin describes what the page should contain
3. **Block Discovery** – Sputnik queries `get_block_types()` to find all registered Carimus Backbone blocks
4. **Context Building** – Sputnik filters blocks to only those allowed for the selected post type
5. **Clarifying Questions** – Sputnik asks follow-up questions if it needs more information
6. **Content Generation** – When ready, AI generates a page layout JSON using only allowed blocks and fields
7. **Strict Validation** – Generated layout is rejected if any block or field is outside the allowed set
8. **Draft Creation** – If validation passes, a new draft post is created in WordPress
9. **Human Refinement** – Admin is redirected to the WordPress editor to review, refine, and publish

## Project Structure

```
sputnik/
├── sputnik.php              # Main plugin file
├── composer.json            # Composer package configuration
├── assets/
│   └── js/
│       └── app.js           # Frontend chat application
├── includes/
│   ├── admin/               # Admin interface components
│   │   ├── menu.php         # Admin menu & submenu registration
│   │   ├── page.php         # Main Sputnik page rendering
│   │   ├── assets.php       # Admin asset loading
│   │   ├── settings-config.php   # Settings registration & helpers
│   │   └── settings-page.php    # Settings page rendering
│   ├── ai/                  # AI integration
│   │   ├── client.php       # Multi-provider AI client
│   │   └── prompts.php      # AI prompt building
│   ├── api/                 # REST API endpoints
│   │   ├── chat-controller.php   # Chat handler logic
│   │   └── routes.php       # API route registration
│   ├── blocks/              # Block management
│   │   ├── allowed.php      # Block discovery from theme
│   │   └── validator.php    # Block validation logic
│   └── content/             # Content generation
│       ├── block-builder.php    # WordPress block builder
│       └── post-creator.php     # Post creation logic
└── readme.txt
```

## Usage

### Step-by-Step Example

1. **Admin logs in** to WordPress and navigates to Sputnik
2. **Selects content type**: "Page"
3. **Enters initial prompt**: "Create a page for our upcoming hiring event"
4. **Sputnik responds** with follow-up questions: "What key benefits or details should I highlight?"
5. **Admin provides details**: "401k matching, health benefits, flexible work, career growth"
6. **Sputnik generates** a first draft page using only the allowed blocks for Pages
7. **Draft is saved** automatically to WordPress
8. **Admin is redirected** to the block editor to review, refine, and publish

The entire workflow takes minutes instead of hours—letting your team focus on perfecting content rather than creating from scratch.

---

### Via Composer (Recommended)

Add Sputnik to your WordPress project using Composer:

```bash
composer require carimus/sputnik
```

Then activate the plugin in your WordPress admin dashboard, or programmatically:

```php
activate_plugin('sputnik/sputnik.php');
```

### Manual Installation

1. Download or clone this repository
2. Copy the `sputnik` folder to your `wp-content/plugins/` directory
3. Activate the plugin from the WordPress admin dashboard

## Configuration

### Requirements

- **WordPress 6.0+** (for full block editor support)
- **PHP 7.4+**
- **Carimus Backbone Theme** (required – provides custom post types, block definitions, and ACF blocks)
- **AI Provider API key** (see configuration below)
- Theme must have blocks defined in `templates/blocks/**` with `block.json` files

### AI Provider Configuration

Sputnik is **provider-agnostic** and supports multiple AI services. Configure your provider and API key in the **Sputnik Settings** page in the WordPress admin.

#### Configure via WordPress Admin (Recommended)

1. In WordPress admin, go to **Sputnik → Settings**
2. Select your AI provider (Claude, OpenAI, or Gemini)
3. Enter your API key (securely encrypted and stored)
4. Click **Save Settings**

#### Configure via Environment Variables (Fallback)

If no API key is set in WordPress Settings, Sputnik will check for environment variables:

```bash
export SPUTNIK_AI_PROVIDER=anthropic  # or 'openai' or 'google'
export ANTHROPIC_API_KEY=your_key_here
export OPENAI_API_KEY=your_key_here
export GOOGLE_API_KEY=your_key_here
```

#### **Recommended: Anthropic Claude** (Default)

Best for block generation – superior constraint adherence and structured JSON output.

Get API key: [https://console.anthropic.com](https://console.anthropic.com)

#### Alternative: OpenAI

Get API key: [https://platform.openai.com/api-keys](https://platform.openai.com/api-keys)

#### Alternative: Google Gemini

Get API key: [https://aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey)

### Setup

1. Activate the Sputnik plugin in WordPress
2. Go to **Sputnik → Settings** in the WordPress admin
3. Select your AI provider and enter your API key
4. Click **Save Settings**
5. Block configuration is automatic – Carimus theme defines allowed blocks and post types
6. Navigate to **Sputnik** in the WordPress admin to start generating page drafts

**Note:** If you prefer environment variables, you can skip step 2-4 and set `SPUTNIK_AI_PROVIDER` and the corresponding API key env var instead.

### Important Notes

- **Content Type is Locked** – Once you select a post type, it cannot be changed mid-conversation
- **Drafts Only** – Sputnik ALWAYS saves pages as drafts. You control when they're published
- **Use Only Allowed Blocks** – Sputnik strictly validates and will reject any layout using blocks outside your allowed set
- **First Draft Only** – Sputnik generates initial content; your team handles refinement and perfection

## Core Modules

### Settings Configuration (`includes/admin/settings-config.php`)

Handles provider and API key configuration:

- Registers WordPress settings and fields
- Provides `sputnik_get_ai_provider()` – gets provider from WordPress options, falls back to env var
- Provides `sputnik_get_api_key($provider)` – gets encrypted API key, falls back to env var
- Encrypts API keys before storage, decrypts on retrieval
- Settings accessible via **Sputnik → Settings** in WordPress admin

### Block Metadata Reader (`includes/blocks/allowed.php`)

Dynamically queries `get_block_types()` to discover all registered blocks from the Carimus Backbone theme. Reads block.json metadata including:

- Block name and title
- Description and category
- ACF field definitions and constraints
- Block example and preview data

Filters blocks based on the selected post type using the theme's `allowed_block_types_all` filter.

### AI Client (`includes/ai/client.php`)

Abstracted interface that supports multiple AI providers:

- **Anthropic Claude** (recommended) – Best for constraint-based JSON generation
- **OpenAI GPT** – Most popular, well-documented
- **Google Gemini** – Good alternative with competitive pricing

Handles communication with the selected AI provider, sending:

- User messages and post type
- Complete block metadata with ACF field schemas
- Constraints and allowed field values

Configuration priority:

1. WordPress Settings (if configured via admin)
2. Environment variables (fallback)

Processes the AI response containing block layouts with populated ACF fields.

### Chat Controller (`includes/api/chat-controller.php`)

Main request handler that orchestrates the flow:

- Receives user messages and locked post type
- Fetches allowed blocks for the post type
- Calls AI client for content generation
- Validates generated blocks (strict enforcement)
- Creates draft if validation passes
- Returns clarifying questions if more info is needed

### Block Builder (`includes/content/block-builder.php`)

Converts AI-generated layouts into WordPress block format using the Block API.

### Block Validator (`includes/blocks/validator.php`)

**Strictly validates** all generated blocks with zero tolerance:

- Rejects any blocks not in the allowed list
- Rejects any fields not defined in the block's ACF schema
- No exceptions, no approximations – validation is binary (pass or fail)
- Returns detailed error logs for debugging

### Post Creator (`includes/content/post-creator.php`)

Handles the creation of WordPress draft posts with the generated block content:

- Converts AI-generated JSON to WordPress block structure
- Populates ACF block attributes with generated field values
- Uses `serialize_blocks()` to create post_content
- Always sets `post_status: 'draft'` (never publishes)

## API

### Chat Endpoint

**POST** `/wp-json/sputnik/v1/chat`

Request body:

```json
{
    "messages": [
        { "role": "user", "content": "Create a post about WordPress tips" }
    ],
    "postType": "post"
}
```

Response:

```json
{
    "reply": { "role": "assistant", "content": "..." },
    "complete": true,
    "postId": 123
}
```

## Development

### File Locations

- **Frontend App:** `assets/js/app.js`
- **Admin Settings:** `includes/admin/settings-config.php` and `includes/admin/settings-page.php`
- **Admin Menu:** `includes/admin/menu.php`
- **API Routes:** `includes/api/routes.php`
- **Block Discovery:** `includes/blocks/allowed.php`
- **AI Prompts:** `includes/ai/prompts.php`

### Using Settings Functions

In your code, use these helper functions to access AI configuration:

```php
// Get the currently configured AI provider
$provider = sputnik_get_ai_provider();  // Returns 'anthropic', 'openai', or 'google'

// Get the API key for a provider
$api_key = sputnik_get_api_key('anthropic');  // Returns encrypted key from options or env var

// Or get key for current provider
$api_key = sputnik_get_api_key();  // Gets key for sputnik_get_ai_provider()
```

Configuration sources (checked in order):

1. WordPress Settings (if admin has configured them)
2. Environment variables (fallback)

### Adding Support for New Block Types

To add a new block that Sputnik can generate:

1. **In Carimus Backbone Theme:**
    - Create a new directory in `templates/blocks/{block-name}/`
    - Add `block.json` with block metadata and ACF field definitions
    - Add `render.php` for the block template
    - Ensure the block name follows the `carimus/{block-name}` pattern

2. **In theme's functions.php:**
    - Block will be auto-discovered via the `allowed_block_types_all` filter
    - Add post type to the filter if needed

3. **In Sputnik:**
    - No changes needed – blocks are discovered dynamically
    - Test the AI output by creating pages with the new block

### Block JSON Structure

Sputnik reads and uses the full `block.json` structure from your Carimus Backbone blocks:

```json
{
    "name": "carimus/block-name",
    "title": "Block Title",
    "description": "What this block does",
    "category": "layout",
    "acf": {
        "blockVersion": 3,
        "mode": "auto",
        "renderTemplate": "render.php"
    },
    "example": {
        "attributes": {
            "data": {
                "field_name": "example value"
            }
        }
    }
}
```

The `acf.fields` object defines the block's available ACF fields and their types. Sputnik uses this to generate valid field data.

## License

[Add your license here]

## Support

For issues, feature requests, or questions, please open an issue in the repository.
