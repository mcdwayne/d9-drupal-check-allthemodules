<?php

/**
 * @file
 * FlickityViewsStyle.php
 */

namespace Drupal\flickity_views\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Defines a style plugin that renders a Flickity carousel.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "flickity_views",
 *   title = @Translation("Flickity"),
 *   help = @Translation("Displays view content in a Flickity carousel."),
 *   theme = "flickity_views",
 *   display_types = {"normal"}
 * )
 */

class FlickityViewsStyle extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var boolean
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var boolean
   */
  protected $usesRowClass = TRUE;

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var boolean
   */
  protected $usesGrouping = FALSE;

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var boolean
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['settings'] = array('default' => 'default_group');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['settings'] = array(
      '#title' => $this->t('Settings group'),
      '#type' => 'select',
      '#options' => flickity_settings_list(),
      '#description' => $this->t('The settings group to apply.'),
      '#default_value' => $this->options['settings'] ? $this->options['settings'] : 'default_group'
    );
  }
}
