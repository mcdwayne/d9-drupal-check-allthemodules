<?php

namespace Drupal\administration_language_negotiation\Annotation;

use Drupal\Core\Condition\Annotation\Condition;

/**
 * Defines a administration language negotiation condition annotation object.
 *
 * Plugin Namespace: Plugin\AdministrationLanguageNegotiationCondition.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class AdministrationLanguageNegotiationCondition extends Condition
{

  /**
   * The administration language negotiation condition plugin ID.
   *
   * @var string
   */
    public $id;

    /**
     * The default weight of the administration language negotiation condition plugin.
     *
     * @var int
     */
    public $weight;

    /**
     * The human-readable name of the administration language negotiation condition plugin.
     *
     * @var \Drupal\Core\Annotation\Translation
     *
     * @ingroup plugin_translatable
     */
    public $name;

    /**
     * The description of the administration language negotiation condition plugin.
     *
     * @var \Drupal\Core\Annotation\Translation
     *
     * @ingroup plugin_translatable
     */
    public $description;
}
