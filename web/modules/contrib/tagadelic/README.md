# Tagadelic #
The D8 version provides a block and page of the most used taxonomy terms. There is an API that works in a similar way to the D7 version (https://github.com/muka/tagadelic). By extending the abstract class TagadelicCloudBase and overriding the createTags function one can create a weighted tag cloud of whatever one likes. See the class TagadelicTaxonomyCloud for an example. Any suggestions or patches are welcome here on Drupal.org.

# Usage #
Go to admin/structure/tagadelic and select the vocabularies you want to include in the tag cloud.

Once you have done this, go to admin/structure/block, click "Place block" on the region you want to place the block and a new block called "Tagadelic tag cloud" should appear. Select this block and then the number of tags you want to display.

# Contact #
More on http://github.com/systemick3/tagadelic

Made by systemick; Mike Garthwaite
If you need custom work for this module, please contact me at <michael at
systemick dot co dot uk> or at http://www.systemick.co.uk.
