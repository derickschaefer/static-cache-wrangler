# Changelog

All notable changes to this project will be documented in this file.

## [2.0.6] - 2025-11-11

### WordPress.org Compliance Release

This release ensures full compatibility with WordPress.org Plugin Directory validation requirements introduced in 2025, requiring prefixed template variables and enhanced namespace isolation.

### Compliance & Code Quality
- **MAJOR:** All template variables in admin views now use `stcw_` prefix for WordPress.org compliance
- **IMPROVED:** Passes 100% WordPress.org Plugin Check validation (zero errors, zero warnings)
- **ENHANCED:** Code clarity with consistent variable naming throughout templates
- **UPDATED:** Inline documentation for better code understanding

### Developer Features
- **NEW HOOK:** `stcw_before_file_save` - fires immediately before static file write to disk
  - **Parameters:** `$static_file` (string) - path to file being saved
  - **Use Case:** Start timers, collect memory data, monitor disk operations
  
- **NEW HOOK:** `stcw_after_file_save` - fires after static file save completion
  - **Parameters:** `$success` (bool) - true on success, `$static_file` (string) - saved file path
  - **Use Case:** Log, validate, or post-process generated files
  
- **NEW HOOK:** `stcw_before_asset_download` - allows inspection/modification of asset URLs before download
  - **Parameters:** `$url` (string) - original asset URL
  - **Returns:** Filtered URL string
  - **Use Case:** Transform URLs, add authentication, or skip certain assets
  
- **NEW HOOK:** `stcw_after_asset_download` - executes after each asset download completes
  - **Parameters:** `$dest` (string) - destination path, `$url` (string) - original URL
  - **Returns:** Filtered destination path (optional)
  - **Use Case:** Validate downloads, optimize files, or log completion
  
- **NEW HOOK:** `stcw_before_asset_batch` - fires before asynchronous asset batch begins
  - **Use Case:** Initialize timers, resource tracking for async performance
  
- **NEW HOOK:** `stcw_after_asset_batch` - fires after async asset batch completes
  - **Parameters:** `$processed` (int) - assets processed successfully, `$failed` (int) - failed downloads
  - **Use Case:** Record batch performance, memory usage, queue diagnostics

### Performance Profiling
- **NEW:** Foundation for optional Performance Profiler MU plugin
- **NEW:** `/tools/` directory with developer documentation
- **NEW:** `/tools/performance-profiler.txt` - complete profiling installation guide
- **ENHANCED:** Profiling hooks enable detailed benchmarking without core modifications
- **ZERO OVERHEAD:** All profiling features inactive by default unless explicitly enabled
- **WP-CLI READY:** Profiler adds `wp stcw profiler` command group for performance analysis

### Performance Profiler Features (Optional MU Plugin)
When installed and enabled, the profiler provides:
- **Page generation metrics** - time, memory, peak memory, file I/O operations
- **Asset batch analytics** - duration, memory, success/failure rates
- **WP-CLI commands:**
  - `wp stcw profiler stats` - aggregate performance statistics
  - `wp stcw profiler logs` - view recent profiling entries
  - `wp stcw profiler clear` - remove all profiling data
  - `wp stcw profiler export` - export data to CSV for analysis
- **Retention management** - automatically maintains most recent 100 entries (configurable)
- **Zero external dependencies** - all data stored locally in WordPress options table

### Documentation
- **ADDED:** Complete performance profiling guide in `/tools/performance-profiler.txt`
- **ADDED:** Developer hook usage examples with real-world scenarios
- **UPDATED:** README.md with profiling installation instructions
- **UPDATED:** Code comments throughout for better maintainability
- **ADDED:** Performance profiler download link: [moderncli.dev/code/static-cache-wrangler/performance-profiler/](https://moderncli.dev/code/static-cache-wrangler/performance-profiler/)

### Compatibility
- **TESTED:** WordPress 6.8.3
- **TESTED:** PHP 7.4, 8.0, 8.1, 8.2, 8.3
- **COMPATIBLE:** All major themes and page builders
- **COMPATIBLE:** Multisite with isolated storage per site
- **COMPATIBLE:** All WP-CLI versions

### Migration Notes
- **NO BREAKING CHANGES:** Fully backward compatible with 2.0.5
- **OPTIONAL:** Install Performance Profiler MU plugin for advanced diagnostics
- **RECOMMENDED:** Review new hooks if building companion plugins or integrations
- **NOTE:** Profiling features require manual installation and opt-in activation

### Files Changed
- `admin/views/admin-page.php` - Template variable prefixing (WordPress.org compliance)
- `includes/class-stcw-core.php` - Added profiling hooks in file operations
- `includes/class-stcw-asset-handler.php` - Added profiling hooks in asset downloads
- `includes/class-stcw-generator.php` - Added profiling hooks in generation process
- `tools/performance-profiler.txt` - New developer documentation
- `README.md` - Added profiling documentation and hook examples
- `CHANGELOG.md` - This file
- `readme.txt` - WordPress.org plugin directory description

### Developer Benefits
- ✅ **Zero-impact profiling** - No overhead unless explicitly enabled
- ✅ **Non-invasive hooks** - Monitor without modifying core plugin code
- ✅ **Extensible architecture** - Foundation for companion plugins and SaaS integrations
- ✅ **Production-safe** - Profiling completely disabled by default
- ✅ **CLI-focused** - All profiling operations via WP-CLI for automation
- ✅ **Open architecture** - Use hooks for custom monitoring solutions

### Summary
Version 2.0.6 is primarily a WordPress.org compliance release with enhanced developer capabilities. While there are no user-facing changes or new features for end users, this release establishes the foundation for advanced performance monitoring and provides developers with powerful hooks for building companion plugins and custom integrations.

The optional Performance Profiler MU plugin showcases how these hooks can be used to create sophisticated monitoring tools without modifying the core plugin.

---

## [2.0.5] - 2025-10-25

### Enhanced Static HTML Output
- **MAJOR:** Implemented hybrid WordPress meta tag removal system using native `remove_action()` with regex safety net
- **REMOVED:** WordPress-specific meta tags no longer appear in static HTML:
  - RSD (Really Simple Discovery) links for XML-RPC
  - Windows Live Writer manifest links
  - WordPress shortlink tags
  - WordPress generator meta tags (version information)
  - REST API discovery links
  - oEmbed discovery links
  - REST API HTTP headers
- **CLEANED:** Removed `data-wp-strategy` attributes from script tags for cleaner HTML
- **IMPROVED:** Static HTML is now 3.1% smaller and fully portable without WordPress metadata

### Developer Features
- **NEW HOOK:** `stcw_remove_wp_head_tags` action - allows developers to remove additional WordPress head tags before output buffering
- **NEW HOOK:** `stcw_process_static_html` filter - allows developers to modify HTML before saving to file
- **NEW METHOD:** `STCW_Generator::remove_wordpress_meta_tags()` - centralized WordPress tag removal using native APIs
- **ENHANCED:** `process_static_html()` method with comprehensive regex patterns as safety net
- **DOCUMENTED:** Added inline PHPDoc comments explaining each removal and extension point

### Performance
- **OPTIMIZED:** Meta tags prevented from generating (not removed after generation) for better performance
- **REDUCED:** HTML generation time by 2.3% due to fewer actions in wp_head()
- **IMPROVED:** File sizes reduced by 3.1% from removing unnecessary metadata
- **FASTER:** Background asset processing with reduced overhead

### Code Quality
- **REFACTORED:** Follows WordPress coding standards and best practices
- **IMPROVED:** Uses WordPress's native `remove_action()` API instead of only regex-based removal
- **ENHANCED:** More maintainable code with clear separation of concerns
- **DOCUMENTED:** Comprehensive PHPDoc comments throughout class methods
- **EXTENSIBLE:** Clean API for third-party developers and companion plugins

### Security
- **IMPROVED:** WordPress version information no longer exposed in generator meta tags
- **ENHANCED:** Reduced fingerprinting surface by removing WordPress-specific metadata
- **BETTER:** Clean HTML output prevents information disclosure

### Documentation
- **ADDED:** Complete implementation guide with step-by-step instructions
- **ADDED:** Quick testing reference card for 2-minute verification
- **ADDED:** Developer examples with 15+ hook usage patterns
- **ADDED:** Before/after comparisons and performance benchmarks
- **ADDED:** Troubleshooting guide for common issues

### Compatibility
- **TESTED:** WordPress 6.8.3
- **TESTED:** PHP 7.4, 8.0, 8.1, 8.2, 8.3
- **COMPATIBLE:** All major themes and page builders
- **COMPATIBLE:** SEO plugins (Yoast SEO, Rank Math, All in One SEO)
- **COMPATIBLE:** Performance plugins (WP Rocket, W3 Total Cache)
- **COMPATIBLE:** Page builders (Gutenberg, Elementor, Divi, Beaver Builder)

### Migration Notes
- **NO BREAKING CHANGES:** Fully backward compatible with 2.0.4
- **RECOMMENDED:** Clear and regenerate static files after update for cleanest output: `wp scw clear && wp scw enable`
- **OPTIONAL:** Review new hooks for customization opportunities in theme or companion plugins

### Files Changed
- `includes/class-stcw-generator.php` - Major enhancement with new methods, hooks, and improved processing

### Benefits Summary
- ✅ **Cleaner HTML**: No WordPress-specific metadata in static output
- ✅ **Better Security**: WordPress version and internal URLs no longer exposed
- ✅ **Smaller Files**: 3.1% reduction in HTML file size
- ✅ **Faster Generation**: 2.3% improvement in processing time
- ✅ **More Portable**: True offline capability without WordPress references
- ✅ **Extensible**: Two new hooks enable companion plugins and customization
- ✅ **Professional Output**: Framework-agnostic HTML suitable for any deployment

---

## [2.0.4] - 2025-10-22

### Major WordPress.org Compliance & Refactor Release
- **MAJOR:** Comprehensive compliance overhaul aligning with WordPress.org Plugin Directory and Coding Standards.
- **RENAME:** Plugin slug, text domain, and directory changed from `static-cache-generator` → `static-cache-wrangler` to meet naming and trademark requirements.
- **NAMESPACE:** All internal class and function prefixes updated from `STCG` → `STCW` (4+ character namespace compliance).
- **I18N:** Unified text domains and translation calls across all PHP files for full localization validation.
- **STRUCTURE:** Updated folder structure, includes, and autoload paths for modern compatibility.
- **ADMIN UI:** Refactored admin views and templates for cleaner markup, translation readiness, and better accessibility.
- **CLI:** Confirmed namespace and command base as `scw` (formerly `scg`), removed legacy aliases for clarity.
- **REPO:** Updated all GitHub references, assets, and documentation links to reflect the new canonical project name.
- **PACKAGING:** Cleaned distribution process and `.zip` exports to exclude development assets, node_modules, vendor directories, and internal build scripts.

### Migration Notes
- **BREAKING:** Sites upgrading from `Static Cache Generator` must deactivate and remove the old plugin before activating `Static Cache Wrangler`.
- Existing static files should be cleared and regenerated for full compatibility.
- Command reference:  
```bash
  wp scw clear
  wp scw enable
  wp scw process
```

---

## [2.0.3] - 2025-10-18

### WordPress.org Compliance
- **MAJOR:** Changed all PHP prefixes from `SCG_` to `STCW_` (4+ characters)
- **BREAKING:** All option names changed (`scw_enabled` → `stcw_enabled`)
- **BREAKING:** All WordPress hooks changed (`scw_process_assets` → `stcw_process_assets`)
- **IMPORTANT:** WP-CLI commands remain unchanged (`wp scw`)

### Script/Style Enqueuing
- Extracted inline CSS to `admin/css/admin-style.css`
- Extracted inline JavaScript to separate files
- Properly enqueued all scripts and styles

### Migration Notes
- Static files need to be regenerated
- Clear all files: `wp scw clear`
- Re-enable and regenerate

---

## [2.0.2] - 2025-10-09

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

---

## [2.0.1] - 2025-10-08

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

---

## [2.0.0] - 2025-01-15

### Added
- Modern card-based admin UI
- Real-time asset processing with progress bar
- Directory size calculation and display
- WP-CLI `zip` command for creating archives
- File system location display in admin

### Changed
- Renamed WP-CLI commands from `ssg` to `scw`
- Improved error handling in asset downloads
- Better CSS/JS asset path rewriting

### Fixed
- Admin page now correctly displays file paths
- Directory size calculation working properly
- Namespace conflicts resolved in admin view
- Asset processing AJAX handler properly registered

---

## [1.0.0] - 2024-12-01

### Initial Release
- Initial development version
- Internal testing on production sites
- Core functionality established
- Basic static file generation
- Asset downloading and localization
