======================================================================
About
======================================================================

Drupal 8's version of Views is included in core, and very powerful.

However, its handling of taxonomy terms is missing some important
features, and can be confusing to configure properly.

The default contextual filter (aka "argument") for D8 taxonomy terms
is called "Has taxonomy term ID", which builds the most efficient
query to find content tagged with a given term.

In Drupal 7 and prior, there was an option on the "argument validator"
for taxonomy terms called "Term name converted to Term ID" that would
convert a term name into its corresponding term ID, exactly for use
with this contextual filter. That allowed you to use the most direct,
efficient query, but allow for human-readable URLs based on term names
instead of numeric IDs.

This module restores that functionality by providing a "Term name into
ID" option when configuring the validation for a Views contextual
filter, primarily for use with the "Has taxonomy term ID" contextual
filter.

======================================================================
Usage
======================================================================

1. Install as a normal Drupal 8 module using your favorite method.
2. Enable the module
3. Build a view using the 'Has taxonomy term ID' contextual filter.
4. Configure the contextual filter:
4a. Check the 'Specify validation criteria' checkbox.
4b. Under the 'Validator' selector, choose 'Taxonomy term name as ID'
4c. If your site has many vocabularies and the term name might not be
    unique across them, consider limiting which vocabularies to search
    by configuring the 'Vocabulary' checkboxes.
4d. Review other relevant validation settings (e.g. if you want to
    check access control, convert '-' to ' ' in the argument).
5. Save your view.
6. Rejoice.

NOTE: The conversion of a term name into its numeric ID assumes
there's only one term of a given name. If your site has multiple
vocabularies that have terms with the same name, you'll almost
certainly want to limit the validator to only search in a single
vocabulary.

If you have a vocabulary with duplicate names, you probably want to
either force the arguments in the URLs to be numeric IDs and not use
this validator at all, or consider using the 'Has taxonomy term ID
with depth' contextual filter and forcing the URLs to include a depth.

======================================================================
Related module
======================================================================

https://www.drupal.org/project/views_taxonomy_term_name_depth

However, that module takes a different approach, adds an entirely
separate contextual filter, and leads to more complicated
queries. Hence, the existence of views_taxonomy_term_name_into_id.
