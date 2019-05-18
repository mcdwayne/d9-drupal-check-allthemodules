<?php

namespace Drupal\Tests\insert\FunctionalJavascript;

/**
 * Tests Insert module's image insert capability.
 * 
 * @group insert
 */
class InsertImageTest extends InsertImageTestBase {

  public function testInsertDisabled() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName);

    $this->drupalGet('node/add/article');

    $page = $this->getSession()->getPage();
    $images = $this->drupalGetTestFiles('image');

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $this->assertEquals(0, count($page->findAll('css', '.insert')), 'Insert container node does not exists');
  }

  public function testSingleStyle() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'link' => 'link',
      ],
      'default' => 'link',
    ]);

    $this->drupalGet('node/add/article');

    $page = $this->getSession()->getPage();
    $images = $this->drupalGetTestFiles('image');

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
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

    $this->createImageField($fieldName, ['alt_field' => '0']);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'insert__auto' => 'insert__auto',
      ],
    ]);

    $this->drupalGet('node/add/article');

    $page = $this->getSession()->getPage();
    $images = $this->drupalGetTestFiles('image');

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $page->findButton('Insert')->click();

    $body = $page->findField('body[0][value]');

    $this->assertEquals(1, preg_match('!^<img src=".+/files/[^/]+/[^/]+"[^>]+>!', $body->getValue()), 'Inserted using AUTOMATIC style: ' . $body->getValue());
  }

  public function testAutomaticAlteredStyle() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName, ['alt_field' => '0']);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'insert__auto' => 'insert__auto',
      ],
      'auto_image_style' => 'thumbnail',
    ]);

    $this->drupalGet('node/add/article');

    $page = $this->getSession()->getPage();
    $images = $this->drupalGetTestFiles('image');

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $page->findButton('Insert')->click();

    $body = $page->findField('body[0][value]');

    $this->assertEquals(1, preg_match('!^<img src=".+/files/styles/thumbnail/public/[^/]+/[^/]+"[^>]+>!', $body->getValue()), 'Inserted using AUTOMATIC style: ' . $body->getValue());
  }

  public function testOriginalImageRotation() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName, ['alt_field' => '0']);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'image' => 'image',
      ],
      'default' => 'image',
      'rotate' => '1',
    ]);

    $images = $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $page->findButton('Insert')->click();

    $body = $page->findField('body[0][value]');

    $this->assertTrue(
      strpos($body->getValue(), 'width="40"') !== FALSE &&
      strpos($body->getValue(), 'height="20"') !== FALSE,
      'Verified default dimension attributes: ' . $body->getValue()
    );

    $page->findLink('↺')->click();

    $body->waitFor(20, function($element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      return strpos($element->getValue(), 'width="20"') !== FALSE;
    });

    $this->assertTrue(
      strpos($body->getValue(), 'width="20"') !== FALSE &&
      strpos($body->getValue(), 'height="40"') !== FALSE,
      'Switched dimension attribute values: ' . $body->getValue()
    );

    $page->findLink('↺')->click();

    $body->waitFor(20, function($element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      return strpos($element->getValue(), 'width="40"') !== FALSE;
    });

    $this->assertTrue(
      strpos($body->getValue(), 'width="40"') !== FALSE &&
      strpos($body->getValue(), 'height="20"') !== FALSE,
      'Switched dimension attribute values again after rotating a second time: ' . $body->getValue()
    );
  }

  public function testStyledImageRotation() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName, ['alt_field' => '0']);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'thumbnail' => 'thumbnail',
      ],
      'default' => 'thumbnail',
      'rotate' => '1',
    ]);

    $images = $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $page->findButton('Insert')->click();

    $body = $page->findField('body[0][value]');

    $this->assertTrue(
      strpos($body->getValue(), 'width="40"') !== FALSE &&
      strpos($body->getValue(), 'height="20"') !== FALSE,
      'Verified default dimension attributes: ' . $body->getValue()
    );

    $page->findLink('↺')->click();

    $body->waitFor(20, function($element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      return strpos($element->getValue(), 'width="20"') !== FALSE;
    });

    $this->assertTrue(
      strpos($body->getValue(), 'width="20"') !== FALSE &&
      strpos($body->getValue(), 'height="40"') !== FALSE,
      'Switched dimension attribute values: ' . $body->getValue()
    );

    $page->findLink('↺')->click();

    $body->waitFor(20, function($element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      return strpos($element->getValue(), 'width="40"') !== FALSE;
    });

    $this->assertTrue(
      strpos($body->getValue(), 'width="40"') !== FALSE &&
      strpos($body->getValue(), 'height="20"') !== FALSE,
      'Switched dimension attribute values again after rotating a second time: ' . $body->getValue()
    );
  }

  public function testRotationWithAbsoluteUrl() {
    $page = $this->gotoInsertConfig();
    $page->checkField('absolute');
    $this->saveInsertConfig($page);

    $fieldNames = [
      strtolower($this->randomMachineName()),
      strtolower($this->randomMachineName()),
    ];

    $this->createImageField($fieldNames[0], ['alt_field' => '0']);
    $this->createImageField($fieldNames[1], ['alt_field' => '0']);

    $this->updateInsertSettings($fieldNames[0], [
      'styles' => [
        'thumbnail' => 'thumbnail',
      ],
      'default' => 'image',
      'rotate' => '1',
    ]);

    $this->updateInsertSettings($fieldNames[1], [
      'styles' => [
        'thumbnail' => 'thumbnail',
      ],
      'default' => 'thumbnail',
      'rotate' => '1',
    ]);

    $files = $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldNames[0] . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $page->attachFileToField(
      'files[' . $fieldNames[1] . '_0]',
      \Drupal::service('file_system')->realpath($files[0]->uri)
    );

    $this->assertSession()->waitForField($fieldNames[0] . '[0][fids]');
    $this->assertSession()->waitForField($fieldNames[1] . '[0][fids]');

    $body = $page->findField('body[0][value]');

    $wrappers = [
      $page->find('css', '#edit-' . $fieldNames[0] . '-wrapper'),
      $page->find('css', '#edit-' . $fieldNames[1] . '-wrapper'),
    ];

    $wrappers[0]->findButton('Insert')->click();

    $this->assertTrue(
      strpos($body->getValue(), '<img src="http') !== FALSE,
      'Verified absolute path: ' . $body->getValue()
    );

    $this->assertTrue(
      strpos($body->getValue(), 'width="40"') !== FALSE &&
      strpos($body->getValue(), 'height="20"') !== FALSE,
      'Verified default dimension attributes: ' . $body->getValue()
    );

    $wrappers[0]->findLink('↺')->click();

    $body->waitFor(20, function($element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      return strpos($element->getValue(), 'width="20"') !== FALSE;
    });

    $this->assertTrue(
      strpos($body->getValue(), '<img src="http') !== FALSE,
      'Verified absolute path after rotating: ' . $body->getValue()
    );

    $this->assertTrue(
      strpos($body->getValue(), 'width="20"') !== FALSE &&
      strpos($body->getValue(), 'height="40"') !== FALSE,
      'Switched dimension attributes: ' . $body->getValue()
    );

    $body->setValue('');

    $wrappers[1]->findButton('Insert')->click();

    $this->assertTrue(
      strpos($body->getValue(), '<img src="http') !== FALSE,
      'Styled image - verified absolute path on: ' . $body->getValue()
    );

    $this->assertTrue(
      strpos($body->getValue(), 'width="40"') !== FALSE &&
      strpos($body->getValue(), 'height="20"') !== FALSE,
      'Styled image - verified default dimension attributes: ' . $body->getValue()
    );

    $wrappers[1]->findLink('↺')->click();

    $body->waitFor(20, function($element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      return strpos($element->getValue(), 'width="20"') !== FALSE;
    });

    $this->assertTrue(
      strpos($body->getValue(), '<img src="http') !== FALSE,
      'Styled image - verified absolute path after rotating: ' . $body->getValue()
    );

    $this->assertTrue(
      strpos($body->getValue(), 'width="20"') !== FALSE &&
      strpos($body->getValue(), 'height="40"') !== FALSE,
      'Styled image - switched dimension attributes: ' . $body->getValue()
    );
  }

  public function testImageUrlOutput() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName, ['alt_field' => '0']);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'image' => 'image',
        'thumbnail' => 'thumbnail',
      ],
      'default' => 'image',
    ]);

    $images = $this->drupalGetTestFiles('image');

    $this->drupalGet('admin/config/content/formats/manage/plain_text');
    $page = $this->getSession()->getPage();

    $page->findField('filters[filter_html_escape][status]')->uncheck();
    $page->findField('filters[editor_file_reference][status]')->check();
    $page->findButton('Save configuration')->click();

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $page->findButton('Insert')->click();
    $page->find('css', '.insert select.insert-style')->selectOption('thumbnail');
    $page->findButton('Insert')->click();

    $page->findField('title[0][value]')->setValue('title');
    $page->findButton('Save')->click();

    $page = $this->getSession()->getPage();

    $count = preg_match_all(
      '!(src="[^"]+")!',
      $page->find('css', '.field--name-body')->getHtml(),
      $matches
    );

    $this->assertEquals(2, $count, 'Verified two image being inserted in body.');

    $this->assertFalse(strpos($matches[0][0], 'thumbnail'), 'First image refers to original URL.');
    $this->assertTrue(strpos($matches[0][1], 'thumbnail') !== FALSE, 'Second image refers to style URL.');
  }

  public function testUpdatingAltAttribute() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'image' => 'image',
      ],
      'default' => 'image',
    ]);

    $images = $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $body = $page->findField('body[0][value]');
    $altField = $page->findField($fieldName . '[0][alt]');

    $altField->setValue('initial');
    $page->findButton('Insert')->click();
    $this->assertTrue(strpos($body->getValue(), 'alt="initial"') !== FALSE, 'Verified initial string set on alt attribute: ' . $body->getValue());
    $altField->setValue('altered');
    $this->assertTrue(strpos($body->getValue(), 'alt="altered"') !== FALSE, 'Verified altered string set on alt attribute: ' . $body->getValue());
  }

  public function testUpdatingTitleAttribute() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName, ['title_field' => '1']);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'image' => 'image',
      ],
      'default' => 'image',
    ]);

    $images = $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $body = $page->findField('body[0][value]');
    $altField = $page->findField($fieldName . '[0][alt]');
    $titleField = $page->findField($fieldName . '[0][title]');

    $altField->setValue('alt');
    $titleField->setValue('initial');
    $page->findButton('Insert')->click();
    $this->assertTrue(strpos($body->getValue(), 'title="initial"'), 'Verified initial string set on title attribute: ' . $body->getValue());
    $titleField->setValue('altered');
    $this->assertTrue(strpos($body->getValue(), 'title="altered"'), 'Verified altered string set on title attribute: ' . $body->getValue());
  }

  public function testUpdatingAltAttributeRevisitingForm() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'image' => 'image',
      ],
      'default' => 'image',
    ]);

    $images = $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $body = $page->findField('body[0][value]');
    $altField = $page->findField($fieldName . '[0][alt]');

    $altField->setValue('initial');
    $page->findButton('Insert')->click();

    $page->findField('title[0][value]')->setValue('title');
    $page->findButton('Save')->click();

    $this->drupalGet('node/1/edit');

    $altField = $page->findField($fieldName . '[0][alt]');
    $altField->setValue('altered');

    $this->assertTrue(strpos($body->getValue(), 'alt="altered"') !== FALSE, 'Verified altered string set on alt attribute: ' . $body->getValue());
  }

  public function testLinkImageSetting() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName, ['alt_field' => '0']);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'thumbnail' => 'thumbnail',
      ],
      'default' => 'thumbnail',
      'link_image' => 'large',
    ]);

    $this->drupalGet('node/add/article');

    $page = $this->getSession()->getPage();
    $images = $this->drupalGetTestFiles('image');

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $body = $page->findField('body[0][value]');

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $page->findButton('Insert')->click();

    $this->assertEquals(1, preg_match('!^<a href=".+/large/[^>]+><img src=".+/thumbnail/[^>]+></a>$!', $body->getValue()), 'Inserted linked image: ' . $body->getValue());
  }

  public function testCaption() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName, ['alt_field' => '0', 'title_field' => '1']);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'image' => 'image',
      ],
      'default' => 'image',
      'caption' => TRUE,
    ]);

    $images = $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $body = $page->findField('body[0][value]');
    $titleField = $page->findField($fieldName . '[0][title]');

    $titleField->setValue('some title');
    $page->findButton('Insert')->click();
    $this->assertEquals(1, preg_match('!data-caption="some title"[^>]*>$!', $body->getValue()), 'Verified caption being inserted: ' . $body->getValue());

    $titleField->setValue('');

    $this->assertEquals(0, preg_match('!data-caption="some title"[^>]*>$!', $body->getValue()), 'Verified caption being removed when erasing title field: ' . $body->getValue());

    $body->setValue('');

    $page->findButton('Insert')->click();
    $this->assertEquals(0, preg_match('!data-caption="some title"[^>]*>$!', $body->getValue()), 'Verified no caption being inserted when title is empty: ' . $body->getValue());

    $titleField->setValue('some title');
    $this->assertEquals(1, preg_match('!data-caption="some title"[^>]*>$!', $body->getValue()), 'Verified caption being inserted on images already placed: ' . $body->getValue());
  }

  public function testAlign() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName, ['alt_field' => '0']);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'image' => 'image',
      ],
      'default' => 'image',
      'align' => TRUE,
    ]);

    $images = $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $body = $page->findField('body[0][value]');

    $page->findButton('Insert')->click();

    $this->assertEquals(1, preg_match('!data-align="none"[^>]*>$!', $body->getValue()), 'Verified initial align attribute: ' . $body->getValue());

    $elements = $page->findAll('css', '.insert-align-controls input');
    end($elements)->click();

    $this->assertEquals(1, preg_match('!data-align="right"[^>]*>$!', $body->getValue()), 'Verified altering align attribute: ' . $body->getValue());

    $page->findField('title[0][value]')->setValue('title');
    $page->findButton('Save')->click();
    $this->drupalGet('node/1/edit');

    $this->assertEquals('right', $page->find('css', '.insert-align-controls input:checked')->getValue(), 'Verified align attribute after reloading form: ' . $body->getValue());

    $page->find('css', '.insert-align-controls input')->click();

    $this->assertEquals(1, preg_match('!data-align="none"[^>]*>$!', $body->getValue()), 'Verified reset align attribute: ' . $body->getValue());
  }

}
