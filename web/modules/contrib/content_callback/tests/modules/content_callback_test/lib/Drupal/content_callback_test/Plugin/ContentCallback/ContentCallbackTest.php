<?php

/**
 * @file
 * Contains \Drupal\content_callback_test\Plugin\ContentCallback\ContentCallbackTest.php.
 */

namespace Drupal\content_callback_test\Plugin\ContentCallback;

use Drupal\Core\Annotation\Translation;
use Drupal\content_callback\Annotation\ContentCallback;
use Drupal\content_callback\Plugin\ContentCallback\PluginBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * A test callback
 *
 * @ContentCallback(
 *   id = "content_callback_test",
 *   title = @Translation("Test callback"),
 *   entity_types =  {
 *     "node"
 *   }
 * )
 */
class ContentCallbackTest extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = array(
      '#markup' => 'this is a test content callback',
    );

    return $build;
  }
}
