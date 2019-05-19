<?php

namespace Drupal\views_timestamp_aggregate\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("date_unixtime")
 */
class DateUnixtime extends FieldPluginBase {

  // @todo: Aggregation settings not showing

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
    $this->ensureMyTable();
    // Add the field.
    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
    $pattern = $this->options['pattern'];
    // Get results for "created" field.
    // @todo: check if the output is not affected by server locale
    //  maybe add an option to converted to a format in render() but here only return timestamp with a give granularity (this may be a more expensive query)
    if (!empty($pattern)) {
      // Pattern example: '%d, %m, %Y'.
      $this->field_alias = $this->query->addField('', "FROM_UNIXTIME(" . $this->tableAlias . "." . $this->realField . ", '" . $pattern . "')", $this->tableAlias . '_' . $this->field, $params);
    }
    else {
      $this->field_alias = $this->query->addField('', "FROM_UNIXTIME(" . $this->tableAlias . "." . $this->realField . ")", $this->tableAlias . '_' . $this->field, $params);
    }

    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['pattern'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['pattern'] = [

      '#type' => 'textfield',
      '#title' => $this->t('Date pattern'),
      '#default_value' => $this->options['pattern'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // @todo: validate pattern
    $options = &$form_state->getValue('options');
    $options['pattern'] = trim($options['pattern']);
  }

}
