<?php

namespace Drupal\views_zurb_foundation\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_zurb_foundation_block_grid",
 *   title = @Translation("Zurb Foundation Block Grid"),
 *   help = @Translation("Displays rows in a Zurb Foundation Block Grid"),
 *   theme = "views_zurb_foundation_block_grid",
 *   theme_file = "../views_zurb_foundation.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewsZurbFoundationBlockGrid extends StylePluginBase {
  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::usesRowPlugin.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::usesRowClass.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Definition.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['small'] = ['default' => 'small-up-1'];
    $options['medium'] = ['default' => 'medium-up-2'];
    $options['large'] = ['default' => 'large-up-3'];
    $options['automatic_width'] = ['default' => TRUE];
    $options['col_class_custom'] = ['default' => ''];
    $options['col_class_default'] = ['default' => TRUE];
    $options['row_class_custom'] = ['default' => ''];
    $options['row_class_default'] = ['default' => TRUE];
    $options['default'] = ['default' => ''];
    $options['info'] = ['default' => []];
    $options['override'] = ['default' => TRUE];
    $options['sticky'] = ['default' => FALSE];
    $options['order'] = ['default' => 'asc'];
    $options['caption'] = ['default' => ''];
    $options['summary'] = ['default' => ''];
    $options['description'] = ['default' => ''];
    $options['apply_equalizer'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    foreach (['small', 'medium', 'large'] as $size) {
      $form["${size}"] = [
        '#type' => 'select',
        '#title' => $this->t("Number of columns per breakpoint (@size)", ['@size' => $size]),
        '#required' => TRUE,
        '#default_value' => isset($this->options["${size}"]) ? $this->options["${size}"] : NULL,
        '#options' => [
          "${size}-up-1" => 1,
          "${size}-up-2" => 2,
          "${size}-up-3" => 3,
          "${size}-up-4" => 4,
          "${size}-up-5" => 5,
          "${size}-up-6" => 6,
          "${size}-up-7" => 7,
          "${size}-up-8" => 8,
        ],
      ];
    }

    $form['apply_equalizer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Apply equalizer on items.'),
      '#default_value' => isset($this->options["apply_equalizer"]) ? $this->options["apply_equalizer"] : NULL,
      '#description' => $this->t('Check if you want apply equalizer on items. See @link', ['@link' => 'https://foundation.zurb.com/sites/docs/equalizer.html']),
    ];
  }

}
