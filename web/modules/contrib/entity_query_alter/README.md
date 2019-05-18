# Entity query alter (via Event Dispatcher) for Drupal 8

Drupal 8 is [missing](https://www.drupal.org/project/drupal/issues/2611638)
the D7 equivalent of `hook_entity_query_alter()` in D7, which is fine...until
you need to alter the entity query. A use case would be for implementing access
logic for custom entities at the query level. There is an
[open issue](https://www.drupal.org/project/entity/issues/2909970) against
Entity module (and hopefully, later, core) to do just that, however in the
meantime it is painful to construct your own SQL queries based on Drupal's
field API, akin to what an entity query does. Once you're at
`hook_entity_TAG_alter()`, though, the entity query has been compiled into a
standard SQL select.

This simple module aims to provide an alter event on the entity query, until
the above API lands. Use at your own risk and do understand the impacts of
hooking into the entity query build process. From a technical perspective,
this module adds a `::alter()` method which is called prior to preparing or
compiling the entity query inside of `::execute()`, and copies in the standard
set of alter metadata and tags which would normally be used in identifying and
altering the SQL select statement in the above core hook.

&copy; 2018 Brad Jones LLC. GPL-2 license.
