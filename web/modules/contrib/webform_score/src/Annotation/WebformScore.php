<?php

namespace Drupal\webform_score\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a webform score annotation object.
 *
 * Plugin Namespace: Plugin\WebformScore.
 *
 * @see hook_webform_score_info_alter()
 * @see \Drupal\webform_score\Plugin\WebformScoreInterface
 * @see \Drupal\webform_score\Plugin\WebformScoreManager
 * @see \Drupal\webform_score\Plugin\WebformScoreManagerInterface
 * @see plugin_api
 *
 * @Annotation
 */
class WebformScore extends Plugin {

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
   * List of data type plugin IDs this plugin is capable of scoring.
   *
   * @var string[]
   */
  public $compatible_data_types = [];

  /**
   * Whether this webform score plugin is aggregation one.
   *
   * Aggregation plugins decorate a set of non-aggregation plugins and derive
   * their score using some aggregation function from the underlying
   * non-aggregation scores.
   *
   * @var bool
   */
  public $is_aggregation = FALSE;

}
