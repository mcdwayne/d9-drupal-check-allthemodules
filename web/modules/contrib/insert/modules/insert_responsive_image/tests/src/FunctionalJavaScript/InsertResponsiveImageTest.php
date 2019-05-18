<?php

namespace Drupal\Tests\insert_responsive_image\FunctionalJavascript;

/**
 * Tests Insert module's insert_responsive_image sub-module.
 *
 * @group insert
 */
class InsertResponsiveImageTest extends InsertResponsiveImageTestBase {

  public function testInsertingPictureTag() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName, ['alt_field' => '0']);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'responsive_image__wide' => 'responsive_image__wide',
      ],
      'default' => 'responsive_image__wide',
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

    $this->assertEquals(1, preg_match('!srcset="[^"]+"!', $body->getValue()), 'Applied srcset attribute: ' . $body->getValue());

    $this->assertEquals(1, preg_match('!sizes="[^"]+"!', $body->getValue()), 'Applied sizes attribute: ' . $body->getValue());
  }

}
