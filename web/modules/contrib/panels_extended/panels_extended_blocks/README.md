Panels Extended_BLOCKS
======================

INTRODUCTION
------------
A lot of time I need to supply lists of nodes to the users, for example
the last 10 published articles, most active users, etc. There are multiple
ways to solve this problem, for example using views. However I wanted to
add them to the panels, make sure they perform very nicely and allow
them to output to JSON.

This module adds:
* a base implementation (`NodeListBlockBase`) of a block doing just that
* some classes implementing `BlockConfigBase`
* extra interface to use in combination with NodeListBlockBase

### NodeListBlockBase
* Loops through it's configurations to:
  - Alter the query
  - Alter the query range
  - Alter the result from the database
  - Alter the data returned to the caller (for rendering to HTML/JSON)
* Doesn't render when there is no data.
* Basic implementation for rendering to HTML (I'm mainly targetting JSON, but
  you can easily make your own default implementation).

Note: I'm using direct queries, since EntityQuery that is **MUCH** slower,
especially when quering more fields and adding sorting.

Interfaces
----------
* **AlterQueryInterface**: Alter the query (e.g. add extra query conditions).
* **AlterQueryRangeInterface**: Alter the query range (offset and limit).
* **AlterQueryResultInterface**: Alter the result from the database.
* **AlterBlockDataInterface**: Alter the data send back to the panel.

Block configs
-------------
Some common block configurations are implemented:
* **NodeListBaseConfig**: Added to each block. Filters out non published nodes
  and sorts by created desc, nid desc.

* **NodeTypeFilter**: Add a filter for one or more specific node types. Also
  can allow user selection in the block configuration form.

* **NrOfItemsLimiter**: Adds an option to the block configuration form to let
  a user decide the maximum number of items for this block instance.

* **PreventNodeDuplication**: Prevent the same nodes from appearing more than
  once on the same page.

* **TermFilter**: Adds an option to the block configuration form to let a
  user decide if the block should be filtered on one or more terms.

* **FixedNodesConfig**: A more or less special configuration to allow users
  to fixate specific nodes on specific positions. Example: you want to supply
  the 10 most recent articles, but on the 2nd position you want to show an
  advertorial which isn't in the default list. You can override that position
  and only 9 will be fetched from the database and advertorial is inserted on
  the 2nd position.

Example
-------
Example: [ContentListBlock](src/Plugin/Block/ContentListBlock.php).

The default (maximum) number of items returned is set to 10:
```php
/**
 * {@inheritdoc}
 */
public function getNumberOfItems() {
  return 10;
}
```

And we're adding some extra configurations:
```php
/**
 * {@inheritdoc}
 */
protected function getBlockConfigsToAdd() {
  return [
    // Allows filtering on terms from vocabulary 'tags'.
    new TermFilter($this, ['tags' => 'Tags']),
    
    // Allows fixating nodes.
    new FixedNodesConfig($this),
    
    // Allows changing the number of items to 5, 10, 15 or 20.
    new NrOfItemsLimiter($this, [5, 10, 15, 20]),
    
    // Allows selecting which node types are returned.
    new NodeTypeFilter($this),
    
    // Prevent node duplication on this page so if we place this block
    // multiple times, we prevent showing the same node multiple times.
    new PreventNodeDuplication($this),
  ];
}
```
