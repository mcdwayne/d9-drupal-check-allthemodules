<?php

namespace Drupal\block_placeholder\Form;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_placeholder\Entity\BlockPlaceholderInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Define block placeholder order form.
 */
class BlockPlaceholderOrderForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_placeholder.order';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();

    $form['block_order'] = [
      '#type' => 'table',
      '#header' => [
        'label' => $this->t('Label'),
        'bundle' => $this->t('Bundle'),
        'weight' => $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'block-order-entity-weight'
        ]
      ],
      '#empty' => $this->t('No block content has been referenced for this placeholder.'),
    ];

    /** @var BlockContent $entity */
    foreach ($entity->loadReferences() as $entity_id => $entity) {
      $row = &$form['block_order'][$entity_id];
      $weight = $entity->get('block_placeholder_weight')->value;

      $row['#entity'] = $entity;
      $row['#weight'] = $weight;
      $row['#attributes']['class'][] = 'draggable';

      $row['label']['#plain_text'] = $entity->label();
      $row['bundle']['#plain_text'] = $entity->bundle();
      $row['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#delta' => 50,
        '#required' => TRUE,
        '#attributes' => [
          'class' => ['block-order-entity-weight']
        ]
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Order')
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('block_order') as $entity_id => $info) {
      if (!isset($info['weight']) || !isset($form['block_order'][$entity_id]['#entity'])) {
        continue;
      }
      $weight = $info['weight'];

      /** @var BlockContent $block_entity */
      $block_entity = $form['block_order'][$entity_id]['#entity'];

      if (!$block_entity->hasField('block_placeholder_weight')) {
        continue;
      }
      $block_entity
        ->set('block_placeholder_weight', $weight)
        ->save();
    }
    $entity = $this->getEntity();

    drupal_set_message(
      $this->t('@label placeholder order has been updated.', [
        '@label' => $entity->label()
      ])
    );

    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

  /**
   * Get block placeholder entity.
   *
   * @return BlockPlaceholderInterface
   */
  public function getEntity() {
    return $this->getRequest()->attributes->get('block_placeholder');
  }
}
