# Blog Extension Plugin

The **Blog Extension Plugin** extends [Winter.Blog](https://github.com/wintercms/wn-blog-plugin) with tags, a picture
gallery and file attachments for every post, plus components for tag pages, related posts, breadcrumbs, reading time
and a configurable RSS feed.

[![Version](https://img.shields.io/github/v/release/numencode/wn-blogextension-plugin?style=flat-square&color=0099FF)](https://github.com/numencode/wn-blogextension-plugin/releases)
[![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/numencode/wn-blogextension-plugin?style=flat-square&color=0099FF)](https://packagist.org/packages/numencode/wn-blogextension-plugin)
[![Tests](https://img.shields.io/github/actions/workflow/status/numencode/wn-blogextension-plugin/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/numencode/wn-blogextension-plugin/actions)
[![License](https://img.shields.io/github/license/numencode/wn-blogextension-plugin?label=open%20source&style=flat-square&color=0099FF)](https://github.com/numencode/wn-blogextension-plugin/blob/main/LICENSE.md)

---

## Features

- **Tags** – tag posts in the post form (new tags are created as you type) and manage them under Blog → Tags.
- **Picture gallery** – every post gets a gallery of media library pictures with titles (it replaces Winter.Blog's
  featured images). `post.primaryPicture` is the first published picture.
- **Attached files** – media library files with titles, e.g. PDF documents.
- **Publishing** – pictures and files can be unpublished; they are hidden on the website and visible to backend users.
- **Components** – tag list, post tags, tag page, related posts, breadcrumbs, reading time and an RSS feed, with
  Bootstrap 5 starter templates.
- **Menu items** – "Blog tag" and "All blog tags" for Winter.Pages menus and Winter.Sitemap.
- **Translations** – tag names and slugs (with translated tag URLs) and picture/file titles are translatable with
  [Winter.Translate](https://github.com/wintercms/wn-translate-plugin).
- **Faker** – a development tool that generates demo categories, tags, posts and pictures (only while debug mode is
  on).
- English and Slovenian translations.

## Requirements

- PHP 8.2 or newer
- [Winter CMS](https://wintercms.com) 1.2 (tested with 1.2.15)
- [Winter.Blog](https://github.com/wintercms/wn-blog-plugin) 2.2 or newer and
  [NumenCode.Fundamentals](https://github.com/numencode/wn-fundamentals-plugin) 2.1 or newer (installed automatically)
- Optional: [Winter.Translate](https://github.com/wintercms/wn-translate-plugin) for translations,
  [Winter.Pages](https://github.com/wintercms/wn-pages-plugin) for menu items,
  [fakerphp/faker](https://github.com/FakerPHP/Faker) for the Faker page (usually installed as a development
  dependency of Winter CMS projects)

## Installation

```bash
composer require numencode/wn-blogextension-plugin
php artisan winter:up
```

## Backend

### Post form

The plugin adds to Winter.Blog's post form:

- **Tags** (tab "Categories"),
- **Pictures** – the gallery, edited inline: drag to reorder, collapse or expand the items one by one or all at once,
  unpublish single pictures,
- **Files** – the attached files, edited the same way.

It also arranges the fields of the "Manage" tab in two columns.

### Settings

Settings → Blog → **Blog Extension**:

| Tab | Settings |
|---|---|
| Extensions | Switch tags, the picture gallery and attached files on or off (all on by default), and edit the excerpt with the rich editor (off by default; for the post content, use Winter.Blog's own "Use rich editor" setting). |
| RSS feed | Title, description, category, copyright, generator, language, author, comments anchor, full content. |

### Permissions

| Permission | Allows | Default roles |
|---|---|---|
| `numencode.blogextension.manage_settings` | the settings page | developer, publisher |
| `numencode.blogextension.access_tags` | Blog → Tags and the tags field | developer, publisher |
| `numencode.blogextension.access_pictures` | the picture gallery of posts | developer, publisher |
| `numencode.blogextension.access_files` | the attached files of posts | developer, publisher |
| `numencode.blogextension.access_faker` | Blog → Faker | developer |

### Faker

Blog → **Faker** generates demo data: categories, tags, posts (each with a random category, 1–3 random tags and
optionally 1–3 random pictures from a media library folder). The page only exists while `app.debug` is on and
`fakerphp/faker` is installed; otherwise it returns 404.

## Components

| Component | Shows | Properties |
|---|---|---|
| `blogTagList` | all tags with the number of their published posts | `limit` (0 = all), `sortOrder`, `showPostCount`, `showEmptyTags`, `tagPage` |
| `blogTagPost` | the tags of the page's post | `postAlias`, `tagPage` |
| `blogTagFilter` | the published posts of a tag, paginated (unknown tag → 404) | `slug`, `pageNumber`, `postsPerPage`, `sortOrder`, `noPostsMessage`, `postPage`, `categoryPage`, `tagPage` |
| `blogTagRelated` | published posts sharing tags with the page's post, most shared tags first | `postAlias`, `limit`, `noPostsMessage`, `postPage`, `categoryPage`, `tagPage` |
| `blogBreadcrumbs` | the category path of a category page or of the page's post | `categoryPage`, `postsAlias`, `postAlias`, `divider` |
| `blogReadingTime` | the estimated reading time of the page's post | `postAlias`, `wordsPerMinute` |
| `blogRssFeed` | an RSS feed with the channel details from the settings | Winter.Blog's RSS feed properties |

Naming rules: properties ending in `Page` are the pages used for links (a `tagPage` can be left empty for no tag
links), properties ending in `Alias` name another component on the same page (`postAlias` = Winter.Blog's
`blogPost`, `postsAlias` = `blogPosts`).

Components that use the page's post (`blogTagPost`, `blogTagRelated`, `blogBreadcrumbs`, `blogReadingTime`) also
work when they are placed in the layout.

### Example: a tag page

```ini
title = "Tag"
url = "/blog/tag/:slug/:page?"

[blogTagFilter]
slug = "{{ :slug }}"
pageNumber = "{{ :page }}"
postsPerPage = 10
postPage = "blog/post"
categoryPage = "blog/category"
tagPage = "blog/tag"
==
{% component 'blogTagFilter' %}
```

### Example: a post page

```ini
title = "Post"
url = "/blog/post/:slug"

[blogPost]
slug = "{{ :slug }}"
categoryPage = "blog/category"

[blogBreadcrumbs]
categoryPage = "blog/category"

[blogReadingTime]

[blogTagPost]
tagPage = "blog/tag"

[blogTagRelated]
limit = 3
postPage = "blog/post"
categoryPage = "blog/category"
tagPage = "blog/tag"
==
{% component 'blogBreadcrumbs' %}
<h1>{{ post.title }}</h1>
{% component 'blogReadingTime' %}
{% component 'blogTagPost' %}

{% for picture in post.pictures %}
    <img src="{{ picture.picture|media|resize(400, 300, { mode: 'crop' }) }}" alt="{{ picture.title }}">
{% endfor %}

{{ post.content_html|raw }}

{% for file in post.files %}
    <a href="{{ file.file|media }}">{{ file.title ?: file.file }}</a>
{% endfor %}

{% component 'blogTagRelated' %}
```

### Template variables

| Variable | Contains |
|---|---|
| `post.tags` | the post's tags (by name); `tag.url` is set by the components |
| `post.pictures`, `post.files` | the published pictures/files (all of them for backend users), in backend order |
| `post.primaryPicture` | the first published picture, or `null` |
| `picture.picture`, `file.file` | the media library path – use `\|media`, and `\|resize(…)` for pictures |
| `breadcrumbs` | the breadcrumbs (`title`, `url`, `isActive`), set by `blogBreadcrumbs` |
| `__SELF__.tags`, `__SELF__.posts`, `__SELF__.tag`, `__SELF__.minutes` | the component data in overrides |

### Overriding templates

Copy a component's template into your theme, e.g. `themes/<theme>/partials/blogTagFilter/default.htm` (the folder is
the component alias used on the page), and change the markup. The default templates use Bootstrap 5 classes.

## Menu items

With Winter.Pages, the menu item types **Blog tag** (one tag) and **All blog tags** (every tag with published posts)
link to a CMS page with a `blogTagFilter` component whose `slug` comes from a URL parameter. Winter.Sitemap uses the
same types; with Winter.Translate, the alternate links use the translated tag slugs.

## Upgrading from 1.x

See [Upgrading from 1.x](CHANGELOG.md#upgrading-from-1x) in the changelog.

---

## Changelog

All notable changes are documented in the [CHANGELOG](CHANGELOG.md).

## Contributing

Please refer to the [CONTRIBUTING](CONTRIBUTING.md) guide for details on contributing to this project.

## Security

If you identify any security issues, email info@numencode.com rather than using the issue tracker.

## Author

The **NumenCode.BlogExtension** plugin is created and maintained by [Blaz Orazem](https://orazem.si/).

For inquiries, contact: info@numencode.com

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
