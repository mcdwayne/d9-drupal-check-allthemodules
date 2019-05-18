# Search API References

A Drupal 8 module to provide an additional data type for Search API indexes.

## Usage

Search API References provides a "reference" processor to Search API backends
for indexing. This can be used for entity reference fields.

### Limitations

This module only supports search_api_solr backend at this point, and is coded to
enforce this support. Any referenced content should be supported though.

## Concept

An example usage of this module would be to allow the indexing of
[Field Collections](http://drupal.org/project/field_collection) (or
[Paragraphs](https://www.drupal.org/project/paragraphs)) content as child
documents in Apache Solr backend.

## About

This module was originally developed for use by the [Office of Information
Technology at Rutgers University](https://oit.rutgers.edu). For questions
or support, please contact the [OIT Webmaster](webmaster@oit.rutgers.edu).
