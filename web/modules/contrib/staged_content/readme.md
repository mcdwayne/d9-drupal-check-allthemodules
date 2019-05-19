# Staged content 

## Introduction
This module allows a simple way to provide "staged" content to be 
injected into projects. Providing a basic set during a development 
cycle.

## Basic usage 
### Exporting content 
Exporting the content from a site into files. 
```
    # Export the content based on the data in a config file. 
    # Note that the output dir can be selected with the --folder=OUTPUT_DIR option.
    # Marker name is a placeholder (allowing for subfoldering in the set) and can be passed
    # as a string. 
    # e.g in this example all the prod data is in /path/to/dir/prod 
    # and all the acc data is in /path/to/dir/acc
    drush staged-content-export /path/to/my/config.yml --folder=/path/to/dir/MARKER_NAME 
```
A Sample config file would look as follows: 
```
    # Defines which markes can be detected. Sorting the data over
    # various subsets. This makes content sets easier to maintain. 
    markers:
      - acc
      - test
      - dev
    # Which entity types should be exported.
    entity_types:
      node:
        entityType: node
        # Include the original entity id in the exported data. 
        # Defaults to FALSE, if not specified only the uuid is considered relevant.
        includeId: TRUE 
      term:
        entityType: taxonomy_term
      user:
        entityType: user
      block_content:
        entityType: block_content
      menu_link_content:
        entityType: menu_link_content
```

## Importing content
```
    # Import all the data from a directory:
    drush staged-content-import /path/to/my/config.yml
```
