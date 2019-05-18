#Rocketship Page

Contains the Page content type, used for your static content from an "About 
us" page to a landingspage for a campaign.

This is the core of the Skeleton in many ways. It is very basic, but in 
combination with our paragraphs can be used for almost anything. In fact, 
every feature's overview pages are basic pages with Overview paragraphs. The 
homepage, 404 and 403 are also basic pages.

This way the client has full control over metatags, page titles and the like.
 Every page in the site is  editable by the client this way. We strongly urge
  you to keep up this pattern, and if you need a custom page make a custom 
  block and add it to a basic page using the Overview paragraph using 
  Brent's contrib module, overview_field. Make sure to check out that README 
  as well.

It is built using a Header Paragraph Field and a normal Paragraph Field.

##### Header paragraph
* Currently we only have two paragraphs for this field; simple and focus.
And only simple is really completely ready. The simple header allows the 
client to set a different title for the detail page, or give it some emphasis
 or italics. It also gives the client the option to show or hide the 
 breadcrumb, as well as select a background image and/or background color for
  the header.

##### Paragraph field
* This is the bog standard paragraph field present on most content types. By 
default all paragraph types are selectable, but depending on what the client 
purchased some paragraphs should be denied access using the permissions 
system. Don't uncheck them from the fields, so that the developers can still 
add paragraphs the client doesn't have access to (for bespoke landings pages 
for example).

Metatags is set up, RDF is set up. Path alias pattern uses the custom token 
from rocketship_core so nested pages' aliases follow the alias from the page 
above. 
