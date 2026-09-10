# Achievement Tree

> 🚧 **开发中 (Work in Progress)**:功能与接口仍可能变化,暂不建议用于生产环境。

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/thefish12357/flarum-achievement-tree.svg)](https://packagist.org/packages/thefish12357/flarum-achievement-tree)

A [Flarum](http://flarum.org) extension. Add a user achievement tree (badges) to your forum.

## Features

- Show an achievement badge row under each post: hover for the name, click for details.
- Full achievement tree on the user profile page: unlocked highlighted, locked greyed out.
- Users submit proof materials from the settings page; admins review and grant achievements.

## Installation

```sh
composer require thefish12357/flarum-achievement-tree:"*"
```

## Updating

```sh
composer update thefish12357/flarum-achievement-tree:"*"
php flarum migrate
php flarum cache:clear
```

## Development

See `AGENTS.md` in the parent folder for the build commands, architecture decisions and progress.

## Links

- [Packagist](https://packagist.org/packages/thefish12357/flarum-achievement-tree)
- [GitHub](https://github.com/thefish12357/flarum-achievement-tree)
