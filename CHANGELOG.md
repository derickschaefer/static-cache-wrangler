# Changelog

All notable changes to this project will be documented in this file.

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
