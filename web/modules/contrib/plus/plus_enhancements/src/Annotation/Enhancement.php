<?php

namespace Drupal\plus_enhancement\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Enhancement annotation object.
 *
 * Plugin Namespace: "Plugin/Enhancement".
 *
 * @see \Drupal\plus_enhancements\Plugin\Enhancement\EnhancementInterface
 * @see \Drupal\plus_enhancements\EnhancementsPluginManager
 * @see plugin_api
 *
 * @Annotation
 *
 * @ingroup plugins_enhancements
 */
class Enhancement extends Plugin {

  protected $id = '';

  protected $conditions = [];

  protected $css = [];

  protected $dependencies = [];

  protected $description = '';

  protected $enabled = FALSE;

  protected $experimental = FALSE;

  protected $group = 'general';

  protected $js = [];

  protected $path = NULL;

  protected $settings = [];

  protected $title = '';

  protected $ui = TRUE;

  protected $version = NULL;

}
