VisualN Style
Config entity that attaches drawer plugins to fields and views.

There are four types of components: Drawer, Mapper, Adapter and Builder. Drawer can be considered the central place where visualization drawing occurs.
Each component generally consists of two files - a drupal plugin of a certain type and a js file. If needed, css files and other media may be included.
The logic is as follows: first, plugins prepare/modify html markup and attach js settings and scripts, then js scripts get and prepare data and draw visualization drawings according to those settings.

Brief description of the plugin types:

Drawer
Plugin to attach js script file that actually draws visualization required.

Mapper
Plugin to make mapping amongst input data keys (structure) and the one required by Drawer plugin. Mapper can replace keys in d3.js object provided by Adapter or can just provide mappings map to Drawer so that could work with it itself.

Adapter
Plugin function is to get data provided in a format and source and to convert it to a standard d3.js object to work with.
Some possible input formats include:
- xml
- json
- csv file
- views html output

Builder
Plugin manages all the things, prepares settings and calls Drawer, Mapper and Adapter plugins where needed. Generally the Default builder plugin should be enough for the most cases but can be overridden if needed.

You don't really need Mapper and Adapter for your custom visualizations if you put all the processing into a Drawer. Those are supposed for building flexible and reusable solutions. Also you can always reuse existing Mappers and Adapters.

Drawer decides which builder to use, but Builder itself does all the main job selecting plugins, connecting plugins and passing data between those etc.

