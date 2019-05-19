<?php

namespace Drupal\Tests\textimage\Kernel;

use Drupal\image\Entity\ImageStyle;

/**
 * Trait to manage Textimage setup tasks common across tests.
 */
trait TextimageTestTrait {

  /**
   * The Textimage factory service.
   *
   * @var \Drupal\textimage\TextimageFactoryInterface
   */
  protected $textimageFactory;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The entity display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Common test initialization tasks.
   */
  public function initTextimageTest() {
    // Load services.
    $this->textimageFactory = \Drupal::service('textimage.factory');
    $this->renderer = \Drupal::service('renderer');
    $this->fileSystem = \Drupal::service('file_system');
    $this->entityDisplayRepository = \Drupal::service('entity_display.repository');

    // Change Image Effects settings.
    $config = \Drupal::configFactory()->getEditable('image_effects.settings');
    $config
      ->set('image_selector.plugin_id', 'dropdown')
      ->set('image_selector.plugin_settings.dropdown.path', drupal_get_path('module', 'image_effects') . '/tests/images')
      ->set('font_selector.plugin_id', 'dropdown')
      ->set('font_selector.plugin_settings.dropdown.path', drupal_get_path('module', 'image_effects') . '/tests/fonts/LinLibertineTTF_5.3.0_2012_07_02')
      ->save();

    // Change Textimage settings.
    $config = \Drupal::configFactory()->getEditable('textimage.settings');
    $config
      ->set('url_generation.enabled', TRUE)
      ->set('debug', TRUE)
      ->set('default_font.name', 'Linux Libertine')
      ->set('default_font.uri', drupal_get_path('module', 'image_effects') . '/tests/fonts/LinLibertineTTF_5.3.0_2012_07_02/LinLibertine_Rah.ttf')
      ->save();

    // Create a test image style, with a image_effects_text_overlay effect.
    $style = ImageStyle::create([
      'name' => 'textimage_test',
      'label' => 'Textimage Test',
    ]);
    $style->addImageEffect([
      'id' => 'image_effects_text_overlay',
      'data' => [
        'font' => [
          'name' => 'Linux Libertine',
          'uri' => drupal_get_path('module', 'image_effects') . '/tests/fonts/LinLibertineTTF_5.3.0_2012_07_02/LinLibertine_Rah.ttf',
          'size' => 16,
          'angle' => 0,
          'color' => '#000000FF',
          'stroke_mode' => 'outline',
          'stroke_color' => '#000000FF',
          'outline_top' => 0,
          'outline_right' => 0,
          'outline_bottom' => 0,
          'outline_left' => 0,
          'shadow_x_offset' => 1,
          'shadow_y_offset' => 1,
          'shadow_width' => 0,
          'shadow_height' => 0,
        ],
        'layout' => [
          'padding_top' => 0,
          'padding_right' => 0,
          'padding_bottom' => 0,
          'padding_left' => 0,
          'x_pos' => 'center',
          'y_pos' => 'center',
          'x_offset' => 0,
          'y_offset' => 0,
          'background_color' => NULL,
          'overflow_action' => 'extend',
          'extended_color' => NULL,
        ],
        'text' => [
          'strip_tags' => TRUE,
          'decode_entities' => TRUE,
          'maximum_width' => 0,
          'fixed_width' => FALSE,
          'align' => 'left',
          'line_spacing' => 0,
          'case_format' => '',
          'maximum_chars' => NULL,
          'excess_chars_text' => '…',
        ],
        'text_string'             => 'Test preview',
      ],
    ]);
    $style->save();
  }

  /**
   * Asserts a Textimage.
   */
  protected function assertTextimage($path, $width, $height) {
    $image = \Drupal::service('image.factory')->get($path);
    $w_error = abs($image->getWidth() - $width);
    $h_error = abs($image->getHeight() - $height);
    $tolerance = 0.1;
    $this->assertTrue($w_error < $width * $tolerance && $h_error < $height * $tolerance, "Textimage {$path} width and height ({$image->getWidth()}x{$image->getHeight()}) approximate expected results ({$width}x{$height})");
  }

  /**
   * Returns the URI of a Textimage based on style name and text.
   */
  protected function getTextimageUriFromStyleAndText($style_name, $text) {
    return $this->textimageFactory->get()
      ->setStyle(ImageStyle::load($style_name))
      ->process($text)
      ->getUri();
  }

  /**
   * Returns the Url object of a Textimage based on style name and text.
   */
  protected function getTextimageUrlFromStyleAndText($style_name, $text) {
    return $this->textimageFactory->get()
      ->setStyle(ImageStyle::load($style_name))
      ->process($text)
      ->getUrl();
  }

}
