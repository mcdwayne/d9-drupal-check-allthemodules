Entity Normalization
====================

CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers
 
INTRODUCTION
------------
This module makes it really easy to normalize the nodes in a more usefull way
than Drupal does by default. You can define the output of each entity type
(like nodes, users, terms, etc) per bundle using YAML files instead of creating
everything in code.

Some of the key features are:
 * Define output in readable YAML files .
 * Extend definitions: create basic and bundle specific definitions.
 * Different definitions per normalization format.
 * Combine fields in groups without writing code.
 * Add custom data to the output using 'pseudo' fields. 

REQUIREMENTS
------------
This module requires the following modules enabled:

 * Serialization (core module)

RECOMMENDED MODULES
-------------------

 * Entity Normalization - Normalizers (submodule)
   Provides some default normalizers for easy normalizing entities.
 * Rest UI - Enable REST output for nodes.
   https://www.drupal.org/project/restui

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/documentation/install/modules-themes/modules-8


CONFIGURATION
-------------

The module itself doesn't have any configuration options from the interface.
Use RestUI (or code) to enable the JSON (or another) output format for content so
you can fetch nodes using a url like '/node/1?_format=json'.

After this, you can configure the JSON output of entities using YAML files in the
root of your module using the pattern: <module_name>.entity_normalization.yml.

Specification:
```yaml
    <label>:                  # Unique label for your definition.
      extends: string         # [Optional] Inherit another configuration. Use the <label> of another configuration.
      type: string            # The entity type, for example 'node', 'user' or 'taxonomy_term'.
      bundle: string|array    # The entity bundle name(s), for example 'user' or '[article, 'page]'.
      format: string          # [Optional] A specific output format to be used for the normalizers.
      weight: int             # [Optional] Define the weight (default 0) of the configuration.
      fields:                 # The fields to output.
        <machine_name>:       # The machine name of the field.
          name: string        # [Optional] Name of the field in the output.
          required: bool      # [Optional] If FALSE, the field is not required and will not be added to the output if it doesn't exist. Default is TRUE.
          type: string        # [Optional] Field type. Currently only 'pseudo' is supported. Use this if you want to 
                                add specific data to the output outside normal fields. A 'normalizer' is required.
          normalizer: string  # [Optional] Name of a service which should be used as the normalizer for this field. Required if type=pseudo.
          group: string       # [Optional] Name of the group where to put this field into in the output.
      normalizers: array      # [Optional] Additional normalizer service names to be called for this entity.
```

EXAMPLE
-------
```yaml
      user.base:
        type: user
        bundle: user
        fields:
          uid:
            name: id
          name:

      article.base:
        type: node
        bundle: [article]
        fields:
          nid:
            name: id
          url:
            type: pseudo
            normalizer: entity_url.normalizer
          title:
          uid:
            name: author
```
JSON Output:
```json
      {
        "id": 1234,
        "url": "\/article\/1234/my-title.html",
        "title": "My Title",
        "author": {
          "id": 1,
          "name": "Administrator"
        }
      }
```
More examples can be found in the tests/Fixtures directory. 

TROUBLESHOORTING
---------------

 * If your normalization configuration isn't picked up, check the following:
 
   - Rebuild the cache: the module uses standard caching.
   - Check for errors in the logs after clearing the cache, it might be that
     one of your YML configuration files are invalid.
   - Are you targetting the correct entity bundle(s)?

MAINTAINERS
-----------
Current maintainers:

 * Remko Klein (remkoklein) - https://www.drupal.org/u/remkoklein
