<?php

namespace Drupal\file_downloader\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a DownloadOption annotation object.
 *
 * @ingroup file_downloader
 *
 * @Annotation
 */
class DownloadOption extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the download option.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label = '';

  /**
   * The description of the download option.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

}
