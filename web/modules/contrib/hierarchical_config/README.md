# Hierarchical Config

This is a helper module to maintain hierarchical configuration. 

## Usage

After the installation you can create a new config bundle with all the needed fields at admin/structure/hierarchical_configuration_type.
Now add an hierarchical config entity reference field to your site vocabulary and your article content type for example.

I would suggest to use the Inline Entity Form module for the form displays.

The module now provides a token for every field. Depends on where you are on your site, it returns different values. 
If you are on a node without a referenced configuration entity, it will return the values from a referenced term.
If the term has no configuration entity, then from it's parent. And so on.
