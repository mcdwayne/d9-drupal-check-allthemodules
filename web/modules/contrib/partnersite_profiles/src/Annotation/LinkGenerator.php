<?php

namespace Drupal\partnersite_profile\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Access Link generator item annotation object.
 *
 * @see \Drupal\partnersite_profile\Plugin\LinkGeneratorManager
 * @see plugin_api
 *
 * @Annotation
 */
class LinkGenerator extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

	/**
	 * The access link generated type of the plugin.
	 *
	 * @var \Drupal\Core\Annotation\Translation
	 *
	 * @ingroup plugin_translatable
	 */
	public $type;

}
