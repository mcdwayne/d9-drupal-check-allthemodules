module.exports = {

  before: function(browser) {
    browser
      .drupalInstall({ setupFile: __dirname + '/TestSetup.php' });
  },
  after: function(browser) {
    browser
      .drupalUninstall();
  },
  'Visit a test page and create some test page': (browser) => {
    browser
      .drupalLoginAsAdmin(() => {
        browser
          .drupalRelativeURL('/user')
          .drupalRelativeURL('/node/add/article')
          .setValue("input[name='title[0][value]']", 'test')
          .click('input[name=op]');
        
       browser
          .waitForElementPresent('ul.tabs li:nth-of-type(2) [data-drupal-link-system-path="node/1/dpl/live/preview"]', 3000);
        
        browser.expect.element('ul.tabs li:nth-of-type(2) [data-drupal-link-system-path="node/1/dpl/live/preview"]').text.to.equal('Visit live');
        browser.expect.element('ul.tabs li:nth-of-type(3) [data-drupal-link-system-path="node/1/dpl/staging/preview"]').text.to.equal('Visit staging');

        browser
          .click('ul.tabs li:nth-of-type(2) [data-drupal-link-system-path="node/1/dpl/live/preview"]');

        // Test the URL ...
        browser.verify.urlContains('node/1');
        browser.verify.urlContains('http%3A//live.example/1');

        // Ensure that the three different sizes are available.
        browser.expect.element('ul.bar--sizing li:nth-of-type(1) button').to.have.attribute('data-cpl-size').equals('240:500');
        browser.expect.element('ul.bar--sizing li:nth-of-type(2) button').to.have.attribute('data-cpl-size').equals('500:800');
        browser.expect.element('ul.bar--sizing li:nth-of-type(3) button').to.have.attribute('data-cpl-size').equals('800:1200');
        browser.expect.element('ul.bar--sizing li:nth-of-type(4) button').to.have.attribute('data-cpl-size').equals('1200:-1');
        browser.expect.element('ul.bar--sizing li:nth-of-type(5) button').to.have.attribute('data-cpl-size').equals('-1:-1');

        // Ensure the iframe is 1000px wide by default.
        browser.getElementSize('iframe', (result) => {
          browser.assert.equal(typeof result, "object");
          browser.assert.equal(result.value.width >= 240 && result.value.width <= 500, true);
        });

        browser.click('ul.bar--sizing li:nth-of-type(1) button');
        browser.getElementSize('iframe', (result) => {
          browser.assert.equal(typeof result, "object");
          browser.assert.equal(result.value.width >= 240 && result.value.width <= 500, true);
        });

        browser.click('ul.bar--sizing li:nth-of-type(2) button');
        browser.getElementSize('iframe', (result) => {
          browser.assert.equal(typeof result, "object");
          browser.assert.equal(result.value.width >= 500 && result.value.width <= 800, true);
        });
      })
      .end();
  },

};
