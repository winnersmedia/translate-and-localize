# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2024-06-27

### Added
- Initial release of Translate and Localize with Grok
- AI-powered translation and localization using Grok API
- Polylang integration for WordPress multilingual support
- Background queue processing to avoid timeouts
- Cloudflare-safe implementation with configurable timeouts
- In-place translation for existing language posts
- Customizable translation prompts with placeholders
- Real-time progress tracking with AJAX updates
- Free-text model selection for flexibility
- Test connection feature to verify API configuration
- Auto-selection of post's assigned language as target
- Support for all public post types
- HTML formatting preservation in translations
- Post metadata and taxonomy copying
- Queue status dashboard in settings
- Comprehensive error handling and retry logic
- Security features including nonce verification
- Professional admin interface with responsive design

### Technical Details
- Requires WordPress 5.0+
- Requires PHP 7.4+
- Requires Polylang plugin
- Uses WordPress cron for background processing
- Database table for translation queue management

### Created by
Winners Media Limited - https://www.winnersmedia.co.uk