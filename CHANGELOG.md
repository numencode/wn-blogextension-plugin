# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0] - 2026-11-11

A rewrite on NumenCode.Fundamentals 2.1, Winter.Blog 2.2+ and Winter CMS 1.2.15. It contains breaking changes –
see [Upgrading from 1.x](#upgrading-from-1x).

### Security

- **Faker:** generating demo data was a GET request without a permission check, so any backend user could run it,
  and any page an administrator visited could trigger it (no CSRF protection). It also ran in production. The Faker
  is now a page (Blog → Faker) that requires the `access_faker` permission, generates data with a CSRF-protected AJAX
  request and only exists while debug mode is on.
- Category names in the default templates are escaped (they were inserted into links as raw HTML).

### Added

- **Translatable tags:** tag names and slugs are translatable with Winter.Translate. Tag pages accept the translated
  slug, the language switcher links to the tag in the other language, and Winter.Sitemap gets the alternate URLs.
- **Menu item types** "Blog tag" and "All blog tags" for Winter.Pages menus and Winter.Sitemap.
- Tag validation: the name is required, the slug is required, unique and URL-safe; the slug can be edited (it is still
  generated from the name).
- `blogTagFilter` and `blogTagRelated`: a `noPostsMessage` property.
- `blogTagList`: sorting by the number of posts.
- `post.primaryPicture` (also `post.primary_picture`) as a model attribute.
- Default roles for the permissions (developer and publisher; Faker: developer only).
- Slovenian translation (`lang/sl`).

### Changed

- **Requires PHP 8.2+, NumenCode.Fundamentals 2.1+ and Winter.Blog 2.2+.** `fakerphp/faker` is no longer required
  (suggested, for the Faker page).
- **Backend:** pictures and files are edited with the Fundamentals `relationrepeater` (collapsed, with the item title
  in the header, "Expand all" / "Collapse all", the Translate language selector). The Tags pages use Winter's default
  views.
- **Pictures and files** are published by default and use the Fundamentals `Publishable` trait: unpublished items are
  hidden on the website and visible to backend users. They are deleted together with their post.
- **The feature switches** (tags, picture gallery, files) are on by default. Stored settings are kept.
- **Component properties** follow one naming scheme (links end in `Page`, other components in `Alias`, counts are
  `limit`) – see the upgrade guide for the renamed properties.
- **`blogTagRelated`** uses the post of the page's `blogPost` component (`postAlias`) instead of a `slug` property.
- **`blogBreadcrumbs`** sets the page variable `breadcrumbs` in every case; the post crumb links to the post page.
- Components that use the page's post also work in the layout.
- **Templates** use Bootstrap 5 markup, translated strings instead of the Translate-only `|_` filter, and Winter's
  core image resizer.
- The RSS feed is valid XML in every case (no CDATA sections), uses the rendered post content and lists all categories
  of a post.
- Tag and category queries count and list published posts only.
- Strict types, parameter and return types and PSR-12 throughout.

### Fixed

- The plugin replaced Winter.Blog's `hasMany` relations of the Post model instead of adding to them.
- Pictures and files of deleted posts stayed in the database.
- The category list "Slug" column was misconfigured.
- `blogBreadcrumbs`, `blogReadingTime` and `blogTagPost` failed on pages without a post.
- `blogReadingTime` counted words with non-ASCII letters (č, š, ž, …) as several words.
- `blogTagRelated` used MySQL-only SQL, didn't check that the post is published, and its `results` validation
  rejected numbers like 10.
- `blogTagList` loaded all posts of every tag to count them and counted unpublished posts.
- `blogTagFilter` showed an empty page for an unknown tag (now 404) and accepted invalid page numbers.
- Tag names longer than 25 characters failed with a database error; duplicate slugs were possible.
- Implicitly nullable parameters (deprecated in PHP 8.4).

### Removed

- The **"WYSIWYG content" setting** – Winter.Blog has its own "Use rich editor" setting. The update copies a stored
  "on" value to it.
- The Faker settings tab (the options are on the Faker page now) and the `/thumb` folder handling.
- The "Editors" settings tab – the excerpt editor switch is in the "Extensions" tab (stored values are kept).
- The `blogBreadcrumbs` property `compatibility`.
- The controller views that used the removed Fundamentals `numencode_partial()`.

### Upgrading from 1.x

1. **Requirements** – PHP 8.2+, NumenCode.Fundamentals 2.1+ (see its
   [upgrade guide](https://github.com/numencode/wn-fundamentals-plugin/blob/main/CHANGELOG.md#upgrading-from-1x))
   and Winter.Blog 2.2+. Run `php artisan winter:up` and `php artisan cache:clear` after updating. The update:
   - widens the tag columns and makes slugs unique (empty slugs are generated from the name, duplicates get the tag
     ID appended),
   - deletes pictures and files whose post no longer exists and adds a foreign key, so they are deleted with their
     post,
   - moves the "WYSIWYG content" setting to Winter.Blog's "Use rich editor".
2. **Feature switches** – tags, the picture gallery and files are on by default. If you never saved the settings,
   they appear in the post form now; switch them off in Settings → Blog → Blog Extension if you don't use them.
3. **Component properties** – rename them in pages and layouts:

   | Component | 1.x | 2.0 |
   |---|---|---|
   | all | `tagFilterPage` | `tagPage` |
   | `blogTagList` | `results` | `limit` |
   | `blogTagList` | `postsCount` | `showPostCount` |
   | `blogTagList` | `emptyTags` | `showEmptyTags` |
   | `blogTagRelated` | `results` | `limit` |
   | `blogTagRelated` | `slug` | `postAlias` (the alias of the page's `blogPost` component, default `blogPost`) |
   | `blogBreadcrumbs` | `categoryAlias` | `postsAlias` |
   | `blogBreadcrumbs` | `compatibility` | removed – use the `breadcrumbs` page variable and your own markup |
   | `blogReadingTime` | `readingSpeed` | `wordsPerMinute` |

   `blogTagList` `sortOrder` accepts `name asc|desc`, `posts_count desc` and `created_at asc|desc`; other values fall
   back to `name asc`.
4. **Theme overrides** of the component templates:
   - `blogBreadcrumbs`: `__SELF__.blogBreadcrumbs` → `__SELF__.breadcrumbs`;
   - `|resize('750x300.crop')` → `|resize(750, 300, {mode: 'crop'})` (also in your own templates using
     `post.primaryPicture`);
   - `post.primaryPicture` is a `Picture` model now (`.picture`, `.title` work as before);
   - the default templates use Bootstrap 5; review overrides that copy them.
5. **Faker** – it's on the page Blog → Faker (only while `app.debug` is on) and needs `fakerphp/faker`
   (`composer require --dev fakerphp/faker` if your project doesn't have it).
6. **Unpublished pictures and files** are no longer shown on the website. Check the gallery of your posts if you
   relied on that.

## [1.0.0] - 2025-02-08

### Added

- Initial release of the **Blog Extension Plugin** for Winter CMS 1.2.7: tags, picture gallery, file attachments,
  WYSIWYG editors, RSS feed settings, content faker, breadcrumbs and reading time.

[Unreleased]: https://github.com/numencode/wn-blogextension-plugin/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/numencode/wn-blogextension-plugin/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/numencode/wn-blogextension-plugin/releases/tag/v1.0.0
