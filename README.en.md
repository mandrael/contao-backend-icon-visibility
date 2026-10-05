# Back end icon visibility for Contao

[Deutsche Version](README.md)

Since Contao 5.5 the back end lists show only a few icons directly in the row (edit, publish, child records). Copy, move, delete, details, versions and others live in the "..." menu and need an extra click.

This bundle lets you choose in the system settings which icons stay visible in the row: for all lists, per area, or simply all of them as in Contao 5.3. If their group allows it, users can make their own selection in their profile.

## Features

- **Show all icons:** every list shows all icons in the row and the "..." menu disappears. Large site structures then load all icons right away as well.
- **Show in the row:** first "In all lists" for icons that should be visible in every list that has them, for example "Details". This includes lists of other extensions. Below, ten areas add to the selection for their lists: pages, articles, content elements, news, events, FAQ, newsletters and recipients, forms and form fields, files, members.
- **Keep in the "..." menu:** built the same way and takes precedence. This allows, for example, "details everywhere except for members" or "all icons as in Contao 5.3, only delete stays in the menu".
- **New after/into:** the buttons that create a record after or inside another one can be shown as icons too.
- **Own selection per user:** a user group can be set to "Allow own icon selection". Its members can then switch on their own selection in their profile, which replaces the system settings. It is prefilled with them when switched on and only shows areas whose back end modules the user may access. Administrators always may.
- Only icons that actually exist in the respective list can be selected, labelled as Contao labels them. Areas of extensions that are not installed (such as news or FAQ) are left out.

Without a selection the bundle changes nothing. The main icons that Contao always shows remain visible. Right-clicking a row still opens the complete menu.

## Requirements

- Contao 5.7 with PHP 8.3 or later
- Contao 6.0 with PHP 8.4 or later

## Installation

```bash
composer require mandrael/contao-backend-icon-visibility
```

Or search for `mandrael/contao-backend-icon-visibility` in the Contao Manager. Then update the database (Contao Manager or `vendor/bin/contao-console contao:migrate`): the bundle adds fields for the own selection to the user and user group tables.

## Configuration

Back end → System → Settings → section **Back end icons**. The values are stored like the other system settings and apply to all users without an own selection.

Own selection: User management → User groups → section **Back end icons** → "Allow own icon selection". The users then find the setting in their profile.

## Notes

- Many icons need space: in narrow windows long titles may be shortened.
- With "Show all icons" Contao renders all icons of very large lists at once instead of on demand. With several hundred rows this costs some loading time, roughly as in Contao 5.3.

## License

MIT, see [LICENSE](LICENSE).
