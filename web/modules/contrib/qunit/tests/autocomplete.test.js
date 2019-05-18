// $Id$

/**
 * @file
 * Tests for collapsible fieldsets.
 */

(function($) {

Drupal.tests.Autocomplete = function() {
};

Drupal.tests.Autocomplete.prototype = new Drupal.WebTest;

Drupal.tests.Autocomplete.prototype.getInfo = function() {
  return {
    'name': 'User autocomplete',
    'description': 'Tests to make sure autocomplete works properly.',
    'group': 'System',
    'serverClass': 'AutocompleteTestCase'
  };
};

Drupal.tests.Autocomplete.prototype.test = function() {
  var $ = this.child$;
  equals($('#edit-name').length, 1);
  /* @TODO. This test right now is just a Proof of Concept for the iframe locking mechanism. */
};

})(jQuery);
