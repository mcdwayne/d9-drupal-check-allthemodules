INTRODUCTION
------------

This Drupal module provides context for content items 
by displaying a block of other similar content. 
Similarity is based on the taxonomy terms assigned to content. 
Blocks are available based on similarity within each of the defined vocabularies 
for a site as well as a block for similarity within all vocabularies.

How it work:-

- Create a free tagging vocabulary assigned to the content types.
- Create a view with necessary fields,sort and contextual filter.
(Remember you have to set the nid contextual argument 
provided by similar term and not Content nid).
- Enable the block to the specific content type.
- Start adding content with free tags.


Block will show up displaying other content 
in descending order of common tags(terms).
Field showing in the view can be displayed 
by either number of how many tags matched and also as percentage.
