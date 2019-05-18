<?php

namespace Drupal\external_data_source\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a External Data Source item annotation object.
 *
 * @see \Drupal\external_data_source\Plugin\ExternalDataSourceManager
 * @see plugin_api
 *
 * @Annotation
 */
class ExternalDataSource extends Plugin
{

    /**
     * The plugin ID.
     *
     * @var string
     */
    public $id;

    /**
     * The name of the plugin.
     *
     * @var \Drupal\Core\Annotation\Translation
     *
     * @ingroup plugin_translatable
     */
    public $name;

    /**
     * The description of the plugin.
     *
     * @var \Drupal\Core\Annotation\Translation
     *
     * @ingroup plugin_translatable
     */
    public $description;

}
