# Redirect Options

Provides functionality for adding redirect type info to redirects by taxonomy based selection field.

### Motivation
Va.gov has several legacy redirects that need to be migrated into drupal. Some are server level, some are template based.
Va.gov uses a headless setup with graphql queries generated for consumption via metalsmith frontend. In the redirect query,
information about the original redirect source is required, so this module associates a new vocabulary taxonomy term to indicate
redirect type with each redirect.

### What it does
 * Creates a new vocabulary: `Type of Redirect` and adds the terms `Server` and `Template`. You will need to change the terms to suit
 your use case.
 * Adds `Select redirect type` selection field to redirect form, pulling values from `Type of Redirect` vocabulary
 * Creates a new table: `redirect_options` used for storing redirect type and redirect association
 * Adds the redirect type to the `redirect` table `title` column. This will change the `To` column here: admin/config/search/redirect to output the redirect type as the name of the redirect link for each result. This can be modified in the view: `admin/structure/views/view/redirect/edit/page_1`

## Installation

### Composer
If your site is [managed via Composer](https://www.drupal.org/node/2718229), use Composer to
download the module:
   ```sh
   composer require "drupal/redirect_options"
   ```
