# Important D7 vs D8 difference
In the D7 version the javascript in the module was used to download another javascript file (pub1.js). This file is not compatible with the current version of jQuery (3.x).

We were not able to find a resource that would work with jQuery 3.x. To get around this we downloaded and now serve the javascript in the bapw.js file with adjustments for the jQuery 3.x.

#Setup
Configuration can now be found in:
Configuration->Search and Metadata->Ghostery
