# Bees Blog theme overrides

These folders contain ready-to-copy overrides for the themes bundled with
thirty bees. Copy the contents of the selected folder into the matching shop
theme directory, preserving the directory structure, then clear the Smarty
cache.

- `niara/` targets `/themes/niara/`.
- `community-theme-default/` targets `/themes/community-theme-default/`.
- `warehouse/` targets the installed Warehouse theme directory, commonly
  `/themes/warehouse/`.

The override templates preserve each theme's existing Bees Blog markup and
render the responsive data prepared by module version 1.10.0.

The Warehouse package follows its native `ph_simpleblog` presentation:
Bootstrap grid cards, `page-heading`/`page-subheading` typography, compact
metadata, Font Awesome icons, light content panels, and matching SCSS source.
