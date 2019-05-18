<?php

namespace Drupal\past_db\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\past_db\Entity\PastEvent;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the event's argument key value.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("past_db_event_argument_data")
 */
class EventArgumentData extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $definition = parent::defineOptions();
    $definition['argument_name'] = ['default' => NULL];
    $definition['data_key'] = ['default' => NULL];
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['argument_name'] = [
      '#type' => 'textfield',
      '#title' => t('Argument name'),
      '#default_value' => $this->options['argument_name'],
      '#required' => TRUE,
    ];

    $form['data_key'] = [
      '#type' => 'textfield',
      '#title' => t('Data key'),
      '#default_value' => $this->options['data_key'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $this->aliases['event_id'] = 'event_id';
    $event_id = $this->getValue($values, 'event_id');

    if (empty($this->options['argument_name'])) {
      return "";
    }
    if (empty($event_id)) {
      return "";
    }

    /** @var PastEvent $event */
    $event = $values->_entity;
    $argument = $event->getArgument($this->options['argument_name']);
    if (empty($argument)) {
      return "";
    }

    $data = $argument->getData();
    if (!empty($this->options['data_key'])) {
      $data = isset($data[$this->options['data_key']]) ? $data[$this->options['data_key']] : NULL;
    }
    return is_array($data) ? print_r($data, TRUE) : $data;
  }

}
