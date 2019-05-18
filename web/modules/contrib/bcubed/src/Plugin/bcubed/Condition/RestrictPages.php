<?php

namespace Drupal\bcubed\Plugin\bcubed\Condition;

use Drupal\bcubed\ConditionBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides basic condition to restrict condition sets to running on specified pages.
 *
 * @Condition(
 *   id = "restrict_pages",
 *   label = @Translation("Restrict Pages"),
 *   description = @Translation("Setup pages for which this condition set will run"),
 *   settings = {
 *     "mode" = 0,
 *     "pages" = ""
 *   }
 * )
 */
class RestrictPages extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function preCondition() {
    // Convert path to lowercase. This allows comparison of the same path
    // with different case. Ex: /Page, /page, /PAGE.
    $pages = Unicode::strtolower($this->settings['pages']);
    // Compare the lowercase path alias (if any) and internal path.
    $path = \Drupal::service('path.current')->getPath();
    $path_alias = Unicode::strtolower(\Drupal::service('path.alias_manager')->getAliasByPath($path));
    $page_match = \Drupal::service('path.matcher')->matchPath($path_alias, $pages) || (($path != $path_alias) && \Drupal::service('path.matcher')->matchPath($path, $pages));
    // When $args['mode'] has a value of 0, the tracking condition set
    // is run on all pages except those listed in $pages. When
    // set to 1, it is run only on those pages listed in $pages.
    $page_match = !($this->settings['mode'] xor $page_match);

    return $page_match;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['mode'] = [
      '#type' => 'radios',
      '#title' => 'Run on specific pages',
      '#options' => [
        'Every page except the listed pages',
        'The listed pages only',
      ],
      '#default_value' => $this->settings['mode'],
    ];
    $form['pages'] = [
      '#type' => 'textarea',
      '#title' => 'Pages',
      '#title_display' => 'invisible',
      '#default_value' => $this->settings['pages'],
      '#description' => 'Specify pages by using their paths. Enter one path per line. The \'*\' character is a wildcard. Example paths are /blog for the blog page and /blog/* for every personal blog.',
      '#rows' => 10,
      '#required' => TRUE,
    ];

    return $form;
  }

}
