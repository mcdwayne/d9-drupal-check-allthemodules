<?php

namespace Drupal\zsm\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ZSMPlugin annotation object.
 *
 * Plugin Namespace: Plugin\ZSMPlugin
 *
 * @see plugin_api
 *
 * @Annotation
 */
class ZSMPlugin extends Plugin {

    /**
     * The plugin ID.
     *
     * @var string
     */
    public $id;

    /**
     * The human-readable name of the ZSMPlugin.
     *
     * @ingroup plugin_translatable
     *
     * @var \Drupal\Core\Annotation\Translation
     */
    public $label;

    /**
     * The category under which the ZSMPlugin should be listed in the UI.
     *
     * @var \Drupal\Core\Annotation\Translation
     *
     * @ingroup plugin_translatable
     */
    public $category;

}
