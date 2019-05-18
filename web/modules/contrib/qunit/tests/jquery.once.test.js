(function($) {

/**
 * Tests the jQuery Once plugin.
 */
Drupal.tests.Once = function() {
};
Drupal.tests.Once.prototype = new Drupal.UnitTest;

Drupal.tests.Once.prototype.getInfo = function() {
  return {
    name: Drupal.t('jQuery Once'),
    description: Drupal.t('Tests for the jQuery Once plugin.'),
    group: Drupal.t('System')
  };
};

Drupal.tests.Once.prototype.test = function() {
  expect(4);

  var html = '<span>Hello</span>';
  var jqueryhtml = $(html);

  // Test One
  jqueryhtml.once('testone', function() {
    ok(true, Drupal.t('Once function is executed fine.'));
  });
  jqueryhtml.once('testone', function() {
    ok(false, Drupal.t('Once function is executed twice.'));
  });

  // Test Two
  jqueryhtml.once('testtwo', function() {
    ok(true, Drupal.t('Once function is executed fine one different tests.'));
  });

  // Test Three
  jqueryhtml.once('newclassfortestthree').addClass('testthreecomplete');
  ok(jqueryhtml.hasClass('testthreecomplete'), Drupal.t('Once each function is called.'));

  // Test Four
  jqueryhtml.once('newclassfortestthree').addClass('failure');
  equal(jqueryhtml.hasClass('failure'), false, Drupal.t('Once each function is called multiple times rather then once.'));
};

})(jQuery);
