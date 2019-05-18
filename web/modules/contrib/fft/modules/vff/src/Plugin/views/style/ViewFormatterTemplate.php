<?php

namespace Drupal\vff\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_formatter_template",
 *   title = @Translation("View Formatter Template"),
 *   help = @Translation("Displays rows in a Bootstrap Grid layout"),
 *   theme = "views_formatter_template",
 *   theme_file = "../vff.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewFormatterTemplate extends StylePluginBase {
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
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * Definition.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['template'] = ['default' => ''];
    $options['render_type'] = ['default' => 'raw'];

    $options['vff_tree_field'] = ['default' => ''];
    $options['vff_tree_parent_field'] = ['default' => ''];
    $options['vff_clean_template'] = ['default' => ''];
    $options['show_empty'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $fft_templates = fft_get_templates('views');

    $form['template'] = [
      '#title' => $this->t('Template'),
      '#type' => 'select',
      '#options' => $fft_templates['templates'],
      '#default_value' => $this->options['template'],
      '#attributes' => ['class' => ['fft-template']],
    ];

    $form['render_type'] = [
      '#title' => $this->t('Field Render Format'),
      '#type' => 'select',
      '#options' => [
        'raw' => 'Raw',
        'styled' => 'Styled',
      ],
      '#default_value' => $this->options['render_type'],
      '#description' => $this->t('Select field render format.'),
    ];

    $field_labels = $this->displayHandler->getFieldLabels(TRUE);
    $form['fields_available'] = [
      '#type' => 'item',
      '#title' => $this->t('Fields available for Twig template'),
      '#markup' => json_encode($field_labels),
      '#states' => [
        'visible' => [
          ':input[name="style_options[render_type]"]' => ['value' => 'raw'],
        ],
      ],
    ];

    $form['vff_clean_template'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Clean Template'),
      '#description' => $this->t('Use this checkbox to use clean template. The output will remove wrapper div with view-id,view-content...'),
      '#required' => FALSE,
      '#return_value' => 1,
      '#default_value' => $this->options['vff_clean_template'],
    ];

    $options = ['' => $this->t('- None -')];
    $field_labels = $this->displayHandler->getFieldLabels(TRUE);
    $options += $field_labels;
    if (count($options) > 1) {
      $form['vff_tree_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Tree id field'),
        '#options' => $options,
        '#default_value' => $this->options['vff_tree_field'],
        '#description' => $this->t('Select field id used as parent to build array tree. Useful for build taxonomy tree.'),
        '#group' => 'vff_tree',
      ];

      $form['vff_tree_parent_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Tree parent id field'),
        '#options' => $options,
        '#default_value' => $this->options['vff_tree_parent_field'],
        '#description' => $this->t('Select field parent id used as parent to build array tree. Useful for build taxonomy tree.'),
        '#group' => 'vff_tree',
      ];
    }

    $form['show_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show template when empty'),
      '#default_value' => $this->options['show_empty'],
      '#description' => $this->t('Per default the template is hidden for an empty view. With this option it is possible to show an empty template.'),
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Get rendered fields.
   *
   * @return array|null
   *   Return rendered field.
   */
  public function getRenderedFields() {
    return $this->rendered_fields;
  }

  /** @inheritdoc */
  public function evenEmpty() {
    return !empty($this->options['show_empty']) || parent::evenEmpty() ;
  }

}
