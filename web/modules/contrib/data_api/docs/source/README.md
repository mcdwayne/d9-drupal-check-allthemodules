# Drupal Module: Data Common Api
**Author:** Aaron Klump  <sourcecode@intheloftstudios.com>

## Summary
**Provide a simple and consistent means to interact programatically with entity data, form submissions and other Drupal data across projects and major versions.**

You may also visit the [project page](https://www.drupal.org/project/data_api) on Drupal.org.

## Requirements
* Php 5.3 or higher

## Installation
1. Install as usual, see [http://drupal.org/node/70151](http://drupal.org/node/70151) for further information.
1. If you are not using composer to manage your site, you must add this module's composer.json file to the root merge-plugin see [this link](https://www.drupal.org/node/2405811) for more info.

## Configuration
1. No configuration is necessary; but the module won't do anything unless you utilitze it's functions.

## Suggested Use
More code examples are available if you enable the Advanced Help module.

### Read Entities
    // Create a getter for node entities
    $n = data_api('node');
    
    // Load a node entity
    $node = node_load(4503);
    
    // Use the getter to pull the first name or default.
    // Do not include the language key; language is determined automatically.
    $vars['name'] = $n->get($node, 'field_first_name.0.value', '{first name}');
    
    // You can resuse the node getter with a new node.  The node getter can be reused as long as the entity type doesn't change.
    $node = node_load(345);
    $vars['name'] = $n->get($node, 'field_first_name.0.value', '{first name}');
    
    // But to pull data from a different entity type, you can either create a new getter for user entities...
    $u = data_api('user');
    
    //... or just reassign the entity type of the original getter to 'user'.
    $n->setEntityType('user');
    $vars['mail'] = $n->get($GLOBALS['user'], 'mail', '{missing email}');

### Set entities

    // Load a comment entity
    $comment = comment_load(1);

    // Change the body value in the object only.
    data_api('comment')->set($comment, 'comment_body.0.value', 'lorem', []);

    // Save the comment to the database
    comment_save($comment);

### Arrays and objects
This will also work on native arrays and objects, and offers a means of supplying defaults with minium code.  For more information go [here](https://github.com/aklump/data).

    // Create a global getter that uses no entity type.
    $g = data_api();
    
    // Using a standard array...
    $array = array('do' => array('re', 'mi'));
    
    // Access it's elements.
    print $g->get($array, 'do.0', 'none'); // === 're'
    print $g->get($array, 'do.1', 'none'); // === 'mi'
    print $g->get($array, 'do.2', 'none'); // === 'none'; the default
    
    // Set a deep object
    $object = new \stdClass;
    $g->set($object, 'do.re.mi.fa.so', 'laaaa');
    
    ... $object->do->re->mi->fa->so === 'laaaa'
    
### And form submissions...

    $value = data_api()->get($form_state, 'values.summary', 'none');
    
### Use callback to load an entity reference
The fourth argument is a callable that receives the value and the default, so you can post process the value, e.g.,

    $related_node = data_api('node')->get($node, 'field_related_node.0.nid', null, function ($nid, $defaultValue) {
        return $nid ? node_load($nid) : $defaultValue;
    });
    
## Design Decisions/Rationale
* To bring consistency across Drupal versions for accessing data on entities.
* To simplify the code used to programatically interact with entities.
* To avoid Exceptions and issets() when pulling data.

## Roadmap/Drupal 8
I'm planning a Drupal 8 version which will follow the same patterns so you do not have to relearn a new api.

## Contact
* **In the Loft Studios**
* Aaron Klump - Developer
* PO Box 29294 Bellingham, WA 98228-1294
* _skype_: intheloftstudios
* _d.o_: aklump
* <http://www.InTheLoftStudios.com>
