<?php

namespace Drupal\Tests\whitelabel\Functional;

use Drupal\file\Entity\File;

/**
 * Tests the caching system during white label switching.
 *
 * @group whitelabel
 */
class WhiteLabelCacheTest extends WhiteLabelTestBase {

  /**
   * Holds the second generated white label throughout the different tests.
   *
   * @var \Drupal\whitelabel\WhiteLabelInterface
   */
  private $whiteLabel2;

  /**
   * Holds the site's default name (Drupal).
   *
   * @var string
   */
  private $defaultName;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'whitelabel_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $image_files = $this->drupalGetTestFiles('image');

    // Create a secondary white label.
    $this->whiteLabel2 = $this->drupalCreateWhiteLabel([
      'token' => 'token2',
      'uid' => $this->whiteLabelOwner->id(),
      'name' => 'Another name for white label 2',
      'slogan' => 'Still rocking :)',
      'logo' => File::create((array) next($image_files)),
    ]);

    $this->defaultName = $this->config('system.site')->get('name');
  }

  /**
   * Test to see if the HTML titles get overwritten.
   */
  public function testWhiteLabelHtmlTitles() {
    // No white label.
    $this->drupalGet('<front>');
    // Check page title.
    $this->assertSession()->elementTextContains('css', 'title', $this->defaultName);

    // White label 1.
    $this->setCurrentWhiteLabel($this->whiteLabel);
    $this->drupalGet('<front>');
    // Check page title.
    $this->assertSession()->elementTextContains('css', 'title', $this->whiteLabel->getName());

    // White label 2.
    $this->setCurrentWhiteLabel($this->whiteLabel2);
    $this->drupalGet('<front>');
    // Check page title.
    $this->assertSession()->elementTextContains('css', 'title', $this->whiteLabel2->getName());

    // White label 2 (updated).
    $this->whiteLabel2
      ->setName('Updated name')
      ->setSlogan('Rocking some more.')
      ->save();
    $this->drupalGet('<front>');
    // Check page title.
    $this->assertSession()->elementTextContains('css', 'title', $this->whiteLabel2->getName());

    // White label 1.
    $this->setCurrentWhiteLabel($this->whiteLabel);
    $this->drupalGet('<front>');
    // Check page title.
    $this->assertSession()->elementTextContains('css', 'title', $this->whiteLabel->getName());

    // No white label (again).
    $this->resetWhiteLabel();
    $this->drupalGet('<front>');
    // Check page title.
    $this->assertSession()->elementTextContains('css', 'title', $this->defaultName);
  }

  /**
   * Test to see if the system branding block shows the right values.
   */
  public function testWhiteLabelPageCaching() {
    // Place branding block with site name and slogan into header region.
    $this->drupalPlaceBlock('system_branding_block', [
      'region' => 'header',
    ]);

    // No white label.
    $this->drupalGet('<front>');
    // Should contain.
    $this->inBrandingBlock($this->defaultName);
    // Should not contain.
    $this->notInBrandingBlock($this->whiteLabel->getName());
    $this->notInBrandingBlock($this->whiteLabel->getSlogan());
    $this->notInImagePath(file_url_transform_relative(file_create_url($this->whiteLabel->getLogo()->getFileUri())));
    $this->notInBrandingBlock($this->whiteLabel2->getName());
    $this->notInBrandingBlock($this->whiteLabel2->getSlogan());
    $this->notInImagePath(file_url_transform_relative(file_create_url($this->whiteLabel2->getLogo()->getFileUri())));

    // White label 1.
    $this->setCurrentWhiteLabel($this->whiteLabel);
    $this->drupalGet('<front>');
    // Should contain.
    $this->inBrandingBlock($this->whiteLabel->getName());
    $this->inBrandingBlock($this->whiteLabel->getSlogan());
    $this->inImagePath(file_url_transform_relative(file_create_url($this->whiteLabel->getLogo()->getFileUri())));
    // Should not contain.
    $this->notInBrandingBlock($this->defaultName);
    $this->notInBrandingBlock($this->whiteLabel2->getName());
    $this->notInBrandingBlock($this->whiteLabel2->getSlogan());
    $this->notInImagePath(file_url_transform_relative(file_create_url($this->whiteLabel2->getLogo()->getFileUri())));

    // White label 2.
    $this->setCurrentWhiteLabel($this->whiteLabel2);
    $this->drupalGet('<front>');
    // Should contain.
    $this->inBrandingBlock($this->whiteLabel2->getName());
    $this->inBrandingBlock($this->whiteLabel2->getSlogan());
    $this->inImagePath(file_url_transform_relative(file_create_url($this->whiteLabel2->getLogo()->getFileUri())));
    // Should not contain.
    $this->notInBrandingBlock($this->defaultName);
    $this->notInBrandingBlock($this->whiteLabel->getName());
    $this->notInBrandingBlock($this->whiteLabel->getSlogan());
    $this->notInImagePath(file_url_transform_relative(file_create_url($this->whiteLabel->getLogo()->getFileUri())));

    // White label 2 (updated).
    $this->whiteLabel2
      ->setName('Updated name')
      ->setSlogan('Rocking some more.')
      ->save();
    $this->drupalGet('<front>');
    // Should contain.
    $this->inBrandingBlock($this->whiteLabel2->getName());
    $this->inBrandingBlock($this->whiteLabel2->getSlogan());
    $this->inImagePath(file_url_transform_relative(file_create_url($this->whiteLabel2->getLogo()->getFileUri())));
    // Should not contain.
    $this->notInBrandingBlock($this->defaultName);
    $this->notInBrandingBlock($this->whiteLabel->getName());
    $this->notInBrandingBlock($this->whiteLabel->getSlogan());
    $this->notInImagePath(file_url_transform_relative(file_create_url($this->whiteLabel->getLogo()->getFileUri())));

    // White label 1 (again).
    $this->setCurrentWhiteLabel($this->whiteLabel);
    $this->drupalGet('<front>');
    // Should contain.
    $this->inBrandingBlock($this->whiteLabel->getName());
    $this->inBrandingBlock($this->whiteLabel->getSlogan());
    $this->inImagePath(file_url_transform_relative(file_create_url($this->whiteLabel->getLogo()->getFileUri())));
    // Should not contain.
    $this->notInBrandingBlock($this->defaultName);
    $this->notInBrandingBlock($this->whiteLabel2->getName());
    $this->notInBrandingBlock($this->whiteLabel2->getSlogan());
    $this->notInImagePath(file_url_transform_relative(file_create_url($this->whiteLabel2->getLogo()->getFileUri())));

    // No white label (again).
    $this->resetWhiteLabel();
    $this->drupalGet('<front>');
    // Should contain.
    $this->inBrandingBlock($this->defaultName);
    // Should not contain.
    $this->notInBrandingBlock($this->whiteLabel->getName());
    $this->notInBrandingBlock($this->whiteLabel->getSlogan());
    $this->notInImagePath(file_url_transform_relative(file_create_url($this->whiteLabel->getLogo()->getFileUri())));
    $this->notInBrandingBlock($this->whiteLabel2->getName());
    $this->notInBrandingBlock($this->whiteLabel2->getSlogan());
    $this->notInImagePath(file_url_transform_relative(file_create_url($this->whiteLabel2->getLogo()->getFileUri())));
  }

}
