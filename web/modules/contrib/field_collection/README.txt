CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Restrictions
 * Maintainers


INTRODUCTION
------------

Provides a field collection field to which any number of fields can be attached.

Each field collection item is internally represented as an entity, which is
referenced via the field collection field in the host entity. While
conceptually field collections are treated as part of the host entity, each
field collection item may also be viewed and edited separately.


REQUIREMENTS
------------

This project require the following projects:

 * Field (https://www.drupal.org/docs/8/core/modules/field)


INSTALLATION
------------

Install as you would normally install a contributed Drupal projects. See:
https://drupal.org/documentation/install/modules-themes/modules-8 for further
information.


CONFIGURATION
-------------

 * Add a field collection field to any entity, e.g. to a node. For that use the
   the usual "Manage fields" interface provided by the "field ui" of Drupal
   E.g. "Admin -> Structure-> Content types -> Article -> Manage fields".
    
 * Then go to "Admin -> Structure-> Field collection" to define some fields for
   the created field collection.
   
 * By the default, the field collection is not shown during editing of the host
    entity. However, some links for adding, editing or deleting field collection
    items is shown when the host entity is viewed.
  
 * Widgets for embedding the form for creating field collections in the
    host-entity can be provided by any module. In future the field collection
    module might provide such widgets itself too.


RESTRICTIONS
-------------

 * As of now, the field collection field does not properly respect different
   languages of the host entity. Thus, for now it is suggested to only use the
   field for entities that are not translatable.


MAINTAINERS
-----------

Current maintainers:
 * Joel Muzzerall (jmuzz) - https://www.drupal.org/user/2607886
 * Joel Farris (Senpai) - https://www.drupal.org/user/65470
 * Lee Rowlands (larowlan) - https://www.drupal.org/user/395439
 * Nedjo Rogers (nedjo) - https://www.drupal.org/user/4481
 * Ra Mänd (ram4nd) - https://www.drupal.org/user/601534
 * Renato Gonçalves (RenatoG) - https://www.drupal.org/user/3326031
 * Wolfgang Ziegler (fago) - https://www.drupal.org/user/16747
