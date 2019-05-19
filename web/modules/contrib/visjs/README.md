This module integrates the vis.js visualization library. It provides
a means for loading the following Vis compontents:

  - vis.min.js
  - vis.map -- JSON file containing labels, etc. used by vis.js
  - vis_graph3d.min;js -- Create interactive, animated 3d graphs. Surfaces,
    lines, dots and block styling out of the box.
  - vis_timeline_graph2d.min.js -- Draw graphs and bar charts on an interactive
    timeline and personalize it the way you want.
  - vis_network.min.js -- Display dynamic, automatically organised, customizable
    network views

INSTALLATION
============
1.  Install vis.js in a known library location such as /libraries,
    /sites/all/libraries or /sites/YOUR_SITE/libraries.
    a.  Download vis.zip from http://visjs.org/#download_install and put it in
        your chosen libraries directory
    b.  Unzip the file. The result should be something like
        libraries/vis/dist/vis.js. Notice that only the dist directory
        is used by the module so you can remove the rest of the files
        and directories.

2.  Enable the VisJS module (under Libraries at admin/modules).

3.  When you want to use vis in a module, add it as a dependency to a library
    in your module's libraries.yml file, e.g., this gets vis.min.js and
    vis_graph3d.js:

    my_library_using_vis:
      version: 1.0
      dependencies:
        - visjs/vis
        - visjs/vis.graph3d

See http://visjs.org/ for full documentation.
