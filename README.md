# Bees Blog

bees blog is the official thirty bees blog module that comes standard with all thirty bees installations allowing you to make blog posts from your shop.

## Description

The official thirty bees blog module, bees blog comes with all thirty bees installations. This module will allow you to integrate a blog with your thirty bees site out of the box. Blogs are very important tools for SEO that can be used to drive traffic to your site.

The bee blog will allow you to create blog posts, have featured and recent posts on your home page as well.

## License

This software is published under the [Academic Free License 3.0](https://opensource.org/licenses/afl-3.0.php)

## Contributing

thirty bees modules are Open Source extensions to the thirty bees e-commerce solution. Everyone is welcome and even encouraged to contribute with their own improvements.

For details, see [CONTRIBUTING.md](https://github.com/thirtybees/thirtybees/blob/1.0.x/CONTRIBUTING.md) in the thirty bees core repository.

## Packaging

To build a package for the thirty bees distribution machinery or suitable for importing it into a shop, run `tools/buildmodule.sh` of the thirty bees core repository from inside the module root directory.

For module development, one clones this repository into `modules/` of the shop, alongside the other modules. It should work fine without packaging.

## Version 1.9 update: multilanguage and multistore

Bees Blog 1.9 stores category and post content per shop while keeping one
shared entity identifier. It adds shop-scoped posts, categories, translations,
URL rewrites, related products, translated blog route prefixes, default and
per-language images, and native All Shops, shop-group, and single-shop Back
Office workflows.

### Back Office shop context

The native Back Office shop context limits the available write scope:

- **All Shops** allows selecting shops the employee may access.
- **Shop group** limits the selection to authorized shops in that group.
- **Single shop** changes only that shop's association and values.

In an All Shops or shop-group edit, values are initially loaded from the first
associated shop in the active context and are saved to the shops checked in
the shop-association tree. Unchecked associations in that context are removed;
associations outside it are preserved. At least one shop must be selected.
Actions without an association form, such as deletion, use the active context.

Module settings follow the same overwrite rule. Saving in All Shops removes
older group- and shop-specific Bees Blog configuration rows, so values such as
the meta title and translated blog URL prefix become effective everywhere.
Saving in a shop-group context removes the shop-specific overrides belonging
to shops in that group. Saving in a single-shop context keeps all other shops
unchanged.

### Data ownership

| Data | Scope |
| --- | --- |
| Post/category status, position, dates, category/parent, author, comments, and views | Shop |
| Titles, content, SEO fields, language status, and URL rewrite | Shop + language |
| Related products | Shop |
| Default post/category image | Shop |
| Optional post/category image override | Shop + language |
| Module configuration | Native thirty bees global/group/shop configuration inheritance |
| Blog route prefix | Global/group/shop configuration + language |
| Entity identifier and creation date | Global |
| Image-type definitions | Global definitions with per-shop associations |

### Images

The image form contains a default image and optional overrides for each active
language. Uploads on post/category saves write independent files for the shops
selected in the association tree within the active Back Office context.
Single-shop context writes only that shop.

Front Office image resolution uses the language override first and then the
shop default. If neither association has a valid file, no image is returned.
Existing theme templates remain compatible because the public image helper
performs this resolution using the current shop and language context.

The 1.9 migration inspects legacy global files and the obsolete post-shop
image value once, selects the newest valid legacy original when necessary,
and creates independent shop-default files for every associated shop. Legacy
files are removed only after successful conversion, so runtime requests never
scan old filenames or maintain deletion tombstones.

Uploaded and migrated originals are copied byte-for-byte, preserving their
original extension, dimensions, encoding, embedded metadata, and file size.
Resized derivatives use the image extension configured in thirty bees under
Preferences > Images. The derivative extension is stored with the image
association, so Front Office loading remains a direct lookup rather than
scanning possible extensions. All configured thumbnails are generated under
shop/language-specific names.

### URL rewrites and the translated blog prefix

Post and category rewrites are unique per shop and language. The database key
is `(id_shop, id_lang, link_rewrite)`, and every Front Office lookup joins the
language row to the same shop association. Therefore:

- One shared post can use a different rewrite in each shop.
- Two shop-specific posts can use the same rewrite in different shops.
- Duplicate rewrites inside the same shop and language are rejected.
- `category` and `page` remain reserved post rewrites because they are blog
  routes.

The main blog prefix is also translated and follows the current configuration
shop context. For example, the same shop can use `/en/blog/...` and
`/fr/actualites/...`, while another shop can configure different prefixes.
The route matcher is restricted to prefixes configured for the requested shop,
and URL generation selects the prefix for the requested language. Theme
templates do not need to pass or render this internal route parameter. On post
and category pages, the standard language-switch link is decorated so it loads
the same entity in the target language with that language's route prefix and
rewrite. This avoids query-string duplicates and does not require changes to
the language-switcher or theme templates.

### Upgrade and shop duplication

The 1.9.0 upgrade copies legacy translations to every existing association,
moves mutable values into the shop tables, scopes related products, repairs
missing associations, adds shop-aware keys, and creates the scoped image
association table. It converts legacy images to explicit shop defaults and
copies every legacy scalar blog prefix into missing language rows at the same
global, group, or shop scope.

The migration is idempotent: rerunning it preserves existing shop-specific
edits, image associations, and translated route prefixes.

When thirty bees duplicates a shop, the module's native
`actionShopDataDuplication` hook copies post, category, translation,
image-type, related-product, and explicit image associations to the new shop.
Image files are duplicated under target-shop filenames rather than shared.

### Internal SQL alias cleanup

Version 1.9 removes the remaining query aliases inherited from the Simple Blog
module and uses Bees Blog-specific aliases consistently:

| Legacy alias | Bees Blog 1.9 alias | Meaning |
| --- | --- | --- |
| `sbp` | `bbp` | Post base table |
| `sbps`, `sbs`, `bs` | `bbps` | Post shop table |
| `sbpl`, `sbl`, `bl` | `bbpl` | Post language table |
| `sbs_scope` | `bbps_scope` | Post shop-context scope |
| `sbc` | `bbc` | Category base table |
| `sbcs`, `scs` | `bbcs` | Category shop table |
| `sbcl`, `scl` | `bbcl` | Category language table |
| `scs_scope` | `bbcs_scope` | Category shop-context scope |

These names are internal SQL and Back Office list-query aliases. Database
table names, stored data, Front Office routes, public helpers, and theme
templates are unchanged, so this cleanup requires no migration or theme edit.

### Verification

Run the following commands against a disposable or local thirty bees
installation:

```text
php modules/beesblog/tests/run_multistore_integration.php <thirty-bees-root>
php modules/beesblog/tests/run_image_integration.php <thirty-bees-root>
php modules/beesblog/tests/run_runtime_smoke.php <thirty-bees-root>
php modules/beesblog/tests/run_admin_form_smoke.php <thirty-bees-root>
```

The multistore suite covers schema upgrades, shop contexts and associations,
configuration precedence, propagation over existing shop rows, shop-specific
slugs, routes, duplication, updates, and deletion. The image
suite independently covers legacy migration, original-file preservation,
thumbnail formats, shop/language resolution, scoped replacements, duplication,
fallbacks, and deletion. Both retain schema upgrades but remove their temporary
blog entities, image files, and shops even if an assertion fails.

## Version 1.10 update: responsive images

Bees Blog 1.10 generates proportional responsive candidates from the one
image uploaded by the editor. It never crops and never enlarges the source.
The default candidate widths are `320, 480, 640, 768, 1024, 1280, 1536,
1920`; widths above an individual source image are skipped.

Candidates use the image extension configured globally by thirty bees. One
additional JPEG fallback is generated for photographic sources, or PNG for a
PNG/GIF source, at the largest eligible configured width. Front Office
templates render a `picture` element containing a width-descriptor `srcset`
and a single fallback `img` with intrinsic dimensions, lazy/eager loading, and
fetch-priority hints.

The bundled Community, Niara, and Warehouse templates declare image slots
from their actual containers, gutters, card grids, and active side columns.
Lazy images use `sizes="auto"` with those theme calculations as the fallback;
eager images use the calculations directly. Candidate widths remain image
resource widths and are intentionally independent of theme layout breakpoints.

### Configuration and regeneration

Merchants can edit the comma-separated candidate widths under **Blog >
Images > Responsive images**. Widths are validated, sorted, deduplicated, and
stored through the native global/shop-group/shop Configuration hierarchy.
Saving settings immediately marks image sets made with different settings as
missing. The same page shows separate **Posts** and **Categories** progress
bars with completed, missing, and failed counts. Each row button processes only
missing, outdated, or failed sets for that source type. **Regenerate all
responsive images** processes pending work across both types.

The regeneration panel follows the core Images page: each source type has a
front-positioned regeneration button and progress bar. **Reset generation
status** marks every source as pending again without deleting the active files,
allowing a complete repeat run with the same configuration.

Generation uses a persistent queue and processes one source image per AJAX
request, so it can be paused and resumed without relying on a long PHP request.
A working versioned generation stays live while its replacement is built and
is switched only after every candidate and fallback has been verified.

### Existing images and migration

The 1.10 upgrade is intentionally non-destructive: it creates the manifest and
generation-queue tables and seeds the default widths, but does not rewrite or
delete existing image files. Until generation, existing images are rendered
through their 1.9 named image type without a `source` element. New uploads
generate their responsive set immediately and update progress automatically.
This avoids a long-running upgrade and lets the merchant choose when storage
and CPU are used.

The old fixed image-type records do not define `srcset` candidates. They remain
stored temporarily for compatibility with old templates and integrations while
the responsive dashboard is the authoritative migration status. Once support
for those legacy helpers is removed in a future compatibility cleanup, the old
definitions and fixed derivatives can be dropped together.

### Theme overrides

Ready-to-copy Niara and Community Theme overrides are included under
`themes/niara/`, `themes/community-theme-default/`, and `themes/warehouse/`.
Copy the selected folder's contents over the matching shop theme and clear the Smarty cache.
The Community package includes the relative template dependencies required
when it creates a new Bees Blog override directory.

Additional verification commands are:

```text
php modules/beesblog/tests/run_fresh_install_smoke.php <thirty-bees-root>
php modules/beesblog/tests/run_upgrade_smoke.php <thirty-bees-root>
php modules/beesblog/tests/run_responsive_image_integration.php <thirty-bees-root>
php modules/beesblog/tests/run_responsive_template_smoke.php <thirty-bees-root>
php modules/beesblog/tests/run_responsive_admin_smoke.php <thirty-bees-root>
php modules/beesblog/tests/run_theme_override_frontend_smoke.php <thirty-bees-root> <base-url>
```

The fresh-install smoke test is destructive: use a disposable installation.
It removes Bees Blog, installs it from an empty module schema, and leaves the
new installation active. It verifies that the clean 1.10 schema includes the
cumulative 1.9 multistore changes as well as the 1.10 responsive-image state.

## Roadmap

#### Short Term

* None currently.

#### Long Term

* None currently.
