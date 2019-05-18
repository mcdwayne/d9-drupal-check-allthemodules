<?php

namespace Drupal\Tests\insert_colorbox\FunctionalJavascript;

/**
 * Tests Insert module's insert_colorbox module sub-module.
 * 
 * @group insert
 */
class InsertColorboxTest extends InsertColorboxTestBase {

  public function testWithoutGallery() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName, ['alt_field' => '0']);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'colorbox__thumbnail' => 'colorbox__thumbnail',
      ],
      'default' => 'colorbox__thumbnail',
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

    $this->assertTrue(strpos($body->getValue(), 'class="colorbox insert-colorbox"'), 'Verified colorbox classes being set: ' . $body->getValue());
    $this->assertEquals(0, preg_match('!data-colorbox-gallery="[^"]+"!', $body->getValue()), 'Verified data-colorbox-gallery attribute being empty: ' . $body->getValue());
  }

  public function testGallery() {
    $page = $this->gotoInsertConfig();
    $page->findField($this->t('Per page gallery'))->click();
    $this->saveInsertConfig($page);

    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName, ['alt_field' => '0']);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'colorbox__thumbnail' => 'colorbox__thumbnail',
      ],
      'default' => 'colorbox__thumbnail',
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

    $this->assertTrue(strpos($body->getValue(), 'class="colorbox insert-colorbox"'), 'Verified colorbox classes being set: ' . $body->getValue());
    $this->assertTrue(strpos($body->getValue(), 'data-colorbox-gallery="gallery-all-'), 'Verified data-colorbox-gallery attribute being set: ' . $body->getValue());
  }

  public function testLinkWidgetSetting() {
    $page = $this->gotoInsertConfig();
    $page->findField($this->t('Image style'))->selectOption('0');
    $this->saveInsertConfig($page);

    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName, ['alt_field' => '0']);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'colorbox__thumbnail' => 'colorbox__thumbnail',
      ],
      'default' => 'colorbox__thumbnail',
      'link_image' => 'large',
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

    $this->assertEquals(1, preg_match('!^<a href=".+/large/[^"]+" class="colorbox insert-colorbox"[^>]+>\s*<img src=".+/thumbnail/[^>]+></a>$!', $body->getValue()), 'Inserted colorbox link using the style link provided by Insert widget setting: ' . $body->getValue());
  }

}
