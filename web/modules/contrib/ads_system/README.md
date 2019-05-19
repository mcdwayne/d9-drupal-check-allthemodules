# Ads System Module for Durpal8

Ads System Module provides a way to manage Ads/Tags by entities and exposes in blocks. Can manage Ads from Google Ads scripts or custom Ads defined with fields, show them per specific content in any place of the site on the regions or render a specific Ad inside another entity using EntityReference, also you can display with Views, each Ad is configured with  size and the breakpoints to render in specific resolution of screen size.

Manage ads across entities and entity types (config), that generate 
dynamically a block per Ad Type for show one Ad.

## Principal features

- General settings: Global configuration for use on all Ads.
    Sizes: Definition of variants of Ad sizes.
    Breakpoints: Resolutions to show the Ads per Screen size width.
    Head Script: Main Script to render between <head> tags e.g. Google Ads

- Ad-Types: Entity Config (Ad bundles - Types of ads), variants with customs fields.

- Ad Entity: Entity Content for Ad.
    Entity Properties per each Ad: (Name, Size Breakpoint Min y Max)
    Fields, View modes, View Forms, Views, and EntityReference Integrations.
    Theme suggestions.

- Ad Blocks: Per each Ad-Type it’s created a Block to display all Ads content by bundle according to the breakpoints.
    Positioning on a region.
    Visibility options.
    Theme suggestions.

## How to install

1. Install module.
2. Go to ```/admin/structure/ad-types/settings``` and configure.
3. Create/Edit in your theme the template html.html.twig and add 
between head tag the lines.
```
<!-- Init Script to Ads System -->
 {{ ads_system_script_init | raw }}
<!-- END Init Script to Ads System -->
```





