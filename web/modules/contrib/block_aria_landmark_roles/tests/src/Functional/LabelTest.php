<?php

namespace Drupal\Tests\block_aria_landmark_roles\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Test adding ARIA labels.
 *
 * @group block_aria_landmark_roles
 */
class LabelTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'block_aria_landmark_roles',
  ];

  /**
   * Test adding an ARIA label to a block.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAddingALabel() {
    $output = 'id="block-mainpagecontent" aria-label="foo"';

    $this->assertSession()->responseNotContains($output);

    $this->drupalLogin($this->createUser(['administer blocks']));

    $this->drupalPostForm(
      'admin/structure/block/add/system_main_block/classy',
      [
        'region' => 'content',
        'third_party_settings[block_aria_landmark_roles][label]' => 'foo',
      ],
      $this->t('Save block')
    );

    $this->assertSession()->responseContains($output);
  }

  /**
   * Ensure the ARIA label input field exists.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testAdminFormSetting() {
    $this->drupalLogin($this->drupalCreateUser(['administer blocks']));

    $this->drupalGet('admin/structure/block/add/system_main_block/classy');

    $this->assertSession()->fieldExists('third_party_settings[block_aria_landmark_roles][label]');
  }

}
