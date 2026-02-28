---
title: Markdown Features Demo
slug: markdown-features
date: 2026-02-27
tags: [markdown, demo]
category: demos
excerpt: A showcase of all supported markdown features including GFM extensions.
---

# Markdown Features Demo

This post demonstrates the markdown features supported by the package.

## Text Formatting

You can use **bold**, *italic*, ~~strikethrough~~, and `inline code`.

## Lists

- Unordered item one
- Unordered item two
  - Nested item

1. Ordered item one
2. Ordered item two

## Task Lists

- [x] Install the package
- [x] Write a blog post
- [ ] Deploy to production

## Tables

| Method | Description |
|--------|-------------|
| `all()` | All published posts |
| `find($slug)` | Single post by slug |
| `latest()` | Posts sorted by date |
| `whereTag($tag)` | Filter by tag |
| `whereCategory($cat)` | Filter by category |

## Code Blocks

```php
$posts = MdBlog::whereTag('laravel')
    ->sortBy('title');
```

## Blockquotes

> Markdown is a lightweight markup language that you can use to add formatting to plain text documents.

## Links

Visit the [Laravel documentation](https://laravel.com/docs) for more information.
