<?php

namespace Drupal\mail_entity_queue\Form;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface;

/**
 * Builds the form to edit a mail queue item.
 */
class MailEntityQueueItemForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface $item */
    $item = $this->entity;

    $form['#title'] = $this->t('Mail queue item %label', ['%label' => $item->label()]);

    $disabled = ($item->getStatus() === MailEntityQueueItemInterface::SENT);

    $form['queue'] = [
      '#type' => 'textfield',
      '#title' => 'Queue',
      '#disabled' => TRUE,
      '#default_value' => $item->queue()->id(),
    ];
    $form['created'] = [
      '#type' => 'textfield',
      '#title' => 'Created time',
      '#disabled' => TRUE,
      '#default_value' => $item->getCreatedTime(),
    ];
    $form['changed'] = [
      '#type' => 'textfield',
      '#title' => 'Last updated time',
      '#disabled' => TRUE,
      '#default_value' => $item->getChangedTime(),
    ];
    $form['attempts'] = [
      '#type' => 'textfield',
      '#title' => '# of attempts to send',
      '#disabled' => TRUE,
      '#default_value' => $item->getAttempts(),
    ];
    $form['mail'] = [
      '#type' => 'textfield',
      '#title' => 'Mail',
      '#disabled' => $disabled,
      '#default_value' => $item->getMail(),
    ];
    $form['status'] = [
      '#type' => 'textfield',
      '#title' => 'Status code',
      '#disabled' => TRUE,
      '#default_value' => $item->getStatus(),
    ];
    $form['entity_type'] = [
      '#type' => 'textfield',
      '#title' => 'Related entity type',
      '#disabled' => TRUE,
      '#default_value' => $item->getSourceEntityType(),
    ];
    $form['entity_id'] = [
      '#type' => 'textfield',
      '#title' => 'Related entity ID',
      '#disabled' => TRUE,
      '#default_value' => $item->getSourceEntityId(),
    ];

    // Avoid ugly errors from YAML trying to decode objects.
    // If the content is just too complex and we could mess with HTML, let's
    // just disable the element.
    $orig_data = $item->getData();
    array_walk_recursive(
      $orig_data,
      function (&$value) use (&$disabled) {
        if (is_object($value)) {
          $disabled = true;
          $value = (string) $value;
        }
      }
    );

    $form['data'] = [
      '#type' => 'textarea',
      '#description' => $disabled ? $this->t('This item is disabled from editing because the email has already be sent or it contains HTML and it is too complex to be edited directly') : '',
      '#title' => $this->t('Data'),
      '#disabled' => $disabled,
      '#default_value' => Yaml::encode($orig_data),
      '#attributes' => ['data-yaml-editor' => 'true'],
    ];
    if (!$disabled && !\Drupal::moduleHandler()->moduleExists('yaml_editor')) {
      $message = $this->t('It is recommended to install the <a href="@yaml-editor">YAML Editor</a> module for easier editing.', [
        '@yaml-editor' => 'https://www.drupal.org/project/yaml_editor',
      ]);

      $this->messenger()->addStatus($message);
      $form['data']['#rows'] = count(explode("\n", $form['data']['#default_value'])) + 3;
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $item = $this->entity;
    if (!$form['data']['#disabled'] && !is_array($form_state->getValue('data'))) {
      $form_state->setValue('data', Yaml::decode($form_state->getValue('data')));
    }
    else {
      $form_state->setValue('data', $item->getData());
    }

    // If the item was discarded, requeue the element after submit.
    if ((integer) $form_state->getValue('status') === MailEntityQueueItemInterface::DISCARDED) {
      $form_state->setValue('status', MailEntityQueueItemInterface::PENDING);
      $this->messenger()->addStatus($this->t('The item %label has been queued.', ['%label' => $this->entity->label()]));
    }

    return parent::buildEntity($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    /** @var \Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface $item */
    $item = $this->entity;

    if ((integer) $item->getStatus() === MailEntityQueueItemInterface::SENT) {
      $actions['submit']['#disabled'] = TRUE;
    }

    return $actions;
  }

}
