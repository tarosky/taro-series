# Taro Series

Tags: series, posts, news  
Contributors: tarosky, Takahashi_Fumiki  
Tested up to: 6.8  
Stable Tag: nightly  
License: GPLv3 or later  
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

A WordPress plugin for creating series.

## Description

This plugin will..

1. Create a custom post type "Series".
2. Add meta box to specified post types to be a part of the series.
3. Display TOC on post. You can change the settings in Theme Customizer or use "Series TOC" block.

### Customization

#### Template Structure

To override look and feel, put template in your themes directory.

```
template-parts
- series
  - item.php // Each item in TOC.
  - list.php // TOC list which include item.php repeatedly.
```

Copy the template in plugin's directoy and customize.

#### Archive Template

Archive page template of articles in series will be searched in your theme's directory and loaded per the hierarchy below:

```
archive-in-series-{series-slug}.php
archive-in-series.php
archive.php
index.php
```

#### Hooks

Many hooks are also available. Search your plugin direcoty with `'taro_series_'` and you can find them easily :)

#### Functions

See `inludes/functions.php` and you can find useful template tags and functions.

## Installation

### From Plugin Repository

Click install and activate it.

### From Github

See [releases](https://github.com/tarosky/taro-series/releases).

## FAQ

### Where can I get supported?

Please create new ticket on support forum.

### How can I contribute?

Create a new [issue](https://github.com/tarosky/taro-series/issues) or send [pull requests](https://github.com/tarosky/taro-series/pulls).

## Changelog

### 2.0.0

* Add WP_Query orderby parameter `series-updated`.
* Bump minimum PHP requiremtns to PHP 7.2 and over.
* Bump minimum WordPress version to 5.9.

### 1.1.2

* Fix bug in articles count.

### 1.0.0

* First release.
