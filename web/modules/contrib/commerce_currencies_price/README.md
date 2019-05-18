CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

Field type based on commerce price field and currency entity.
It allows to enter prices for all currencies inside one field, 
instead of creating per currency field. Data are serialized 
with this field.

You have available method $entity->field_name->toPrices() to get list of
all currencies price.

Useful for cases when you need to save prices but not necessarily utilizing
querying by currency prices or resolving them.


REQUIREMENTS
------------
 
 * Contributed module Commerce 2 - `composer require drupal/commerce`


INSTALLATION
------------

 * Standard


CONFIGURATION
-------------

 * None


MAINTAINERS
-----------

The 8.x-1.x branch was created by:

 * Valentino Medimorec (valic) - https://www.drupal.org/u/valic
