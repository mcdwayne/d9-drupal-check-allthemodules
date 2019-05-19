--------------------------------------------------------------------------------
  universal_queue module Readme
  http://drupal.org/project/universal_queue
--------------------------------------------------------------------------------

Contents:
=========
1. ABOUT
2. INSTALLATION
3. USAGE EXAMPLES
4. CREDITS

1. ABOUT
========

This module provides developers a possibility to execute some custom
functionality by Drupal queue. This module can be helpful if you have some
pretty heavy actions, but don't want to create a special queue for every such
action.

2. INSTALLATION
===============

Install as usual, see https://www.drupal.org/node/1897420 for further information.

3. USAGE EXAMPLES
===============

1. Postponed node deletion

    universal_queue_add_item('entity_delete_multiple', ['node', [123, 321]]);

2. Postponed file downloading

    universal_queue_add_item('system_retrieve_file', [
      'url' => 'https://wikipedia.org/static/images/project-logos/enwiki.png'
      'destination' => 'public://image',
    ]);


4. CREDITS
==========

Project page: http://drupal.org/project/universal_queue

- Drupal 8 -

Authors:
* Krupin Vladimir - https://www.drupal.org/u/vladimirkrupin
