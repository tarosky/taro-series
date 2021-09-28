# Taro Series

Tags: series, posts, news  
Contributors: tarosky, Takahashi_Fumiki  
Tested up to: 5.8  
Requires at least: 5.4  
Requires PHP: 5.6  
Stable Tag: nightly  
License: GPLv3 or later  
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

A WordPress plugin for creating series.

## Description

This plugin will..

1. Create a custom post type "Series".
2. Add meta box to specified post types to be a part of the series.
3. Display TOC on post. You can change the settings in Theme Customizer.
4. Add "Series Archive".

### Customization

#### Template Structure

To override look and feel, put template in your themes directory.

```
template-parts
- series
  - item.php // Each item in TOC.
  - list.php // TOC template.
```

Copy the template in plugin's directoy and customize.

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


### 1.0.0

* First release.
