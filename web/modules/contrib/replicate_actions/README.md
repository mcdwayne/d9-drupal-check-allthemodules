# Replicate Actions


While Replicating Nodes using 
[https://www.drupal.org/project/replicate](Replicate API) and 
[https://www.drupal.org/project/replicate_ui](Replicate UI) modules, 
I found that the module will publish a node immediately (shows "view" mode) 
after replication. 
What I would like is when the replicated node opens in edit mode so that 
a content manager can make the necessary changes before publishing it.
Otherwise, it creates too many steps for them to go through.

For now, the module makes replicated nodes 
unpublished and makes redirect to edit mode.
But in the future, incoming versions, I am going to add new functionality 
to allow do the same for other entity types and some more actions.

This provides the extension for a simple but powerful 
[https://www.drupal.org/project/replicate_ui](Replicate UI).


## More information
- To issue any bug reports, feature or support requests, see the module issue
  queue at https://www.drupal.org/project/issues/2922694.

Author: Ruslan Piskarev <http://drupal.org/user/424444>.
