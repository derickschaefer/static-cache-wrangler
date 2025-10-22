# Changelog

All notable changes to this project will be documented in this file.

## [2.0.3] - 2025-01-XX

### WordPress.org Compliance
- **MAJOR:** Changed all PHP prefixes from `SCG_` to `STCG_` (4+ characters)
- **BREAKING:** All option names changed (`scg_enabled` → `stcg_enabled`)
- **BREAKING:** All WordPress hooks changed (`scg_process_assets` → `stcg_process_assets`)
- **IMPORTANT:** WP-CLI commands remain unchanged (`wp scg`)

### Script/Style Enqueuing
- Extracted inline CSS to `admin/css/admin-style.css`
- Extracted inline JavaScript to separate files
- Properly enqueued all scripts and styles

### Migration Notes
- Static files need to be regenerated
- Clear all files: `wp scg clear`
- Re-enable and regenerate

## [2.0.2] - 2025-01-09

### WordPress Coding Standards Compliance
- **MAJOR:** Achieved 100% WordPress Plugin Check compliance (zero errors, zero warnings)
- Replaced all `parse_url()` calls with `wp_parse_url()` for consistent URL parsing across PHP versions (9 instances)
- Replaced all `date()` calls with `current_time()` for proper WordPress timezone handling
- Implemented `WP_Filesystem` API for all file operations (replaced `readfile()`, `rename()`, `rmdir()`, `unlink()`)
- Added proper text domain to all translation functions (`__()`, `_e()`, `_n()`)
- Added comprehensive translators comments for all plural translations with placeholders
- Removed deprecated `load_plugin_textdomain()` call (WordPress 4.6+ handles automatically)

### Security Enhancements
- Added `esc_html()`, `esc_url()`, `esc_js()`, `esc_attr()` escaping to all output
- Implemented proper input sanitization with `wp_unslash()` and `sanitize_text_field()` for all `$_SERVER` variables
- Added `sanitize_key()` for all user input validation
- Added phpcs:ignore annotations for binary content (ZIP files) with security explanations
- Wrapped all development `error_log()` calls in `WP_DEBUG` conditionals
- Enhanced nonce verification documentation throughout admin handlers

### Code Quality Improvements
- Reduced code footprint by ~400 lines through better PHP syntax and WordPress API usage
- Replaced verbose comments with self-documenting code following WordPress standards
- Consolidated redundant validation logic (WordPress functions handle edge cases internally)
- Improved PHPDoc comments throughout all classes with proper type hints
- Streamlined autoloader logic with cleaner conditional checks
- Used null coalescing operators (`??`) and ternary expressions for more concise code

### Documentation
- Created proper `readme.txt` in WordPress.org format with all required headers
- Added "Tested up to: 6.8" for current WordPress compatibility
- Added "Requires PHP: 7.4" for minimum PHP version
- Added comprehensive GPL v2+ license information
- Created `languages/README.txt` to document translation file location
- Updated plugin headers with all WordPress.org required metadata

### File Operations
- Simplified `delete_directory_contents()` to use only `WP_Filesystem::rmdir($dir, true)`
- Removed all fallback code using direct PHP filesystem functions
- Enhanced error handling with proper `wp_die()` messages when operations fail
- Improved ZIP file download using `$wp_filesystem->get_contents()` with proper cleanup

### Admin Interface
- Fixed translators comment placement (must be immediately before translation function)
- Separated translation logic from output for proper WordPress i18n scanning
- Added phpcs:ignore for false-positive nonce warnings with explanatory comments
- Improved code readability in admin page template

### WP-CLI
- Enhanced `zip` command with proper WP_Filesystem for file operations
- Improved error handling and user feedback in all CLI commands
- Better progress indicators and status messages

### Performance
- Reduced plugin file size by 49% (static-site.php: 144 → 74 lines)
- Eliminated redundant defensive programming (WordPress APIs handle validation)
- Streamlined output buffering and HTML processing
- More efficient regex patterns in asset extraction

### Removed
- Removed `Domain Path` header (causes warnings, not needed)
- Removed all direct PHP filesystem function calls
- Removed verbose inline comments (code is self-documenting)
- Removed error suppression operators where proper error handling exists
- Removed redundant null checks (using null coalescing instead)

## [2.0.1] - 2025-01-08

### Security
- **CRITICAL:** Added path traversal validation in `zip --output` command to prevent writing files outside allowed directories
- **CRITICAL:** Added comprehensive path validation to `delete_directory_contents()` with realpath() checks
- **CRITICAL:** Enhanced `filename_from_url()` with 13-step security validation and whitelist-based extension filtering
- Added symlink protection to file deletion and ZIP creation operations
- Removed error suppression (`@`) operators, replaced with proper error handling and logging
- Added input sanitization for `$_GET['message']` parameter in admin page
- Implemented multi-layer filename validation to prevent path traversal, null byte injection, and executable file uploads

### Added
- Disk usage information panel in admin dashboard showing total size and available space
- Disk usage warnings when space is low (<500MB) or cache is large (>1GB)
- Comprehensive favicon handling documentation with troubleshooting steps
- File extension whitelist for asset downloads (CSS, JS, images, fonts, PDFs only)
- `uninstall.php` for complete cleanup on plugin deletion (removes options, files, and cron events)
- Settings link on WordPress plugins page for quick access
- Documentation and Support links in plugin meta row
- Load text domain support for internationalization (i18n)
- Detailed security comments throughout codebase explaining validation logic
- Disk space considerations section in README with usage estimates
- Asset handling documentation explaining what is and isn't downloaded

### Changed
- Video files (MP4, WebM, OGG) and audio files (MP3, WAV) intentionally excluded from download whitelist to conserve disk space
- ZIP files excluded from download whitelist to prevent nested compression issues
- Enhanced error logging with contextual information throughout all file operations
- Improved admin page with better visual hierarchy and information density
- Updated plugin headers with proper WordPress.org metadata (Requires PHP, License, etc.)
- `clear_all_files()` now returns boolean success/failure status
- `delete_directory_contents()` performs validation on every file during iteration

### Fixed
- Path validation in `get_static_file_path()` now includes sanitization and path traversal prevention
- Version string handling in `filename_from_url()` now properly sanitized and length-limited
- Admin page `$_GET['message']` parameter now sanitized with `sanitize_key()`
- Potential security issues in all file system operations addressed
- Error messages now properly logged instead of silently suppressed

### Removed
- Error suppression (`@`) from `rmdir()` and `unlink()` operations
- Support for video and audio file downloads (intentional, to reduce disk usage)

## [2.0.0] - 2025-01-15

### Added
- Modern card-based admin UI
- Real-time asset processing with progress bar
- Directory size calculation and display
- WP-CLI `zip` command for creating archives
- File system location display in admin

### Changed
- Renamed WP-CLI commands from `ssg` to `scg`
- Improved error handling in asset downloads
- Better CSS/JS asset path rewriting

### Fixed
- Admin page now correctly displays file paths
- Directory size calculation working properly
- Namespace conflicts resolved in admin view
- Asset processing AJAX handler properly registered

## [1.0.0] - 2024-12-01

### Initial Release
- Initial development version
- Internal testing on production sites
- Core functionality established
- Basic static file generation
- Asset downloading and localization
