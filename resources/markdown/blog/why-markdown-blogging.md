---
title: Why I Switched to Markdown for Blogging
slug: why-markdown-blogging
date: 2026-02-25
tags: [markdown, workflow, opinion]
category: articles
excerpt: A CMS felt like overkill. Here's why plain markdown files won me over.
---

# Why I Switched to Markdown for Blogging

I used to reach for a full CMS every time I needed a blog. WordPress, Ghost, even headless options like Strapi. They all work, but they all felt heavy for what I actually needed: a place to write.

## The Problem

Most of my projects are Laravel apps with Inertia frontends. Adding a CMS means:

- Another database table (or an entire separate system)
- An admin panel to build and maintain
- Rich text editors that produce unpredictable HTML
- Deployment complexity for something that changes once a week

All I wanted was to write a post, commit it, and deploy.

## The Solution

Markdown files in your repo. That's it.

```
resources/markdown/blog/
├── why-markdown-blogging.md
├── laravel-12-first-look.md
└── deploying-with-confidence.md
```

Each file has YAML front matter for metadata and markdown for content. Your editor is your CMS. Git is your version history. Your deploy pipeline publishes your posts.

## What You Gain

1. **Version control** — every edit is a commit
2. **Portability** — plain text files work everywhere
3. **Speed** — no database queries, just file reads (with caching)
4. **Simplicity** — no admin panel to secure or maintain
5. **Developer experience** — write in your IDE with syntax highlighting

## When This Isn't Right

If you need non-technical users to publish content, this isn't for them. If you need scheduling, approval workflows, or a media library, reach for a proper CMS.

But if you're a developer writing for developers? Markdown files are hard to beat.
