# Back end icon visibility for Contao

[Deutsche Version](README.md)

Since Contao 5.5 the back end lists show only a few icons directly in the row (edit, publish, child records). Copy, move, delete, details, versions and others live in the "..." menu and need an extra click.

This bundle lets you choose in the system settings which icons stay visible in the row: for all lists, per area, or simply all of them as in Contao 5.3.

## Features

- **Show all icons:** every list shows all icons in the row and the "..." menu disappears. Large site structures then load all icons right away as well.
- **In all lists:** icons that should be visible in every list that has them, for example "Details". This includes lists of other extensions.
- **Additionally per area:** pages, articles, content elements, news, events, files, forms (with form fields) and members. For pages, for example, the articles of a page or copying with subpages.
- **New after/into:** the buttons that create a record after or inside another one can be shown as icons too.
- Only icons that actually exist in the respective list can be selected, labelled as Contao labels them.

Without a selection the bundle changes nothing. The main icons that Contao always shows remain visible. Right-clicking a row still opens the complete menu.

## Requirements

- Contao 5.7 with PHP 8.3 or later
- Contao 6.0 with PHP 8.4 or later

## Installation

```bash
composer require mandrael/contao-backend-icon-visibility
```

Or search for `mandrael/contao-backend-icon-visibility` in the Contao Manager. No database changes are required.

## Configuration

Back end → System → Settings → section **Back end icons**. The values are stored like the other system settings and apply to all users.

## Notes

- Many icons need space: in narrow windows long titles may be shortened.
- With "Show all icons" Contao renders all icons of very large lists at once instead of on demand. With several hundred rows this costs some loading time, roughly as in Contao 5.3.

## License

MIT, see [LICENSE](LICENSE).
