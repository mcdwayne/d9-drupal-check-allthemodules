<?php

namespace Drupal\Tests\insert_responsive_image\FunctionalJavascript;

use Drupal\Core\Serialization\Yaml;
use Drupal\Tests\insert\FunctionalJavascript\InsertImageTestBase;

abstract class InsertResponsiveImageTestBase extends InsertImageTestBase {

  /**
   * @inheritdoc
   */
  public static $modules = [
    'node',
    'image',
    'insert',
    'field_ui',
    'responsive_image',
    'insert_responsive_image',
  ];

  /**
   * @inheritdoc
   */
  protected function setUp() {
    parent::setUp();

    // Install the 'wide' responsive image style.
    foreach (['2600x2600', '1300x1300', '650x650', '325x325'] as $size) {
      $imageStyle = Yaml::decode(
        file_get_contents(DRUPAL_ROOT . '/core/profiles/standard/config/optional/image.style.max_' . $size . '.yml')
      );
      $this->config('image.style.max_' . $size)->setData($imageStyle)->save(TRUE);
    }
    $wideImageStyle = Yaml::decode(
      file_get_contents(DRUPAL_ROOT . '/core/profiles/standard/config/optional/responsive_image.styles.wide.yml')
    );
    $this->config('responsive_image.styles.wide')->setData($wideImageStyle)->save(TRUE);
  }

}
