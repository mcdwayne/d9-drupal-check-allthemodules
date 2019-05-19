<?php

namespace Drupal\views_responsive_columns\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render results as columns.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_responsive_columns",
 *   title = @Translation("Responsive Columns"),
 *   help = @Translation("Renders results as responsive columns."),
 *   theme = "views_responsive_columns",
 *   display_types = { "normal" }
 * )
 */
class ViewsResponsiveColumns extends StylePluginBase {

  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['group'] = ['default' => ''];
    $options['breakpoints'] = ['default' => []];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    //$form['#attached']['library'][] = 'views_responsive_columns/options_form';
    $form['#attached']['library'] = ['views_responsive_columns/options_form'];

    $breakpount_groups = \Drupal::service('breakpoint.manager')->getGroups();

    $form['breakpoint_group'] = [
      '#type' => 'select',
      '#title' => t('Breakpoint group'),
      '#options' => $breakpount_groups,
      '#empty_option' => t('- Select a break group -'),
      '#default_value' => $this->options['breakpoint_group'],
    ];

    // Loop through breakpoint option groups.
    foreach ($breakpount_groups as $breakpount_group_key => $breakpount_group) {

      // Convert breakpoint ID into a string safe for use as form key.
      $breakpount_group_key_safe = preg_replace('/\W+/','',strtolower(strip_tags($breakpount_group_key)));

      $form['breakpoints'][$breakpount_group_key_safe] = [
        '#type' => 'details',
        '#open' => ($this->options['breakpoint_group'] === $breakpount_group_key),
        '#title' => $breakpount_group,
        '#attributes' => [
          'class' => 'breakpoint-group',
          'data-group' => $breakpount_group_key
        ],
      ];

      // Get breakpoints for group.
      $breakpoints = \Drupal::service('breakpoint.manager')->getBreakpointsByGroup($breakpount_group_key);

      foreach($breakpoints as $breakpoint_key => $breakpoint) {

        // Convert breakpoint ID into a string safe for use as form key.
        $breakpount_key_safe = preg_replace('/\W+/','',strtolower(strip_tags($breakpoints[$breakpoint_key]->getPluginId())));

        $details = [];
        if ($breakpoint->getMediaQuery()) {
          $details[] = 'Query: <em>' . $breakpoint->getMediaQuery() . '</em>'; 
        } else {
          $details[] = 'Query: <em>all</em>'; 
        }
        if (strlen($breakpoint->getWeight())) {
          $details[] = 'Weight: ' . $breakpoint->getWeight();
        }

        $form['breakpoints'][$breakpount_group_key_safe][$breakpount_key_safe] = [
          '#type' => 'fieldset',
          '#title' => $breakpount_key_safe,
          '#description' => implode('<br />', $details),
          '#description_display ' => 'before'
        ];

        $form['breakpoints'][$breakpount_group_key_safe][$breakpount_key_safe]['media_query'] = [
          '#type' => 'hidden',
          '#default_value' => $breakpoint->getMediaQuery(),
        ];

        $form['breakpoints'][$breakpount_group_key_safe][$breakpount_key_safe]['weight'] = [
          '#type' => 'hidden',
          '#default_value' => $breakpoint->getWeight(),
        ];

        $form['breakpoints'][$breakpount_group_key_safe][$breakpount_key_safe]['column_count'] = [
          '#type' => 'number',
          '#title' => t('Column count'),
          '#default_value' => $this->options['breakpoints'][$breakpount_group_key_safe][$breakpount_key_safe]['column_count'],
        ];

      }

    }

  }

}
