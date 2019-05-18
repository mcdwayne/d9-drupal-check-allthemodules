Neutral paths
=============

Purpose
-------

A module for multi-language web sites, allowing users to access content in
languages other than the current one by using path aliases.

How it works
------------

By setting newly created and updated path aliases as language neutral.
Previously created paths can be updated using `pathauto` bulk update
functionality.

Use case
--------

Different users may have different language preferences.
For example, not everyone likes translated administration pages.

Usually when a user creates a node or a taxonomy term, a path alias is
automatically generated. The language of this alias matches that of the user.
This creates a major inconvenience, namely, path aliases don't work for users
with different language preferences. For example, they can only accept the nodes
using a regular `/node/[nid]` path.

Installation & configuration
----------------------------

Install the module as usual. 

After the installation go to the configuration page
`admin/config/search/path/language_settings`.

All the alias types supported by `pathauto` can be configured to be language
neutral.
