# Scout – AI Content Draft Generator

![Version](https://img.shields.io/badge/v1.0.2-blue)
![License](https://img.shields.io/badge/license-GPL--2.0+-green)
![PHP](https://img.shields.io/badge/php-7.4+-purple)
![WordPress](https://img.shields.io/badge/wordpress-6.0+-blue)

Scout is a WordPress plugin that uses AI to generate first drafts of pages using only the blocks and post types available in your Carimus Backbone theme. It acts as a **content writer**, not a designer—creating initial page content that your team can refine and perfect. Scout reads block definitions directly from the WordPress block registry and strictly enforces that only allowed blocks and fields are used. The result is always saved as a draft, giving you full control over the final version.

## Features

- **Content Writer, Not Designer** – Generates first-draft page content; respects your block structure
- **Strict Block Enforcement** – Uses ONLY the blocks available in your Carimus Backbone theme
- **Multi-Provider AI** – Works with Claude, OpenAI, Gemini, and more (choose what works best for you)
- **Dynamic Block Discovery** – Automatically discovers and reads available blocks from the Carimus Backbone theme
- **ACF Integration** – Understands ACF block structures and generates valid field data
- **Media Library Integration** – AI intelligently selects images from your WordPress media library based on content context
- **Post Type Selection** – Lock in your content type before starting (page, customer-story, post, resource, etc.)
- **Smart Validation** – Rejects any blocks or fields outside the allowed set
- **Draft-Only Creation** – Always saves as draft for human review and refinement
- **Multi-Turn Refinement** – Continue editing and refining pages through multiple AI requests
- **Conversational Interface** – Multi-turn chat allows AI to ask clarifying questions

## How It Works

1. **Select Content Type** – Admin locks in the post type they want to create
2. **Initial Prompt** – Admin describes what the page should contain
3. **Block Discovery** – Scout queries `get_block_types()` to find all registered Carimus Backbone blocks
4. **Context Building** – Scout filters blocks to only those allowed for the selected post type
5. **Clarifying Questions** – Scout asks follow-up questions if it needs more information
6. **Content Generation** – When ready, AI generates a page layout JSON using only allowed blocks and fields
7. **Strict Validation** – Generated layout is rejected if any block or field is outside the allowed set
8. **Draft Creation** – If validation passes, a new draft post is created in WordPress
9. **Human Refinement** – Admin is redirected to the WordPress editor to review, refine, and publish

## Project Structure

```
scout/
├── scout.php              # Main plugin file
├── composer.json            # Composer package configuration
├── README.md                # This file
├── assets/
│   └── js/
│       └── app.js           # Frontend chat application
├── includes/
│   ├── admin/               # Admin interface components
│   │   ├── menu.php         # Admin menu & submenu registration
│   │   ├── page.php         # Main Scout page rendering
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
│   ├── content/             # Content generation
│   │   ├── block-builder.php    # WordPress block builder
│   │   ├── post-creator.php     # Post creation logic
│   │   └── post-creator-helper.php  # Helper functions
│   ├── media/               # Media library utilities
│   │   └── placeholder.php  # Image selection and conversion
│   └── updates/             # Plugin update checker
│       └── plugin-update-checker.php # GitHub release auto-updates
└── readme.txt
```

## Usage

### Step-by-Step Example

1. **Admin logs in** to WordPress and navigates to Scout
2. **Selects content type**: "Page"
3. **Enters initial prompt**: "Create a page for our upcoming hiring event"
4. **Scout responds** with follow-up questions: "What key benefits or details should I highlight?"
5. **Admin provides details**: "401k matching, health benefits, flexible work, career growth"
6. **Scout generates** a first draft page using only the allowed blocks for Pages
7. **Draft is saved** automatically to WordPress
8. **Admin is redirected** to the block editor to review, refine, and publish

The entire workflow takes minutes instead of hours—letting your team focus on perfecting content rather than creating from scratch.

---

### Via Composer (Recommended)

Add Scout to your WordPress project using Composer:

```bash
composer require carimus/scout
```

Then activate the plugin in your WordPress admin dashboard, or programmatically:

```php
activate_plugin('scout/scout.php');
```

### Manual Installation

1. Download or clone this repository
2. Copy the `scout` folder to your `wp-content/plugins/` directory
3. Activate the plugin from the WordPress admin dashboard

## Configuration

### Requirements

- **WordPress 6.0+** (for full block editor support)
- **PHP 7.4+**
- **Carimus Backbone Theme** (required – provides custom post types, block definitions, and ACF blocks)
- **AI Provider API key** (see configuration below)
- Theme must have blocks defined in `templates/blocks/**` with `block.json` files

### AI Provider Configuration

Scout is **provider-agnostic** and supports multiple AI services. Configure your provider and API key in the **Scout Settings** page in the WordPress admin.

#### Configure via WordPress Admin (Recommended)

1. In WordPress admin, go to **Scout → Settings**
2. Select your AI provider (Claude, OpenAI, or Gemini)
3. Enter your API key (securely encrypted and stored)
4. Click **Save Settings**

#### Configure via Environment Variables (Fallback)

If no API key is set in WordPress Settings, Scout will check for environment variables:

```bash
export SCOUT_AI_PROVIDER=anthropic  # or 'openai' or 'google'
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

#### WYSIWYG & Rich Text Field Handling

Scout is specially configured to handle WYSIWYG and rich text fields correctly:

- **AI Constraint:** The system prompt explicitly instructs the AI to provide **plain text only** for WYSIWYG fields—no HTML markup like `<p>`, `<b>`, `<i>`, etc.
- **HTML Entity Decoding:** If any HTML entities are accidentally encoded during transmission, Scout automatically decodes them
- **HTML Stripping:** During block building, any remaining HTML tags are removed from WYSIWYG content, preserving only plain text
- **Result:** Your WYSIWYG fields display clean, properly formatted text without encoded entity artifacts

### Setup

1. Activate the Scout plugin in WordPress
2. Go to **Scout → Settings** in the WordPress admin
3. Select your AI provider and enter your API key
4. Click **Save Settings**
5. Block configuration is automatic – Carimus theme defines allowed blocks and post types
6. Navigate to **Scout** in the WordPress admin to start generating page drafts

**Note:** If you prefer environment variables, you can skip step 2-4 and set `SCOUT_AI_PROVIDER` and the corresponding API key env var instead.

### Important Notes

- **Content Type is Locked** – Once you select a post type, it cannot be changed mid-conversation
- **Drafts Only** – Scout ALWAYS saves pages as drafts. You control when they're published
- **Use Only Allowed Blocks** – Scout strictly validates and will reject any layout using blocks outside your allowed set
- **First Draft Only** – Scout generates initial content; your team handles refinement and perfection

## Core Modules

### Settings Configuration (`includes/admin/settings-config.php`)

Handles provider and API key configuration:

- Registers WordPress settings and fields
- Provides `scout_get_ai_provider()` – gets provider from WordPress options, falls back to env var
- Provides `scout_get_api_key($provider)` – gets encrypted API key, falls back to env var
- Encrypts API keys before storage, decrypts on retrieval
- Settings accessible via **Scout → Settings** in WordPress admin

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
- Special instructions: WYSIWYG fields receive **plain text only, no HTML markup**

Configuration priority:

1. WordPress Settings (if configured via admin)
2. Environment variables (fallback)

Processes the AI response containing block layouts with populated ACF fields. Automatically decodes any HTML entities that may have been escaped during transmission.

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

- Reads ACF field definitions from the theme's block.json files
- Maps AI-generated data to the nested structure ACF blocks require
- Handles repeater fields with proper row indexing
- **WYSIWYG Field Handling:** Strips any remaining HTML tags from WYSIWYG/richtext fields, storing only plain text
- Properly structures padding, settings, and other block-level metadata
- Converts the block structure into serialized format for WordPress post_content

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

### Media Library Integration (`includes/media/placeholder.php`)

Scans the WordPress media library and provides intelligent image selection:

- **`scout_get_media_library_images($limit)`** – Fetches up to 20 media library images with full metadata (ID, URL, alt text, dimensions, title)
- **`scout_attachment_id_to_acf_image($attachment_id)`** – Converts attachment IDs to ACF image array format that blocks expect
- AI receives media library context in the system prompt and selects images intelligently based on content context
- For example: "vehicle-related" content triggers selection of images with vehicles
- Images are stored with complete ACF metadata (ID, URL, alt text, width, height, caption)

**How it works:**

1. When generating a page, Scout fetches the 20 most recent images from your media library
2. Image metadata is included in the AI prompt so Claude can reference them
3. For blocks with image fields, Claude analyzes the content and selects appropriate image IDs
4. Image IDs are converted to full ACF image arrays before being stored in blocks
5. Blocks render with real images from your library instead of placeholders

## API

### Chat Endpoint

**POST** `/wp-json/scout/v1/chat`

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
$provider = scout_get_ai_provider();  // Returns 'anthropic', 'openai', or 'google'

// Get the API key for a provider
$api_key = scout_get_api_key('anthropic');  // Returns encrypted key from options or env var

// Or get key for current provider
$api_key = scout_get_api_key();  // Gets key for scout_get_ai_provider()
```

Configuration sources (checked in order):

1. WordPress Settings (if admin has configured them)
2. Environment variables (fallback)

### Adding Support for New Block Types

To add a new block that Scout can generate:

1. **In Carimus Backbone Theme:**
    - Create a new directory in `templates/blocks/{block-name}/`
    - Add `block.json` with block metadata and ACF field definitions
    - Add `render.php` for the block template
    - Ensure the block name follows the `carimus/{block-name}` pattern

2. **In theme's functions.php:**
    - Block will be auto-discovered via the `allowed_block_types_all` filter
    - Add post type to the filter if needed

3. **In Scout:**
    - No changes needed – blocks are discovered dynamically
    - Test the AI output by creating pages with the new block

### Block JSON Structure

Scout reads and uses the full `block.json` structure from your Carimus Backbone blocks:

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

The `acf.fields` object defines the block's available ACF fields and their types. Scout uses this to generate valid field data.

## License

Scout is licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html). This means you can use, modify, and distribute this plugin freely as long as you maintain the same license.

## Versioning & Updates

Scout uses semantic versioning (MAJOR.MINOR.PATCH).

### Automatic Updates (Recommended)

Scout includes automatic update checking via GitHub releases. **Your GitHub repository must be public** for WordPress sites to detect and install updates automatically.

**How it works:**
1. Scout checks your GitHub repo for new releases (at the URL in `Plugin URI`)
2. When a new release is available, WordPress shows an update notification on the Plugins page
3. Users click "Update Now" to install the latest version
4. Updates happen automatically with a single click

When you create a GitHub release with a tag matching a version number, WordPress sites with Scout installed will automatically detect and offer the update:

1. User goes to Plugins page in WordPress admin
2. Scout update appears in the list (if available)
3. Click "Update Now" to install the latest version
4. WordPress downloads the release and activates it

This works automatically—no additional setup required beyond creating releases.

### Creating a Release

Releases are automated via GitHub Actions. Simply push a semantic version tag and everything else happens automatically:

```bash
# Push changes to main branch
git add .
git commit -m "Your changes"
git push origin main

# Create and push a version tag (e.g., v1.0.3)
git tag v1.0.3
git push origin v1.0.3
```

When the tag is pushed, GitHub Actions will automatically:

1. **Update version numbers**
   - `scout.php` → `Version: 1.0.3` and `define('SCOUT_VERSION', '1.0.3')`
   - `composer.json` → `"version": "1.0.3"`

2. **Update README badge** with the new version

3. **Commit changes** with message "chore: bump version to 1.0.3"

4. **Create a GitHub Release** with downloadable plugin files

5. **WordPress sites** will automatically detect the update and show "Update Available" on the Plugins page

**That's it!** Users can then click "Update Now" in WordPress to install the latest version.

### Manual Update Methods

#### Option 1: Composer (Development/Staging)

```bash
composer require carimus/scout:^1.0
```

Update with:

```bash
composer update carimus/scout
```

#### Option 2: Manual SFTP Download

1. Download the latest release from: [https://github.com/carimus/scout/releases](https://github.com/carimus/scout/releases)
2. SFTP the plugin files to your WordPress site: `wp-content/plugins/scout/`
3. Activate in WordPress admin (if not already active)

#### Option 3: WordPress.org Plugin Repository (Future)

Once submitted to the WordPress.org plugin repository, updates will be managed directly from the WordPress admin without any additional setup.

## Support

For issues, feature requests, or questions, please open an issue in the repository.
