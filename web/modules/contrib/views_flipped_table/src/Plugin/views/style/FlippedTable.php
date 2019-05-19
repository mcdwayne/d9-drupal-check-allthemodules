<?php

namespace Drupal\views_flipped_table\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\Table;

/**
 * Style plugin to render each item as a column in a table.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "flipped_table",
 *   title = @Translation("Flipped Table"),
 *   help = @Translation("Displays a table with rows and columns flipped."),
 *   theme = "views_view_flipped_table",
 *   display_types = {"normal"}
 * )
 */
class FlippedTable extends Table {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['flipped_table_header_first_field'] = array('default' => TRUE);

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // For now, remove access to row class form elements since I'm not sure how
    // they should work once flipped. Replacements would be tricky.
    $form['row_class']['#access'] = FALSE;
    $form['default_row_class']['#access'] = FALSE;

    $form['flipped_table_header_first_field'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show the first field as the table header'),
      '#default_value' => $this->options['flipped_table_header_first_field'],
      '#description' => $this->t("Outputs the flipped table's row for the first field inside a table header element."),
    );
  }

}