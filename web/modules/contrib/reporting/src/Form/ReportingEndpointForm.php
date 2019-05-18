<?php

namespace Drupal\reporting\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for Reporting Endpoint add and edit forms.
 *
 * @property \Drupal\Core\Config\Entity\ConfigEntityInterface entity
 */
class ReportingEndpointForm extends EntityForm {

  /**
   * Constructs a ReportingEndpointForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MessengerInterface $messenger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t("Label for the Reporting Endpoint."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    if ($status) {
      $this->messenger()->addMessage(
        $this->t('Saved the %label Endpoint.', [
          '%label' => $this->entity->label(),
        ])
      );
    }
    else {
      $this->messenger()->addError(
        $this->t('The %label Endpoint was not saved.', [
          '%label' => $this->entity->label(),
        ])
      );
    }

    $form_state->setRedirect('entity.reporting_endpoint.collection');
  }

  /**
   * Helper function to check whether an entity already exists.
   */
  public function exists($id) {
    $entity = $this->entityTypeManager->getStorage('reporting_endpoint')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
