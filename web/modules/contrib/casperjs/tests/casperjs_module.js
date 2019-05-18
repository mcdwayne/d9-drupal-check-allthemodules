/**
 * @file
 * Tests casperjs module.
 */

casper.test.begin('Test casper.urlIsFQDN function', 6, function suite(test) {
  casper.start();

  casper.then(function() {
    var FQDNpaths = [
      'https://example.com/',
      'http://www.example.com',
      '//example.com'
    ];

    casper.each(FQDNpaths, function(self, path) {
      test.assert(casper.urlIsFQDN(path), "FQDN path: " + path);
    });
  });

  casper.then(function() {
    var notFQDNpaths = [
      '/',
      'user/login',
      'user/login?destination=http://www.example.com'
    ];

    casper.each(notFQDNpaths, function(self, link) {
      test.assertNot(casper.urlIsFQDN(link), "Non-FQDN path: " + link);
    });
  });

  casper.run(function() {
    test.done();
  });
});

casper.test.begin('Test open.location filter', 6, function suite(test) {
  casper.start();

  // Open FQDN page.
  casper.thenOpen('http://www.google.com', function() {
    // Check that we get a 200 response code.
    test.assertHttpStatus(200, 'FQDN page was loaded successfully.');
    // Check the presence of the main items in the page.
    test.assertExists('form[action="/search"]', 'Search form is present.');
  });

  // Open not FQDN page.
  casper.thenOpen('user/login', function() {
    // Check that we get a 200 response code.
    test.assertHttpStatus(200, 'Non-FQDN page was loaded successfully.');
    // Check the presence of the main items in the page.
    test.assertExists('form#user-login-form', 'Login form is present.');
  });

  // Open non-FQDN page: FQDN with query parameters (avoiding false positives).
  casper.thenOpen('user/login?destination=http://www.example.com', function() {
    // Check that we get a 200 response code.
    test.assertHttpStatus(200, 'Non-FQDN page was loaded successfully.');
    casper.capture('peter.png');
    // Check the presence of the main items in the page.
    test.assertExists('form#user-login-form', 'Login form is present.');
  });

  casper.run(function() {
    test.done();
  });
});
