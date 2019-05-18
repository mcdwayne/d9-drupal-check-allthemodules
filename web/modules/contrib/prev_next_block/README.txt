Prev Next Block

This module creates custom blocks intended to be placed on node pages which
have menu links in any sort of heirarchy. The block generates Previous and Next 
links which navigate this menu to any depth. 

These links are generated once per node/page and cached. Updating the menu in
any way clears this cache.

Installation:
Enable the module on /admin/modules or with drush
From the block layout page /admin/structure/block add a Previous Next Block to
the desired page region. Edit the block's configuration to select with menu the
block will traverse.

Multiple blocks can be placed, each associated with a different menu.