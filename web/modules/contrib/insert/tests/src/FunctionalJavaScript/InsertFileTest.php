<?php

namespace Drupal\Tests\insert\FunctionalJavascript;

/**
 * Tests Insert module's generic file insert capability.
 *
 * @group insert
 */
class InsertFileTest extends InsertFileTestBase {

  public function testInsertDisabled() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createFileField($fieldName);

    $page = $this->getSession()->getPage();

    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $this->assertEquals(0, count($page->findAll('css', '.insert')), 'Insert container node does not exists');
  }

  public function testInsertDisabledAfterEnabling() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createFileField($fieldName);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'link' => 1,
        'icon_link' => 1,
      ],
      'default' => 'link',
    ]);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'link' => 0,
        'icon_link' => 0,
      ],
    ]);

    $page = $this->getSession()->getPage();

    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $this->assertEquals(0, count($page->findAll('css', '.insert')), 'Insert container node does not exists');
  }

  public function testSingleStyle() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createFileField($fieldName);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'link' => 'link',
      ],
      'default' => 'link',
    ]);

    $this->drupalGet('node/add/article');

    $page = $this->getSession()->getPage();
    $files = $this->drupalGetTestFiles('text');

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $this->assertEquals(1, count($page->findAll('css', '.insert')), 'Insert container node exists');
    $this->assertEquals(1, count($page->findAll('css', '.insert > .insert-templates')), 'Insert templates exist');
    $this->assertEquals(1, count($page->findAll('css', '[name="' . $fieldName . '[0][insert_template][link]"]')), 'Insert link template exists');
    $this->assertEquals(1, count($page->findAll('css', '.insert > input.insert-filename')), 'Insert filename input node exists');
    $this->assertEquals(1, count($page->findAll('css', '.insert > input.insert-style')), 'Insert style input node exists');
    $this->assertEquals('link', $page->find('css', '.insert > .insert-style')->getValue(), 'Insert style value is "link"');
    $this->assertEquals(1, count($page->findAll('css', '.insert input.insert-button')), 'Insert button exists');
  }

  public function testAutomaticStyle() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createFileField($fieldName);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'insert__auto' => 'insert__auto',
      ],
      'default' => 'insert__auto',
    ]);

    $this->drupalGet('node/add/article');

    $page = $this->getSession()->getPage();
    $files = $this->drupalGetTestFiles('text');

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $page->findButton('Insert')->click();

    $body = $page->findField('body[0][value]');

    $this->assertEquals(1, preg_match('!^<a[^>]+>[^<]+</a>!', $body->getValue()), 'Correctly inserted using AUTOMATIC style: ' . $body->getValue());
  }

  public function testInsertStyleSelectDefault() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createFileField($fieldName);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'link' => 1,
        'icon_link' => 1,
      ],
      'default' => 'link',
    ]);

    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $this->assertEquals(1, count($page->findAll('css', '[name="' . $fieldName . '[0][insert_template][link]"]')), 'Insert link template exists');
    $this->assertEquals(1, count($page->findAll('css', '[name="' . $fieldName . '[0][insert_template][icon_link]"]')), 'Insert icon link template exists');
    $this->assertEquals(1, count($page->findAll('css', '.insert select.insert-style')), 'Insert style select box exists');
    $this->assertEquals(1, count($page->findAll('css', '.insert select.insert-style > option[value="link"]')), 'Insert style option "link" exists');
    $this->assertEquals(1, count($page->findAll('css', '.insert select.insert-style > option[value="icon_link"]')), 'Insert style option "icon link" exists');

    $page->findButton('Insert')->click();

    $body = $page->findField('body[0][value]');

    $this->assertEquals(1, preg_match('!<a href="[^"]+/text-0.txt" data-insert-type="file" data-insert-attach="[^"]+">text-0.txt</a>!', $body->getValue()), 'Verified inserted HTML: "' . $body->getValue() . '"');
  }

  public function testMultipleInsertOperations() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createFileField($fieldName);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'link' => 1,
        'icon_link' => 1,
      ],
      'default' => 'link',
    ]);

    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $body = $page->findField('body[0][value]');

    $page->findButton('Insert')->click();
    $this->assertEquals(1, preg_match_all('!<a [^>]+>[^<]+</a>!', $body->getValue()), 'Verified inserted HTML after inserting once: "' . $body->getValue() . '"');

    $page->findButton('Insert')->click();
    $this->assertEquals(2, preg_match_all('!<a [^>]+>[^<]+</a>!', $body->getValue()), 'Verified inserted HTML after inserting twice: "' . $body->getValue() . '"');

    $body->setValue($body->getValue() . 'insert after');
    // Simulate updated caret position:
    $this->getSession()->executeScript("var textarea = jQuery('#edit-body-0-value').get(0); textarea.selectionStart = textarea.selectionEnd = textarea.selectionStart + 'insert after'.length;");

    $page->findButton('Insert')->click();
    $this->assertEquals(1, preg_match('!^<a [^>]+>[^<]+</a><a [^>]+>[^<]+</a>insert after<a [^>]+>[^<]+</a>$!', $body->getValue()), 'Verified HTML after inserting three times: "' . $body->getValue() . '"');

    $body->setValue('insert before');
    $this->getSession()->executeScript("var textarea = jQuery('#edit-body-0-value').get(0); textarea.selectionStart = textarea.selectionEnd = 0;");
    $page->findButton('Insert')->click();

    $this->assertEquals(1, preg_match('!^<a [^>]+>[^<]+</a>insert before$!', $body->getValue()), 'Verified HTML inserted before existing content: "' . $body->getValue() . '"');
  }

  public function testInsertStyleSelectOption() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createFileField($fieldName);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'link' => 1,
        'icon_link' => 1,
      ],
      'default' => 'link',
    ]);

    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $page->find('css', '.insert select.insert-style')->selectOption('icon_link');

    $page->findButton('Insert')->click();

    $body = $page->findField('body[0][value]');

    $this->assertEquals(1, preg_match('!<span class="file [^"]+" contenteditable="false" data-insert-type="file"><a href="[^"]+/text-0.txt" type="text/plain; length=1024" data-insert-attach="[^"]+">text-0.txt</a>!', $body->getValue()), 'Verified inserted HTML: "' . $body->getValue() . '"');
  }

  public function testFocus() {
    $longText_field_name = strtolower($this->randomMachineName());
    $this->createTextField($longText_field_name, $this->contentTypeName);

    $fieldName = strtolower($this->randomMachineName());

    $this->createFileField($fieldName);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'link' => 1,
        'icon_link' => 1,
      ],
      'default' => 'link',
    ]);

    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $page->find('css', '#edit-' . $longText_field_name . '-0-value')->focus();
    $page->findButton('Insert')->click();

    $body = $page->findField('body[0][value]');

    $long_text_value = $page->find('css', '#edit-' . $longText_field_name . '-0-value')->getValue();

    $this->assertEquals('', $body->getValue(), 'Body is empty');
    $this->assertEquals(1, preg_match('!^<a [^>]+>text-0.txt</a>$!', $long_text_value), 'Inserted HTML into focused text area');

    $page->find('css', '#edit-body-0-value')->focus();
    $page->findButton('Insert')->click();

    $long_text_value = $page->find('css', '#edit-' . $longText_field_name . '-0-value')->getValue();

    $this->assertEquals(1, preg_match('!^<a [^>]+>text-0.txt</a>$!', $body->getValue()), 'Inserted HTML into body after refocusing: ' . $body->getValue());
    $this->assertEquals(1, preg_match('!^<a [^>]+>text-0.txt</a>$!', $long_text_value), 'Still, second text area has HTML inserted once: ' . $body->getValue());
  }

  public function testAbsoluteUrlSetting() {
    $fieldName = strtolower($this->randomMachineName());
    $this->createFileField($fieldName);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'link' => 1,
        'icon_link' => 1,
      ],
      'default' => 'link',
    ]);

    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $page->findButton('Insert')->click();
    $body = $page->findField('body[0][value]');
    $this->assertEquals(1, preg_match('!^<a href="/!', $body->getValue()), 'Inserted relative URL: ' . $body->getValue());

    $page = $this->gotoInsertConfig();
    $page->checkField('absolute');
    $this->saveInsertConfig($page);

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $page->findButton('Insert')->click();
    $body = $page->findField('body[0][value]');
    $this->assertEquals(1, preg_match('!^<a href="http://!', $body->getValue()), 'Inserted absolute URL: ' . $body->getValue());
  }

  public function testDescriptionField() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createFileField($fieldName, [
      'description_field' => '1',
    ]);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'link' => 1,
        'icon_link' => 1,
      ],
      'default' => 'link',
    ]);

    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $page->findField($fieldName . '[0][description]')->setValue('test-description');

    $page->findButton('Insert')->click();

    $body = $page->findField('body[0][value]');
    $this->assertEquals(1, preg_match('!<a[^>]+ title="test-description"[^>]*>test-description</a>!', $body->getValue()), 'Verified using description: "' . $body->getValue() . '"');
  }

  public function testAdditionalCssClassesSetting() {
    $page = $this->gotoInsertConfig();
    $page->findField('edit-file')->setValue('test-class-1 test-class-2');
    $this->saveInsertConfig($page);

    $fieldName = strtolower($this->randomMachineName());

    $this->createFileField($fieldName);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'link' => 1,
        'icon_link' => 1,
      ],
      'default' => 'link',
    ]);

    $files = $this->drupalGetTestFiles('text');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $page->findButton('Insert')->click();

    $body = $page->findField('body[0][value]');

    $this->assertEquals(1, preg_match('!class="test-class-1 test-class-2"!', $body->getValue()), 'Verified configured classes: "' . $body->getValue() . '"');
  }

  public function testInsertImagePerFileField() {
    $page = $this->gotoInsertConfig();
    $page->checkField('file_field_images_enabled');
    $this->saveInsertConfig($page);

    $fieldName = strtolower($this->randomMachineName());

    $this->createFileField($fieldName, ['file_extensions' => 'txt, png']);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'thumbnail' => 'thumbnail',
      ],
      'default' => 'thumbnail',
    ]);

    $files = $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $page->findButton('Insert')->click();

    $body = $page->findField('body[0][value]');

    $this->assertTrue(strpos($body->getValue(), '<img') !== FALSE, 'Placed image using img tag via generic file field: ' . $body->getValue());
  }

}
