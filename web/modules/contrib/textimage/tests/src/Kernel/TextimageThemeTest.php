<?php

namespace Drupal\Tests\textimage\Kernel;

use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Textimage theme functions.
 *
 * @group Textimage
 */
class TextimageThemeTest extends KernelTestBase {

  use TextimageTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'textimage',
    'image',
    'image_effects',
    'user',
    'file_mdm',
    'file_mdm_font',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig([
      'system',
      'textimage',
      'image',
      'image_effects',
      'user',
      'file_mdm',
      'file_mdm_font',
    ]);
    $this->initTextimageTest();
  }

  /**
   * Test the Textimage formatter theme.
   */
  public function testTextimageFormatterTheme() {

    $textimage = $this->textimageFactory->get();
    $textimage
      ->setStyle(ImageStyle::load('medium'))
      ->process(['one', 'two'])
      ->buildImage();

    // Test output of theme textimage_formatter.
    $output = [
      '#theme' => 'textimage_formatter',
      '#uri' => $textimage->getUri(),
      '#width' => $textimage->getWidth(),
      '#height' => $textimage->getHeight(),
      '#alt' => 'Alternate text',
      '#title' => 'Textimage title',
      '#attributes' => ['class' => 'textimage-test'],
      '#image_container_attributes' => ['class' => ['textimage-container-test']],
      '#anchor_url' => $textimage->getUrl(),
    ];
    $this->setRawContent($this->renderer->renderRoot($output));
    $abs_url = $textimage->getUrl()->toString();
    $rel_url = file_url_transform_relative($abs_url);
    // @todo changing behaviour in D8.1, need to watch #2646744
    $elements = $this->cssSelect("a[href='$abs_url'] div.textimage-container-test img[src='$rel_url']");
    $this->assertNotEmpty($elements, 'Textimage formatted correctly.');
  }

}
