Shownid
--------------------------
--------------------------

Shownid is a very simple module. All it does is display the nid or tid of a
node or taxonomy term page in its own block.

The need for this functionality arose when several of our editors complained
they found it cumbersome to reference node id:s and taxonomy term id:s (nids
and tids) because we are using autoalias patterns and the nid/tid isn't
visible in the page url.

Previously they had to hover over the "edit-button" and memorize the nid/tid or
even open up the edit view and then copy the nid/tid from the url.

With this module, it's easily visible wherever you configure the block to
appear.

Instructions
--------------------------
* Enable the module
* Activate the block where you want it visible.
* Alternatively use Contexts, which are much more configurable.
* Verify the user you are logged on as has the appropriate permissions for
accessing the block (use shownid).
* Navigate to an article or taxonomy term page and verify you can see the
block.
* Style the block using css class "div.shownid_infobox" and "div.shownid span"
for the actual nid/tid number.
