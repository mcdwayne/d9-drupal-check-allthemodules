INTRODUCTION
------------

Better visualizations of drupal.org module usage trends combined with data from 
git repositories. This module is hosted live at drupalmoduletrends.com but the
module can be installed to other sites too. Only Drupal 8 module usages are
used for now.

The different visualizations in this module try to highlight the modules that 
are gaining popularity within Drupal sites. Drupal.org displays absolute 
installation counts of modules and these counts are rising even when relative 
popularity of the module is not. DMT module instead focuses on relative stats: 
on how many % of sites is this module used on.

SUB-MODULES
-----------

 * DMT Default Content:
    Module provides some default content entities that can be used during
    development of this module. The final version should have an integration to 
    fetch this data from drupal.org.
 * DMT React Charts
    React Charts is the first approach to building charts to display the module
    usage trends. The primary motivation to do this with React is to get to
    know React a bit better (as Drupal core is considering to use React for
    admin UIs).

OTHER PROJECTS
--------------

List of other websites and tools that also provide statistics of Drupal modules.

 * https://drustats.com

MAINTAINERS
-----------

 * Mikko Rantanen (mikran) - https://www.drupal.org/u/mikran
