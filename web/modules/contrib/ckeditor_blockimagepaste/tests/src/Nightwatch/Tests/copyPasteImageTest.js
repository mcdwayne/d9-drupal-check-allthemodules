module.exports = {
  '@tags': ['ckeditor_blockimagepaste'],
  before: function(browser) {
    browser.drupalInstall({
      setupFile: 'modules/contrib/ckeditor_blockimagepaste/tests/TestSiteInstallTestScript.php',
    });
  },
  after: function(browser) {
    browser
      .drupalUninstall();
  },
  'CKEditor copy paste image test': browser => {
    // Create a user and role and login.
    browser
      .createUserRole({
        name: 'admin1234',
        password: 'admin1234',
        role: 'ckeditor',
        permissions: [
          'administer nodes',
          'administer content types',
          'administer filters',
        ],
      })
    .drupalLogin({ name: 'admin1234', password: 'admin1234' });

    // Add a content type 'article'.
    browser.drupalRelativeURL('/admin/structure/types/add').setValue('#edit-name', 'article');
    browser.expect.element('.form-item-name .machine-name-value').to.be.visible.before(2000);
    browser.click('#edit-submit');

    // Add a text format.
    browser.drupalRelativeURL('/admin/config/content/formats/add').setValue('#edit-name', 'Full html ckeditor');
    browser.expect.element('.form-item-name .machine-name-value').to.be.visible.before(2000);
    browser.setValue('#edit-editor-editor', 'ckeditor')
    browser.click('input[id="edit-roles-ckeditor"]')
    browser.expect.element('#ckeditor-button-configuration').to.be.visible.before(5000);
    browser.click('#edit-actions-submit');

    // Goto to the image test page and copy the image into memory.
    browser.drupalRelativeURL('/image-test-page');
    browser.keys([browser.Keys.CONTROL, 'a', browser.Keys.NULL]);
    browser.keys([browser.Keys.CONTROL, 'c', browser.Keys.NULL]);

    // Create an article and attempt a paste.
    browser.drupalRelativeURL('/node/add/article');
    browser.expect.element('#cke_edit-body-0-value').to.be.visible.before(5000);
    browser.click('#cke_edit-body-0-value');
    browser.keys([browser.Keys.CONTROL, 'v', browser.Keys.NULL]);
    browser.setValue('#edit-title-0-value', 'Testing image allowed');
    browser.click('#edit-submit');
    browser.assert.elementPresent('#test-image');

    // Enable the block image paste plugin.
    browser.drupalRelativeURL('/admin/config/content/formats/manage/full_html_ckeditor');
    browser.expect.element('#ckeditor-button-configuration').to.be.visible.before(5000);
    browser.click('a[href="#edit-editor-settings-plugins-blockimagepaste"]');
    browser.click('input[name="editor[settings][plugins][blockimagepaste][block_image_paste_enabled]"]');
    browser.click('#edit-actions-submit');

    // Goto the image test page and copy.
    browser.drupalRelativeURL('/image-test-page');
    browser.keys([browser.Keys.CONTROL, 'a', browser.Keys.NULL]);
    browser.keys([browser.Keys.CONTROL, 'c', browser.Keys.NULL]);

    // Create an article and attempt a paste.
    browser.drupalRelativeURL('/node/add/article');
    browser.expect.element('#cke_edit-body-0-value').to.be.visible.before(5000);
    browser.click('#cke_edit-body-0-value');
    browser.keys([browser.Keys.CONTROL, 'v', browser.Keys.NULL]);
    browser.dismissAlert();
    browser.setValue('#edit-title-0-value', 'Testing image blocked');
    browser.click('#edit-submit');
    browser.assert.elementNotPresent('#test-image');
  },

};

