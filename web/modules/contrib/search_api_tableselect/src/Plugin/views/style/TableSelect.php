<?php

namespace Drupal\search_api_tableselect\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\Table;

/**
 * Style plugin to render each item as a row in a table.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "tableselect",
 *   title = @Translation("TableSelect"),
 *   help = @Translation("Displays rows in a table with ability to check something."),
 *   theme = "views_view_tableselect",
 *   display_types = {"normal"}
 * )
 */
class TableSelect extends Table {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['form_class'] = ['default' => 'Drupal\search_api_tableselect\Form\TableSelectExampleForm'];

    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    unset($form['grouping']);
    $form['form_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form class'),
      '#default_value' => $this->options['form_class'],
    ];
  }

  /**
   * Render the display in this style.
   */
  public function render() {
    $rendered = parent::render();

    $form = \Drupal::formBuilder()->getForm($this->options['form_class'], $rendered[0]);

    return $form;
  }

}
