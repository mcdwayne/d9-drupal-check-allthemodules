Smart Entity Reference Selection
--------------------------------

This module adds a selection plugin for entity reference autocomplete fields.
It supplies the following smart features:


* Dynamic limit

* Filter on starts with

* Filter on ends with

* Filter on does not contain


Configuration
-------------

Enable the module and edit an entity reference field. In the 'Reference method'
of the 'Reference type' fieldset, select the 'Smart Entity Reference Selection'
option.


Filters
-------

The user can apply the following filters in the autocomplete field input element:

* -
  results should not contain this word

* #
  the maximum number of results

* ^
  results should start with

* $
  results should end with

Filters can be combined, see the usage eamples.



Usage examples
--------------

AFI stands for Autocomplete Field Input:

* AFI:   sugar #20
  Means: a maximum of 20 results that contain 'sugar'

* AFI:   sugar -brown
  Means: results that contain 'sugar', but do not contain 'brown'

* AFI:   sugar ^brown
  Means: results that contain 'sugar', that start with 'brown'

* AFI:   cast $sugar
  Means: results that contain 'cast', that end with 'sugar'

* AFI:   sugar caster
  Means: results that contain 'sugar' and contain 'caster'

* AFI:   sugar caster -brown #5
  Means: a maximm of 5 results that contain 'sugar' and 'brown', but do not
         contain 'brown'



Known issues
------------

* This module uses a 'NOT LIKE' operator. This operator is not supported on all
  databases that Drupal supports. It has been tested on a MySQL database.

* This module uses the escapeLike() method, which is reported to not work on all
  databases that Drupal supports. It has been tested on a MySQL database.



Inspiration
-----------

This module was developed to help the editors of 24Kitchen (a Dutch television
specialty channel that airs both one-time and recurring (episodic) programs
about food and cooking) find ingredients from a large set containing numerous
variations of the same ingredient. The usage example in this readme uses 'sugar'
as an example. The website contains over 100 ingredients that have the Dutch word
'suiker' in their title. The default autocomplete function only returns the
first 10 matches, often not showing the desired ingredient in the first 10
results.