# Roadmap

Current new feature roadmap for Elastic Search module.
Subject to change

## 1.1

* Better handling of analyzer language handling
    - Custom analyzers should be able to use tokens to add language specific analyzers to them, so they can be auto generated for different languages as map build time
    - Add options to specify directly for each image
* Default mapping values for field via config
* Separate tools module with plugin and event listener generators for drupal console

### Indices

* Update mappings without index deletion (push, copy, delete, alias)

# Search

Needs a few things:

* DQL field
* Results preview
* views integration
* custom blocks


## Possible Future Features - No Version Specified

* Handle comments mapping and indexing
* Compare local and on server index mapping
* Better Kibana integration, iframe dashboards etc...
