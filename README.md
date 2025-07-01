# Translate and Localize with Grok

**AI-powered WordPress translation and localization** using Grok API with Polylang integration and background processing.

![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)
![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)

## 🚨 Problem Solved

Are you experiencing these **WordPress translation challenges**?

- ✅ **Manual translation takes hours** for each post
- ✅ **Generic translations** that don't resonate with local audiences
- ✅ **Cloudflare timeouts** during translation processing
- ✅ **Lost cultural context** in machine translations
- ✅ **No localization** - just word-for-word translation
- ✅ **Broken HTML formatting** after translation
- ✅ **Server timeouts** on long content
- ✅ **No background processing** for translations

**This plugin solves all these problems** with AI-powered translation and localization.

## 🔥 Key Features

### ⚡ AI-Powered Translation & Localization
- **Grok AI integration** for intelligent translations
- **Cultural adaptation** not just word-for-word translation
- **Maintains tone and style** of original content
- **Adapts idioms and expressions** for target audience

### 🛡️ Cloudflare & Timeout Protection
- **Background queue processing** prevents timeouts
- **Works with Cloudflare** 100-second limit
- **Chunked processing** for large content
- **Automatic retry** on failures

### 🎯 Seamless Polylang Integration
- **One-click translation** from post editor
- **Automatic language linking** in Polylang
- **Preserves all metadata** and custom fields
- **Copies taxonomies** to translated posts

### 🛠️ Professional Features
- **Customizable prompts** for different content types
- **Multiple Grok models** to choose from
- **HTML formatting preservation**
- **Progress tracking** with real-time updates
- **Queue management** dashboard

## 🚀 Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/translate-and-localize/`
3. Activate the plugin through WordPress admin
4. **Ensure Polylang is installed and activated**
5. Go to **Settings → Translate & Localize**
6. Add your Grok API key
7. Configure your translation preferences

## ⚙️ Configuration

### 1. Get Your Grok API Key

1. Visit [xAI Console](https://console.x.ai)
2. Sign up or log in to your account
3. Navigate to API Keys section
4. Create a new API key
5. Copy and paste in plugin settings

### 2. Plugin Settings

- **API Key**: Your Grok API key from xAI
- **Model**: Enter the model name (e.g., grok-beta, grok-2-beta) - check [xAI docs](https://docs.x.ai/docs#models) for latest models
- **Default Prompt**: Customize how content is translated
- **Batch Size**: Number of items to process (1-10)
- **Request Timeout**: API timeout in seconds (30-300)
- **Test Connection**: Verify your API key and model are working

### 3. Prompt Customization

Available placeholders:
- `{source_lang}` - Source language code
- `{target_lang}` - Target language code  
- `{content}` - The content to translate

Default prompt:
```
Translate and localize the following content from {source_lang} to {target_lang}. 
Maintain the tone and style while adapting cultural references, idioms, and 
expressions to be appropriate for the target audience. Preserve all HTML formatting.

Content to translate:
{content}
```

## 💡 Usage

### From Post Editor

1. Edit any post or custom post type
2. Look for **"Translate & Localize with Grok"** metabox
3. Select target language
4. Click **"Translate & Localize"**
5. Watch real-time progress
6. Automatic redirect to translated post when complete

### From Posts List

1. Hover over any post in the admin list
2. Click **"Translate & Localize"** in row actions
3. Redirects to post editor with metabox ready

### Translation Process

1. **Queued**: Translation added to background queue
2. **Processing**: Grok API processing the content
3. **Completed**: New draft post created in target language
4. **Failed**: Error message displayed (can retry)

## 🎯 How It Works

1. **Select content** and target language
2. **Queue translation** to avoid timeouts
3. **Background processing** via WordPress cron
4. **Grok AI translates** and localizes content
5. **Creates draft post** in target language
6. **Links translations** in Polylang

## 📊 Queue Management

The plugin processes translations in the background:

- **Runs every minute** via WordPress cron
- **Processes in batches** to avoid timeouts
- **Automatic retry** on failures
- **Progress tracking** in real-time
- **Queue status** visible in settings

## 🛡️ Timeout Prevention

Designed for servers with strict timeout limits:

- **Background queue** prevents Cloudflare 524 errors
- **Configurable timeouts** (30-300 seconds)
- **Small batch sizes** for reliability
- **Chunked processing** for large content
- **Graceful failure handling**

## 📋 Requirements

- **WordPress 5.0+**
- **PHP 7.4+**
- **Polylang or Polylang Pro** (required)
- **Grok API key** from xAI
- **WordPress cron** enabled

## 🔧 Troubleshooting

### No translations processing

1. Check WordPress cron is running
2. Verify API key is correct
3. Check queue status in settings
4. Look for errors in WordPress debug log

### Timeouts still occurring

1. Reduce batch size to 1
2. Lower request timeout
3. Check server timeout settings
4. Enable WordPress debug logging

### Translations not appearing

1. Check Polylang languages are configured
2. Verify source post has language set
3. Look for draft posts in target language
4. Check queue for failed items

## ⚠️ Important Notes

- **Creates draft posts** - review before publishing
- **Requires Polylang** - won't work without it
- **API costs apply** - check xAI pricing
- **Background processing** - not instant

## 🐛 Known Limitations

- Only translates `post_content` field
- Requires manual review of translations
- API rate limits may apply
- Depends on WordPress cron reliability

## 📞 Support

- **GitHub Issues**: [Report bugs](https://github.com/winnersmedia/translate-and-localize/issues)
- **Created by**: [Winners Media Limited](https://www.winnersmedia.co.uk)

## 🤝 Contributing

Contributions welcome! Help improve AI-powered WordPress translations:

1. Fork the repository
2. Create feature branch
3. Make improvements
4. Test thoroughly
5. Submit pull request

## 📝 Changelog

### Version 1.0.0
- Initial release
- Grok API integration
- Polylang compatibility
- Background queue processing
- Cloudflare timeout prevention
- Customizable prompts
- Progress tracking

## 📄 License

GPL v2 or later - same as WordPress

---

**Transform your multilingual content workflow** with AI-powered translation and localization!