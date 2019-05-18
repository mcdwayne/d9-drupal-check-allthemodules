Description
-----------

Overrides Edit's metadata to be cached in sessionStorage. Only works for sites
that have simple permissions.

By "simple permissions", we mean that as long as a user still has the same set
of permissions, the same action will always be allowed. I.e. only the
permissions determine whether in-place editing of a specific field is allowed,
not context like time, location, IP address, workflow state, language and so on.

Ideally, we'd have this kind of metadata in Drupal core, but we currently don't
have this yet, hence we cannot be smart about caching this yet.


Installation
------------

Install like any other module, but for now you need to apply these Drupal 8 core
patches:
  - http://drupal.org/files/js_caching_metadata-2005644-1-drupalSettings.patch
  - http://drupal.org/files/edit_metadata_json_attachments_ajax-1980744-5.patch


Known limitations
-----------------

The per-field metadata is cached in sessionStorage. For fields using the
in-place editor provided by editor.module, that includes custom metadata for the
current text format. If the text format is changed (which can only happen on
entity forms on the back-end), then the metadata cached on the client-side will
be incorrect.
This could be worked around by either having additional granular metadata about
the entity, or by simply not caching any field metadata that has a 'custom' key.
