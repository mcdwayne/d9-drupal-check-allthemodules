<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Form builder to create a new wallet for a given ContentEntity.
 */
class WalletAddForm extends ContentEntityForm {

  private $holder_entity_type;
  private $holder_entity_id;

  /**
   * {@inheritdoc}
   */
  public function __construct($entity_repository, $entity_type_bundle_info, $time, RouteMatchInterface $route_match) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $params = $route_match->getParameters();
    $this->holder_entity_type = $params->getIterator()->key();
    $this->holder_id = $params->getIterator()->current()->id();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wallet_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $holder = $this->entity->getHolder();
    $form['#title'] = $this->t("New wallet for @entity_type '%title'",
      [
        '@entity_type' => $holder->getEntityType()->getLabel(),
        '%title' => $holder->label(),
      ]
      );
    $form['wid'] = [
      '#type' => 'value',
      '#value' => NULL,
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name or purpose of wallet'),
      '#default_value' => '',
      '#required' => '',
      '#access' => !mcapi_one_wallet_mode($this->entity->getHolder())
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Create');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Just check that the name isn't the same.
    // This unique check would be better in a walletStorageController.
    $values = $form_state->getValues();
    $query = $this->entityTypeManager
      ->getStorage('mcapi_wallet')
      ->getQuery()
      ->condition('name', $values['name']);

    if (!\Drupal::config('mcapi.settings')->get('unique_names')) {
      $query->condition('holder_entity_id', $this->holder_entity_id);
      $query->condition('holder_entity_type', $this->holder_entity_type);
    }
    if ($query->execute()) {
      $form_state->setErrorByName(
        'name',
        t("The wallet name '%name' is already used.", ['%name' => $values['name']])
      );
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $form_state->setRedirectUrl($this->entity->toUrl('edit-form'));
  }

}
