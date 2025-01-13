# Blog Extension Plugin

The **NumenCode Blog Extension** plugin for Winter CMS enhances the functionality of the original Winter CMS
Blog Plugin by adding a variety of useful features designed to improve the blog post management experience.
These features can be easily enabled in the CMS or used as components, offering flexibility for both
developers and content creators.

[![Version](https://img.shields.io/github/v/release/numencode/wn-blogextension-plugin?sort=semver&style=flat-square&color=0099FF)](https://github.com/numencode/wn-blogextension-plugin/releases)
[![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/numencode/wn-blogextension-plugin?style=flat-square&color=0099FF)](https://packagist.org/packages/numencode/wn-blogextension-plugin)
[![Checks](https://img.shields.io/github/check-runs/numencode/wn-blogextension-plugin/main?style=flat-square)](https://github.com/numencode/wn-blogextension-plugin/actions)
[![Tests](https://img.shields.io/github/actions/workflow/status/numencode/wn-blogextension-plugin/main.yml?branch=main&label=tests&style=flat-square)](https://github.com/numencode/wn-blogextension-plugin/actions)
[![License](https://img.shields.io/github/license/numencode/wn-blogextension-plugin?label=open%20source&style=flat-square&color=0099FF)](https://github.com/numencode/wn-blogextension-plugin/blob/main/LICENSE.md)

---

## Target Audience

The NumenCode Blog Extension plugin is designed for Winter CMS users who want to extend the functionality of their
blog with enhanced features. It is ideal for developers looking to add dynamic components to their blog posts, as well
as content creators who need a more user-friendly editing experience. This plugin caters to website owners and agencies
seeking a customizable and feature-rich blogging system, offering tools to manage media, improve SEO with tags and
breadcrumbs, and streamline content creation with WYSIWYG editors. Whether you're building a personal blog, a corporate
website, or an online magazine, this extension provides the flexibility to meet various blogging needs.

## Installation

This plugin is available for installation via [Composer](http://getcomposer.org/).

```bash
composer require numencode/wn-blogextension-plugin
```

After installing the plugin you will need to run the migrations.

```bash
php artisan winter:up
```

## Requirements

* [Winter CMS Blog Plugin](https://github.com/wintercms/wn-blog-plugin) version ^2.1 or higher.
* [NumenCode Fundamentals Plugin](https://github.com/numencode/wn-fundamentals-plugin) version ^1.0 or higher.
* [Winter CMS](https://wintercms.com/) 1.2.7 or higher.
* PHP version 8.0 or higher.

---

## Features Overview

This plugin in the extension for the original [Winter CMS Blog Plugin](https://github.com/wintercms/wn-blog-plugin)
which provides a variety of useful features designed to improve the blog post management experience.

This extension introduces a more user-friendly CMS interface for blog post editing, along with powerful additions such as:
- **Picture Gallery** for blog posts
- **File attachments** for each blog post
- **Tagging system** with related posts and tag lists
- **WYSIWYG editor** for both blog content and excerpts
- **RSS feed** which is fully customizable
- **Content faker** for blog categories, posts, and media
- **Breadcrumbs navigation** for blog posts
- **Estimated reading time** display for blog posts

All these features can be easily enabled through the CMS or integrated into your templates as components,
offering flexibility and convenience for developers and content creators alike.

---

## Changelog

All notable changes are documented in the [CHANGELOG](CHANGELOG.md).

---

## Contributing

Please refer to the [CONTRIBUTING](CONTRIBUTING.md) guide for details on contributing to this project.

---

## Security

If you identify any security issues, email info@numencode.com rather than using the issue tracker.

---

## Author

The **NumenCode.BlogExtension** plugin is created and maintained by [Blaz Orazem](https://orazem.si/).

For inquiries, contact: info@numencode.com

---

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

[![License](https://img.shields.io/github/license/numencode/wn-blogextension-plugin?style=flat-square&color=0099FF)](https://github.com/numencode/wn-blogextension-plugin/blob/main/LICENSE.md)
