module.exports = {
  'Autodrop test drop button Javascript' : function (browser) {
    // Get the test page and check the title.
    browser
      .drupalRelativeURL('/autodrop-test-page')
      .waitForElementVisible('body', 1000)
      .assert.title('Autodrop test page | Drupal');
    // Make sure the first action is visible but not the second.
    browser.expect.element('#autodrop-test-dropbutton').text.to.contain('Action no 1');
    browser.expect.element('#autodrop-test-dropbutton').text.to.not.contain('Action no 2');
    // Perform mouse move.
    browser.moveToElement('#autodrop-test-dropbutton', 5, 5, function() {
      // Check that both buttons are visible.
      browser.expect.element('#autodrop-test-dropbutton').text.to.contain('Action no 1');
      browser.expect.element('#autodrop-test-dropbutton').text.to.contain('Action no 2');
    });
    // Leave the drop button.
    browser.moveToElement('body', 0, 0, function() {
      browser.waitForElementNotVisible('#autodrop-test-dropbutton .action2', 1000);
      // Check that the drop button has collapsed.
      browser.expect.element('#autodrop-test-dropbutton').text.to.contain('Action no 1');
      browser.expect.element('#autodrop-test-dropbutton').text.to.not.contain('Action no 2');
    });
    browser.drupalLogAndEnd({ onlyOnError: false });
  },
  '@tags': ['autodrop'],
  before(browser) {
    browser
      .drupalInstall({ setupFile: __dirname + '/NightwatchSetup.php' });
  },
  after(browser) {
    browser
      .drupalUninstall();
  }
};
