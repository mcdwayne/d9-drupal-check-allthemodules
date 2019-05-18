CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation


INTRODUCTION
------------

Current Maintainer: Carlos Carrascal <carlos.carrascal@gmail.com>

Related by Terms is a very simple module to be able to show related content.

The module provides a custom block that will show a list of related nodes. 
At the moment all content types will be displayed in the block.

The related content for the current node is calculated by checking taxonomy
terms coincidences. All terms associated with a node are checked, from all 
vocabularies.

The nodes with the most coincidences in taxonomy terms with the current one 
will be showing up first in the list.

There is also a configuration page available for the module, where you can set:

  * Number of elements to be displayed
  * Display type to use for rendering the nodes (i.e. Teaser, Full, etc.)

If you don't want to use the provided block, this module declares a Drupal
service relatedbyterms.manager that you can use in your own module to get a
list of related nodes, by doing something like this:

\Drupal::service('relatedbyterms.manager')->getRelatedNodes($nid);

(Please, remember that it's better to use dependency injection instead of
directly calling the Drupal::service function)


INSTALLATION
------------

Follow these steps:

* Download and install the module as usual.
* Go to Structure > Blocks and add the "Related by Terms: Shows related content
  by terms" block into the any of your theme regions.
* Go to Configuration > Content authoring  > Related by Terms configuration,
  and configure your block.
* Add some taxonomy terms to your nodes.
* Now you are done.


TRANSLATIONS
------------

This module supports translations of all the used strings.
