<?php

namespace Drupal\views_field_percentage\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("percentage_views_field")
 */
class PercentageViewsField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['range_style_type'] = ['default' => 'html'];
    $options['range_min'] = ['default' => 0];
    $options['range_current'] = ['default' => ''];
    $options['range_max'] = ['default' => 100];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['range_style'] = [
      '#type' => 'details',
      '#title' => $this->t('Percentage Style'),
    ];

    $form['range_style_type'] = [
      '#required' => TRUE,
      '#type' => 'radios',
      '#title' => $this->t('Style'),
      '#fieldset' => 'range_style',
      '#default_value' => $this->options['range_style_type'],
      '#options' => [
        'html' => $this->t('HTML5 <progress></progress>'),
        'circle' => $this->t('Circle'),
        'circle_with_percentage' => $this->t('Circle with percentage'),
      ],
      '#description' => $this->t('Chose percentage style.'),
    ];

    $form['range'] = [
      '#type' => 'details',
      '#title' => $this->t('Range'),
    ];

    $form['range_min'] = [
      '#type' => 'number',
      '#title' => $this->t('Min'),
      '#fieldset' => 'range',
      '#default_value' => $this->options['range_min'],
      '#description' => $this->t('Insert min value of range percentage.'),
    ];

    $form['range_max'] = [
      '#type' => 'number',
      '#title' => $this->t('Max'),
      '#fieldset' => 'range',
      '#default_value' => $this->options['range_max'],
      '#description' => $this->t('Insert max value of range percentage.'),
    ];

    // Setup the tokens for fields.
    $previous = $this->getPreviousFieldLabels();
    $optgroup_arguments = (string) $this->t('Arguments');
    $optgroup_fields = (string) $this->t('Fields');
    foreach ($previous as $id => $label) {
      $options[$optgroup_fields]["{{ $id }}"] = substr(strrchr($label, ":"), 2);
    }

    // Add the field to the list of options.
    $options[$optgroup_fields]["{{ {$this->options['id']} }}"] = substr(strrchr($this->adminLabel(), ":"), 2);

    foreach ($this->view->display_handler->getHandlers('argument') as $arg => $handler) {
      $options[$optgroup_arguments]["{{ arguments.$arg }}"] = $this->t('@argument title', ['@argument' => $handler->adminLabel()]);
      $options[$optgroup_arguments]["{{ raw_arguments.$arg }}"] = $this->t('@argument input', ['@argument' => $handler->adminLabel()]);
    }
    $this->documentSelfTokens($options[$optgroup_fields]);

    // Tokens and help text.
    $output = [];
    $output[] = [
      '#markup' => '<p>' . $this->t('You must add some additional fields to this display before using this field. These fields may be marked as <em>Exclude from display</em> if you prefer. Note that due to rendering order, you cannot use fields that come after this field; if you need a field not listed here, rearrange your fields.') . '</p>',
    ];
    // We have some options, so make a list.
    if (!empty($options)) {
      $output[] = [
        '#markup' => '<p>' . $this->t("The following replacement tokens are available for this field. Note that due to rendering order, you cannot use fields that come after this field; if you need a field not listed here, rearrange your fields.") . '</p>',
      ];
      foreach (array_keys($options) as $type) {
        if (!empty($options[$type])) {
          $items = [];
          foreach ($options[$type] as $key => $value) {
            $items[] = $key . ' == ' . $value;
          }
          $item_list = [
            '#theme' => 'item_list',
            '#items' => $items,
          ];
          $output[] = $item_list;
        }
      }
    }

    $form['range_current'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Current Value'),
      '#fieldset' => 'range',
      '#default_value' => $this->options['range_current'],
      '#description' => \Drupal::service('renderer')->render($output),
    ];
  }

  /**
   * Render markup by options and values.
   *
   * @param Drupal\ResultRow $values
   *   The Values of row result.
   *
   * @return \Drupal\Component\Render\MarkupInterface|\Drupal\views\Render\ViewsRenderPipelineMarkup|string
   *   Final Markup.
   */
  public function render(ResultRow $values) {

    // Define range Min.
    $min = $this->options['range_min'];

    // Define Range Max.
    $max = $this->options['range_max'];

    // Get current value by token.
    $current = $this->tokenizeValue($this->options['range_current']);

    // Calculate percentage.
    $x = ($max - $min) / $current;
    $percent = (1 / $x) * 100;

    // Make Renderable Array with custom template twig.
    $renderable = [
      '#theme' => 'percentage',
      '#range_min' => $this->options['range_min'],
      '#range_max' => $this->options['range_max'],
      '#range_current' => $this->options['range_current'],
      '#percent' => $percent,
      '#difference' => (100 - (int) $percent),
      '#range_style_type' => $this->options['range_style_type'],
      '#cache' => [
        'max-age' => 0,
      ],
      '#attached' => [
        'library' => [
          'views_field_percentage/style',
        ],
      ],
    ];

    // Return rendered html.
    $markup = \Drupal::service('renderer')->render($renderable);
    return $markup;
  }

}
