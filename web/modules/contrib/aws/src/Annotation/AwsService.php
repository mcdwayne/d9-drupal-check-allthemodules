<?php

namespace Drupal\aws\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the AWS Service plugin annotation object.
 *
 * Plugin namespace: Plugin\AWS\Service.
 *
 * @Annotation
 */
class AwsService extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The service name.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The service description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
