<?php

namespace Drupal\views_secondary_row\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\Table;

/**
 * Style plugin to render each item as a row in a table.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_secondary_row_table",
 *   title = @Translation("Table with fields in secondary row"),
 *   help = @Translation("Displays rows in a table, using a secondary row."),
 *   theme = "views_secondary_row_view_table",
 *   display_types = {"normal"}
 * )
 */
class TableSecondaryRow extends Table {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['break2'] = array('default' => '');
    $options['separator2'] = array('default' => '');
    $options['colspan2'] = array('default' => '');

    return $options;
  }

  /**
   * Render the given style.
   * The options form will use template_preprocess_views_secondary_row_style_plugin_table.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $handlers = $this->displayHandler->getHandlers('field');
    if (empty($handlers)) {
      return;
    }

    // Note: views UI registers this theme handler on our behalf. Your module
    // will have to register your theme handlers if you do stuff like this.
    // $form['#theme'] = 'views_ui_style_plugin_table';
    $form['#theme'] = 'views_secondary_row_style_plugin_table';

    $columns = $this->sanitizeColumns($this->options['columns']);

    // Create an array of allowed columns from the data we know:
    $field_names = array('' => t('None')) + $this->displayHandler->getFieldLabels();

    foreach ($columns as $field => $column) {
      $column_selector = ':input[name="style_options[columns][' . $field . ']"]';
      $column_selector2 = ':input[name="style_options[break2][' . $field . ']"]';

      $form['info'][$field]['break2'] = array(
        '#title' => $this->t('Break for @field', array('@field' => $field)),
        '#title_display' => 'invisible',
        '#type' => 'select',
        '#default_value' => isset($this->options['info'][$field]['break2']) ? $this->options['info'][$field]['break2'] : '',
        '#options' => $field_names,
        '#states' => array(
          'visible' => array(
            $column_selector => array('value' => $field),
          ),
        ),
      );
      $form['info'][$field]['separator2'] = array(
        '#title' => $this->t('Separator for @field', array('@field' => $field)),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
        '#size' => 10,
        '#default_value' => isset($this->options['info'][$field]['separator2']) ? $this->options['info'][$field]['separator2'] : '',
        '#states' => array(
          'visible' => array(
            $column_selector => array('value' => $field),
          ),
          'invisible' => array(
            $column_selector2 => array('value' => ''),
          ),
        ),
      );
      $form['info'][$field]['colspan2'] = array(
        '#title' => $this->t('Colspan for @field', array('@field' => $field)),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
        '#size' => 5,
        '#default_value' => isset($this->options['info'][$field]['colspan2']) ? $this->options['info'][$field]['colspan2'] : '',
        '#states' => array(
          'visible' => array(
            $column_selector => array('value' => $field),
          ),
          'invisible' => array(
            $column_selector2 => array('value' => ''),
          ),
        ),
      );
    }
  }

}
