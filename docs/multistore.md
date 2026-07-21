# Multistore behavior

Bees Blog 1.9 stores category and post content per shop while keeping one
shared entity identifier. The back-office shop context is authoritative:

- **All shops** creates, updates, associates, or deletes the item in every
  shop the employee may access.
- **Shop group** applies the operation to every authorized shop in that group.
- **Single shop** changes only that shop's association and values.

In an all/group edit, values are initially loaded from the first associated
shop in the active context and are propagated to the complete context when
saved. The native shop-association tree remains visible, while the native
context selector defines the write scope.

## Data ownership

| Data | Scope |
| --- | --- |
| Post/category status, position, dates, category/parent, author, comments and views | Shop |
| Titles, content, SEO fields, language status and URL rewrite | Shop + language |
| Related products | Shop |
| Default post/category image | Shop |
| Optional post/category image override | Shop + language |
| Module configuration | Native thirty bees global/group/shop configuration inheritance |
| Blog route prefix | Global/group/shop configuration + language |
| Entity identifier and creation date | Global |
| Image-type definitions | Global definitions with per-shop associations |

The image form contains a default image and optional overrides for each active
language. Uploads follow the native back-office shop context: All Shops writes
independent files for every authorized shop, group context writes the shops in
that group, and single-shop context writes only that shop. Front-office image
resolution uses the language override first, then the shop default, then the
legacy entity image. Existing theme templates remain compatible because the
public image helper performs this resolution from the current context.

Legacy global files are not copied during migration. They remain the fallback
until a scoped image is uploaded. Deleting a legacy fallback in one shop
stores an empty shop-level override, so the file remains available to other
shops. Uploaded files are re-encoded using their detected image format and
all configured thumbnails are generated under shop/language-specific names.

## URL rewrites

Post and category rewrites are unique per shop and language. The database key
is `(id_shop, id_lang, link_rewrite)`, and every front-office lookup joins the
language row to the same shop association. Therefore:

- one shared post can use a different rewrite in each shop;
- two shop-specific posts can use the same rewrite in different shops;
- duplicate rewrites inside the same shop and language are rejected;
- `category` and `page` remain reserved post rewrites because they are blog
  routes.

The main blog prefix is also translated and follows the current configuration
shop context. For example, the same shop can use `/en/blog/...` and
`/fr/actualites/...`, while another shop can configure different prefixes.
The route matcher is restricted to prefixes configured for the requested shop,
and URL generation selects the prefix for the requested language. Theme
templates do not need to pass or render this internal route parameter.

## Upgrade and shop duplication

The 1.9.0 upgrade copies legacy translations to every existing association,
moves mutable values into the shop tables, scopes related products, repairs
missing associations, adds shop-aware keys, and creates the scoped image
association table. It also copies every legacy
scalar blog prefix into missing language rows at the same global, group, or
shop scope. The migration is idempotent: rerunning it preserves existing
shop-specific edits and translated route prefixes.

When thirty bees duplicates a shop, the module's native
`actionShopDataDuplication` hook copies post, category, translation,
image-type, related-product, and explicit image associations to the new shop.
Image files are duplicated under target-shop filenames rather than shared.

## Verification

Run against a disposable/local thirty bees installation:

```text
php modules/beesblog/tests/run_multistore_integration.php <thirty-bees-root>
php modules/beesblog/tests/run_runtime_smoke.php <thirty-bees-root>
php modules/beesblog/tests/run_admin_form_smoke.php <thirty-bees-root>
```

The integration test retains the schema upgrade but removes all temporary blog
entities and its temporary shop even if an assertion fails.
