# Sputnik – AI-Powered WordPress Content Generator

Sputnik is a WordPress plugin that harnesses artificial intelligence to streamline content creation. Using conversational AI, users can describe their desired posts and Sputnik automatically generates them with properly formatted WordPress blocks.

## Features

- **AI-Powered Chat Interface** – Describe your content needs in natural language
- **Automatic Block Generation** – AI generates WordPress blocks based on your description
- **Post Type Support** – Customizable block validation based on post types
- **Smart Block Validation** – Ensures generated content adheres to post-type-specific rules
- **One-Click Publishing** – Automatically creates WordPress posts with AI-generated layouts
- **Admin Dashboard** – Manage settings and monitor AI-generated content

## How It Works

1. **User Input** – Open the Sputnik chat interface and describe the post you want to create
2. **AI Processing** – Sputnik sends your request to OpenAI with context about allowed blocks
3. **Block Generation** – The AI generates a layout with appropriate WordPress blocks and content
4. **Validation** – Blocks are validated against post-type-specific allowed blocks
5. **Post Creation** – If valid, a new post is automatically created with the generated content

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

## Installation

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

- WordPress 5.0+
- PHP 7.4+
- OpenAI API key

### Setup

1. Set the `OPENAI_API_KEY` environment variable with your OpenAI API key
2. Activate the Sputnik plugin in WordPress
3. Configure allowed blocks for each post type in the admin dashboard
4. Start using the chat interface to generate content

## Core Modules

### AI Client (`includes/ai/client.php`)

Handles communication with OpenAI's API, processing user messages and generating block layouts in JSON format.

### Chat Controller (`includes/api/chat-controller.php`)

Main request handler that orchestrates the flow:

- Receives user messages and post type
- Fetches allowed blocks for the post type
- Calls AI client for content generation
- Validates generated blocks
- Creates posts if validation passes

### Block Builder (`includes/content/block-builder.php`)

Converts AI-generated layouts into WordPress block format using the Block API.

### Block Validator (`includes/blocks/validator.php`)

Ensures all generated blocks are allowed for the specified post type.

### Post Creator (`includes/content/post-creator.php`)

Handles the creation of WordPress posts with the generated block content.

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
- **Allowed Blocks:** `includes/blocks/allowed.php`

### Adding Support for New Block Types

1. Update `includes/blocks/allowed.php` to define blocks for your post type
2. Modify the prompt in `includes/ai/prompts.php` if needed
3. Test the AI output with the new block configuration

## License

[Add your license here]

## Support

For issues, feature requests, or questions, please open an issue in the repository.
