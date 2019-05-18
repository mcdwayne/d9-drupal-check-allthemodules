<?php

namespace Drupal\search_api_saved_searches\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a notification plugin annotation object.
 *
 * @see \Drupal\search_api_saved_searches\Notification\NotificationPluginManager
 * @see \Drupal\search_api_saved_searches\Notification\NotificationPluginInterface
 * @see \Drupal\search_api_saved_searches\Notification\NotificationPluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class SearchApiSavedSearchesNotification extends Plugin {

  /**
   * The notification plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the notification plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The notification description.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

}
