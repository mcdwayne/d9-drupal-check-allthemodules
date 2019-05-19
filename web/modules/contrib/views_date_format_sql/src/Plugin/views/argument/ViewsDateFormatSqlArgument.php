<?php

namespace Drupal\views_date_format_sql\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\NumericArgument;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;

/**
 * An argument that filters entity timestamp field data.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("views_date_format_sql_argument")
 */
class ViewsDateFormatSqlArgument extends NumericArgument {

  private $format;
  private $format_string;


  public function getFormula() {
    $formula = $this->query->getDateFormat("FROM_UNIXTIME($this->tableAlias.$this->realField)", $this->options['format_string']);
    return $formula;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['format_date_sql'] = array('default' => FALSE);
    $options['format_string'] = array('default' => '');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    $form['format_date_sql'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use SQL to format date'),
      '#description' => $this->t('Use the SQL databse to format the date. This enables date values to be used in grouping aggregation.'),
      '#default_value' => $this->options['format_date_sql'],
    );
    $form['format_string'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Date Format'),
      '#description' => $this->t('Use the SQL database to format the date. This enables date values to be used in grouping aggregation.'),
      '#default_value' => $this->options['format_string'],
    );

    parent::buildOptionsForm($form, $form_state);
  }

  // This should to behave just like a normal timestamp field argument handler
  // if sql date formatting isn't chosen
  public function query($group_by = FALSE) {
    if(!$this->options['format_date_sql']){
      return parent::query();
    }

    $this->ensureMyTable();
    // Now that our table is secure, get our formula.
    $placeholder = $this->placeholder();
    $formula = $this->getFormula() . ' = ' . $placeholder;
    $placeholders = [
      $placeholder => $this->argument,
    ];
    $this->query->addWhere(0, $formula, $placeholders, 'formula');
  }
}
