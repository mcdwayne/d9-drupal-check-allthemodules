How to use this module

1. Install the module
2. Add an extra region to your theme on the YOURTHEME.info.yml file 
  -You might have to uninstall and reinstall your theme for the new region to show up
3. Add the layout config block (the larger containing block) 
4. Do a config export so that you now have an instance of this block 
5. Add all the blocks that you would like to use into this new region (and make note of the machine name of each block)
6. Go to the yml of the layout config block and under settings add the following regions: search_bar, facets, search_results: and add the machine name of each block that you would like to appear in each section.
7. Add a new reference field to your content type, reference blocks, change the display to ‘rendered entity’ 
