# Taxonomy Importer

Provides a csv-importer to add terms to existing taxonomies.


## Table of content

  * [Taxonomy Importer](#taxonomy-importer)
    * [Table of content](#table-of-content)
    * [Features](#features)
    * [Requirements](#requirements)
    * [Installation](#installation)
    * [Configuration](#configuration)
    * [Usage](#usage)
    * [Maintainers](#maintainers)


## Features

  * Hierarchical import
  * Purge existing data before import
  * Allows to update fields as well


## Requirements

This module requires no modules outside of Drupal core.


## Installation

 * Install the module as you would normally install a contributed Drupal module.


## Configuration

 * Setup permission `import taxonomy csv` for who should be allowed to use
   the importer.


## Usage

Goto `/admin/config/content/taxonomy/csv_import`, you will find some example
csv files for hierarchical and flat import with custom term-fields.
If the taxonomy is missing a field, you have to add it first or it just will be
ignored.

## Maintainers

 * Mathias (mbm80) - https://www.drupal.org/u/mbm80

Supporting organizations:

 * drunomics - https://www.drupal.org/drunomics
