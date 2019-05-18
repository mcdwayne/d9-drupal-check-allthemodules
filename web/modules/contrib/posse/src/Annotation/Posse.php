<?php

namespace Drupal\posse\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
* Defines the Posse plugin annotation object.
*
* Plugin namespace: Plugin\Posse
*
* @Annotation
*/
class Posse extends Plugin {

  /**
  * The plugin ID.
  */
  public $id;

  /**
  * The Administrative label.
  *
  * @ingroup plugin_translatable.
  *
  * @var \Drupal\Core\Annotation\Translation
  */
  public $label;

  /**
  * The display label.
  *
  * Shown in the UI for non-administrators.
  *
  * @ingroup plugin_translatable
  *
  * @var \Drupal\Core\Annotation\Translation
  */
  public $display_label;

}
