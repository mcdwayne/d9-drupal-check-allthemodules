<?php

namespace Drupal\entity_expiration\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a SpectraExpirationMethod annotation object.
 *
 * Plugin Namespace: Plugin\Spectra\ExpirationMethod
 *
 * @see plugin_api
 *
 * @Annotation
 */
class EntityExpirationMethod extends Plugin {

    /**
     * The plugin ID.
     *
     * @var string
     */
    public $id;

    /**
     * The human-readable name of the SpectraPlugin.
     *
     * @ingroup plugin_translatable
     *
     * @var \Drupal\Core\Annotation\Translation
     */
    public $label;

    /**
     * The category under which the SpectraPlugin should be listed in the UI.
     *
     * @var \Drupal\Core\Annotation\Translation
     *
     * @ingroup plugin_translatable
     */
    public $category;

}
