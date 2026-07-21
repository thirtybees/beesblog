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
| Module configuration and blog route prefix | Native thirty bees global/group/shop configuration inheritance |
| Entity identifier and creation date | Global |
| Image files and image-type definitions | Shared by entity; image-type associations are per shop |

The physical image filename is based on the global entity identifier. Shops
needing different images should use different post/category entities. This
avoids two shops overwriting the same generated thumbnail set.

## URL rewrites

Post and category rewrites are unique per shop and language. The database key
is `(id_shop, id_lang, link_rewrite)`, and every front-office lookup joins the
language row to the same shop association. Therefore:

- one shared post can use a different rewrite in each shop;
- two shop-specific posts can use the same rewrite in different shops;
- duplicate rewrites inside the same shop and language are rejected;
- `category` and `page` remain reserved post rewrites because they are blog
  routes.

## Upgrade and shop duplication

The 1.9.0 upgrade copies legacy translations to every existing association,
moves mutable values into the shop tables, scopes related products, repairs
missing associations, and adds shop-aware keys. The migration is idempotent;
rerunning it does not overwrite later shop-specific edits.

When thirty bees duplicates a shop, the module's native
`actionShopDataDuplication` hook copies post, category, translation,
image-type, and related-product associations to the new shop.

## Verification

Run against a disposable/local thirty bees installation:

```text
php modules/beesblog/tests/run_multistore_integration.php <thirty-bees-root>
php modules/beesblog/tests/run_runtime_smoke.php <thirty-bees-root>
```

The integration test retains the schema upgrade but removes all temporary blog
entities and its temporary shop even if an assertion fails.
