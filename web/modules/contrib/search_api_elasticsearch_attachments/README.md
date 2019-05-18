# Search API Elasticsearch Attachments
[![CircleCI](https://circleci.com/gh/dakkusingh/search_api_elasticsearch_attachments.svg?style=svg)](https://circleci.com/gh/dakkusingh/search_api_elasticsearch_attachments)

Elasticsearch is generally used to index data of types like string,
number, date, etc.

However, what if you wanted to index a file like a .pdf or a .doc
directly and make it searchable?

This module allows Drupal to index files (attachments) to Elasticsearch by
making use of Elasticsearch data type "attachment".

![Search_API_Elasticsearch_Attachments](https://www.drupal.org/files/search_api_elasticsearch_attachments.jpg)

## Requirements
This module requires:
* Drupal 8
* Search API Module
* Elasticsearch Connector module (8.x-6.0-alpha1 or higher)
* Elasticsearch Version 6.2
* Elasticsearch `ingest-attachment` plugin

## Elasticsearch Plugin Installation
The first step is to install the Elasticsearch plugin: `ingest-attachment`,
which enables ES to recognise the "attachment" data type. In turn, it uses
Apache Tika for content extraction and supports several file types such as
.pdf, .doc, .xls, .rtf, .html, .odt, etc.

```
$ES_HOME> bin/elasticsearch-plugin install ingest-attachment
```
Thats the hard work done.

## Install this module with composer
```
composer require 'drupal/search_api_elasticsearch_attachments:6.x-dev'
```
## Version Information (Important)

You have to choose the correct versions of the module depending on your
Elastic Search Server setup. Please see the table below for
compatibility.

If you are using Elasticsearch Connector 8.x-6.0-alpha1 or higher, 
please use 8.x-6.x-dev of
*search_api_elasticsearch_attachments* module.

<table>
  <tr>
    <th>Search API Elasticsearch Attachments</th>
    <th>Elasticsearch Connector</th>
    <th>Elasticsearch Version</th>
    <th>Attachment Plugin Support</th>
  </tr>
  <tr>
    <td>8.x-1.x</td>
    <td>8.x-5.x</td>
    <td>5x</td>
    <td>Mapper Attachments Plugin</td>
  </tr>
  <tr>
    <td>8.x-5.x</td>
    <td>8.x-5.x</td>
    <td>5x</td>
    <td>Ingest Attachment Processor Plugin</td>
  </tr>
  <tr>
    <td>8.x-6.x</td>
    <td>8.x-6.x</td>
    <td>6x</td>
    <td>Ingest Attachment Processor Plugin</td>
  </tr>
</table>

## Elasticsearch Connector module (8.x-6.0-alpha1) compatibility.
8.x-6.0-alpha1 version of Elasticsearch Connector module requires a patch.
These are applied automatically by composer.

Sit back and let composer do the hard work for you. Following Patches that will
get auto applied by composer:
* Issue #2918138: Support for alterParams()

## Elasticsearch Attachments Configuration
### Enable and Configure the Elasticsearch Attachments Processor
![Enable_the_Processor](https://www.drupal.org/files/Screen_Shot_2017-12-19_at_11_39_06_pm.jpg)
