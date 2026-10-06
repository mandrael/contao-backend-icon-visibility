# Changelog

All notable changes to this project are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Section "Back end icons" in the system settings.
- Option "Show all icons": every list shows all icons in the row, as in Contao 5.3, without the "..." menu.
- Selection "Show in the row": "In all lists" for icons that should be visible in every list that has them, plus ten areas (pages, articles, content elements, news, events, FAQ, newsletters and recipients, forms and form fields, files, members) in one grouped, collapsible field.
- Selection "Keep in the ... menu" with the same groups; it takes precedence over "Show in the row" and "Show all icons".
- Selectable "new after/into" buttons in the site structure and in parent views, optionally with distinct icons (circle open at the bottom or on the right, setting "Own icons").
- Own selection in the user profile, replacing the system settings; allowed per user group ("Allow own icon selection") and always for administrators. Prefilled with the system settings and limited to the areas of the user's back end modules.
- The options are built from the operations that actually exist in the respective list.

### Fixed

- The system settings no longer show outdated values right after saving: Contao writes them only after the redirect, so saving again stored the old values. Changed settings are now written before the redirect.
