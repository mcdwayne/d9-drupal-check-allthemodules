# Drupal Icecat

[Youtube demo](https://www.youtube.com/watch?v=oKJ_wHfhI84)

## Purpose

This module implements the [Icecat library](https://github.com/haringsrob/icecat) and adds
mapping functions to Drupal.

It uses the EAN code (Soon sku + brand) to get structured data from Icecat and maps it to
Drupal entities.

In combination with [Drupal commerce](https://www.drupal.org/project/commerce), this allows you to create a full webshop with products just
by importing a price + ean xml. (Importer is not part of this module).

## Icecat

[Icecat](http://icecat.biz/) is a free Open catalog, containing product data for thousands of
products (Mainly IT).

## Status

### Current features

- Creating mappers for each entity type
- Supports: Specifications, attributes
- Updates the entity on save

### WIP

- Option to not update after first save
- Auto importing images
- Caching

### Future

The future of this module includes more data sources to cover a broader type of products.

