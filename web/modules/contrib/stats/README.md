# Stats

The module provides a way to configure data processing for different occasions.

## Concepts

* A **Stat processor** holds the configuration for processing a data set.
* A stat processor is bound to a trigger entity. This entity might provide data
  to the process, like any of plugins described afterwards.
* Plugin **StatSource**: The source data that shall be processed is loaded via a
  configurable plugin. Its result is stored in rows to be processed in the next
  step.
* Plugin **StatStep**: Multiple step plugins will be executed on the given row
  collection. Each step plugin has access to the whole row collection and
  therefore can change the size and content of the whole collection or compare
  different rows. The plugin will most likely add properties to the destination
  part of a row.
* Plugin **StatDestination**: this plugin type manages the final storage of the 
  processed data rows.
  
## Modules

* **stats**: Basic API, plugin definitions and processing capability
* **stats_field**: Provides a way of binding stats execution by saving entities.

## Contribution

Please post any feature proposals or problems to the drupal.org issue queue on
https://www.drupal.org/project/issues/stats
