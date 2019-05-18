<?php

namespace Drupal\api_tokens\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an API token annotation object.
 *
 * @Annotation
 */
class ApiToken extends Plugin {

  /**
   * The API token ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the API token.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The administrative description of the API token.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

}
