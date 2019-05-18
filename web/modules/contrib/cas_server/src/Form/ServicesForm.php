<?php

/**
 * @file
 * Contains \Drupal\cas_server\Form\ServicesForm.
 */

namespace Drupal\cas_server\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityFieldManager;

class ServicesForm extends EntityForm {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager.
   */
  protected $entityFieldManager;

  /**
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(QueryFactory $entity_query, EntityFieldManager $entity_field_manager) {
    $this->entityQuery = $entity_query;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.query'), $container->get('entity_field.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $service = $this->entity;

    // Form API stuff here
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $service->getLabel(),
      '#description' => $this->t('Label for the Service definition'),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $service->getId(),
      '#machine_name' => array( 
        'exists' => array($this, 'exist'),
      ),
      '#disabled' => !$service->isNew(),
    );

    $form['service'] = array(
      '#type' => 'textfield',
      '#default_value' => $service->getService(),
      '#title' => $this->t('Service URL Pattern'),
      '#size' => 60,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#description' => $this->t('Pattern to match service urls with. * is a wildcard.'),
    );

    $form['sso'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Participate in single sign on?'),
      '#default_value' => $service->getSso(),
      '#options' => array(0 => $this->t('No'), 1 => $this->t('Yes')),
    );

    $fields = array_keys($this->entityFieldManager->getFieldDefinitions('user', 'user'));
    $options = array_combine($fields, $fields);
    $form['attributes'] = array(
      '#type' => 'select',
      '#title' => 'Released attributes',
      '#description' => 'Fields to release as Cas attributes.',
      '#multiple' => TRUE,
      '#default_value' => $service->getAttributes(),
      '#options' => $options,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $service = $this->entity;
    $status = $service->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label Service.', array(
        '%label' => $service->getLabel(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label Service was not saved.', array(
        '%label' => $service->getLabel(),
      )));
    }

    $form_state->setRedirect('entity.cas_server_service.collection');
  }

  public function exist($id) {
    $entity = $this->entityQuery->get('cas_server_service')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
