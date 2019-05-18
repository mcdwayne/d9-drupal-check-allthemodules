--------------------------------------------------------------------------------
  entity_keyvalue module Readme
  http://drupal.org/project/entity_keyvalue
--------------------------------------------------------------------------------

Contents:
=========
1. ABOUT
2. INSTALLATION
3. USAGE EXAMPLES
4. CREDITS

1. ABOUT
========

This module provides possibility to save/load additional data for entities in (Drupal-based) key-value storage like SQL, Redis, MongoDB, etc. It can be useful for some highloaded properties like counters.
Also this module will take care of deleting data on "base" entity deletion (optional) and loading data on entity load (optional).

2. INSTALLATION
===============

Install as usual, see https://www.drupal.org/node/1897420 for further information.

3. USAGE EXAMPLES
===============

3.1 Defining some special counter for node by special hook:

function mymodule_entity_keyvalue_info() {
  return [
    'node' => [
      'keys' => [
        'special_counter' => [
          'default_value' => 0,
          // Possible values: 'any', 'int', 'float', 'string', 'array', 'object'.
          'type' => 'int',
        ],
        'key2' => [
          // Default value can be callable.
          'default_value' => function (NodeInterface $node) {
            return $node->getTitle();
          },
          'type' => 'string',
        ],
      ],
    ],
  ];
}

3.2 Updating value:

$node = Node::load(156);
$store = \Drupal::service('entity_keyvalue_store_provider')->getEntityStore('node');

// Actually, you can avoid this by setting 'autoload' => TRUE in hook_entity_keyvalue_info.
$values = $store->loadValues($node);
$values['special_counter']++;

$store->setValues($node, $values);


4. CREDITS
==========

Project page: http://drupal.org/project/entity_keyvalue

- Drupal 8 -

Authors:
* Evgeny Yudkin - https://www.drupal.org/u/evgeny_yudkin
