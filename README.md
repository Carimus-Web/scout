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
│   │   ├── menu.php         # Admin menu registration
│   │   ├── page.php         # Admin page rendering
│   │   └── assets.php       # Admin asset loading
│   ├── ai/                  # AI integration
│   │   ├── client.php       # OpenAI API client
│   │   └── prompts.php      # AI prompt building
│   ├── api/                 # REST API endpoints
│   │   ├── chat-controller.php   # Chat handler logic
│   │   └── routes.php       # API route registration
│   ├── blocks/              # Block management
│   │   ├── allowed.php      # Post-type block rules
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

Sputnik is **provider-agnostic** and supports multiple AI services. Choose the one that best fits your needs:

#### **Recommended: Anthropic Claude** (Default)

Best for block generation – superior constraint adherence and structured JSON output.

```bash
export SPUTNIK_AI_PROVIDER=anthropic
export ANTHROPIC_API_KEY=your_key_here
```

Get API key: [https://console.anthropic.com](https://console.anthropic.com)

#### Alternative: OpenAI

```bash
export SPUTNIK_AI_PROVIDER=openai
export OPENAI_API_KEY=your_key_here
```

Get API key: [https://platform.openai.com/api-keys](https://platform.openai.com/api-keys)

#### Alternative: Google Gemini

```bash
export SPUTNIK_AI_PROVIDER=google
export GOOGLE_API_KEY=your_key_here
```

Get API key: [https://aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey)

### Setup

1. Choose an AI provider and set the appropriate environment variables (see Configuration section above)
2. Activate the Sputnik plugin in WordPress
3. Block configuration is automatic – Carimus theme defines allowed blocks and post types
4. Navigate to Sputnik in the WordPress admin to start generating page drafts

### Important Notes

- **Content Type is Locked** – Once you select a post type, it cannot be changed mid-conversation
- **Drafts Only** – Sputnik ALWAYS saves pages as drafts. You control when they're published
- **Use Only Allowed Blocks** – Sputnik strictly validates and will reject any layout using blocks outside your allowed set
- **First Draft Only** – Sputnik generates initial content; your team handles refinement and perfection

## Core Modules

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

Processes the AI response containing block layouts with populated ACF fields. Switch providers by setting the `SPUTNIK_AI_PROVIDER` environment variable.

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
- **Admin Menu:** `includes/admin/menu.php`
- **API Routes:** `includes/api/routes.php`
- **Block Discovery:** `includes/blocks/allowed.php`
- **AI Prompts:** `includes/ai/prompts.php`

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
