This module will provide template suggestions for everything that Drupal core
or another supported contributed module doesn't.

So far:

* Block templates per region
* HTML templates per node type
* Page templates per node type
* User templates per highest role|uid (and view mode)
* Field templates per view mode (and bundle)
* Container templates per form or not (has-parent or no-parent), type, and view.
* Page templates per entity type (a fix for core).

[Suggestions are welcome in the issue queue][1]

## See also

Other modules already cover some template suggestion use cases quite well.
In those cases, use the other modules:

* For block templates per block type, use [Block Type Templates][2]
* For node templates selected by content editors on a per-node basis,
  see [Template Whisperer][3]

[1]:https://www.drupal.org/project/issues/twigsuggest
[2]:https://www.drupal.org/project/block_type_templates
[3]:https://www.drupal.org/project/template_whisperer
