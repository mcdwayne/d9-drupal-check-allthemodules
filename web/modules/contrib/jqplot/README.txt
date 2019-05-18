CONTENTS OF THIS FILE
---------------------
 * Introduction
 * About Project
 * Requirements
 * Installation
 * Configuration

Introduction
-----------
jqPlot is a jQuery plugin to generate pure client-side javascript charts in your web pages.

The jqPlot home page is at http://www.jqplot.com/.

The project page and downloads are at http://www.bitbucket.org/cleonello/jqplot/.

These plots are shown as static images. Few examples to demonstrate jqPlot static chart images on the examples pages here: /jqplot_example(Need to Enable jqplot_example module)

About Project
-------------
jqplot module allow you to integrate
jquery.jqplot library, jqPlot css file and optionally the excanvas script for IE support in your web page.


Requirements
------------
1. jquery.jqplot library :- You will need to
download jquery.jqplot latest version from https://bitbucket.org/cleonello/jqplot/downloads/ and extract the jquery.jqplot files to the "<root>/libraries/" directory
and rename to jquery.jqplot(e.g:<root>/libraries/jquery.jqplot/).
2. jquery.jqplot module requires jQuery 1.7 or higher jQuery version.

Installation / Configuration
----------------------------
1. Extract the module files to the "<root>/modules" directory. It should now contain a "jqplot" folder or download it using drush.
2. Enable the module in the "Administration panel > Modules > charts" section.
3. Create a custom module to include js files and to add plot.
4. Add a plot container
   Add a container (target) to your web page where you want your plot to show up.  Be sure to give your target a width and a height:
   '#markup' => '<div id="chart-pie" class="jqplot-target"></div>',
5. Include the js Files
   ['#attached']['library'][] = 'jqplot/jqplot.pieRenderer.min';
   ['#attached']['library'][] = 'jqplot_example/jqplot.example';
6. Create a plot
   Then, create the actual plot by calling the $.jqplot plugin with the id of your target and some data from .js files:
   $.jqplot ('chart-line', [[3,7,9,1,4,6,8,2,5]]);
7. Libraries Directory Configuration
   You can Delete examples folder from jquery.jqplot library(e.g:<root>/libraries/jquery.jqplot/examples) for security reasons. You can also delete docs folder(e.g:<root>/libraries/jquery.jqplot/docs), if you want to save some bandwidth and space.
