<?php

namespace Drupal\ptalk\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityField;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;

/**
 * Field handler to display the thread participants.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("ptalk_thread_participants")
 */
class ThreadParticipants extends EntityField {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['thread_number_participants'] = ['default' => 3];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['thread_number_participants'] = [
      '#title' => $this->t('Number of the participants'),
      '#options' => ['1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5],
      '#description' => $this->t("Choose the number of thread participants that will be displayed in participants field."),
      '#type' => 'select',
      '#default_value' => $this->options['thread_number_participants'],
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    $items = parent::getItems($values);

    foreach ($items as &$item) {
      // Prepare participants string.
      $participants_string = $item['rendered']['#context']['value'];
      $thread_participants = ptalk_generate_user_array($participants_string);
      $thread_participants = ptalk_format_participants($thread_participants, TRUE, (int) $this->options['thread_number_participants'], TRUE);
      $thread_participants = [
        '#markup' => $thread_participants,
      ];

      $item['rendered']['#context']['value'] = $thread_participants;
    }

    return $items;
  }

}
