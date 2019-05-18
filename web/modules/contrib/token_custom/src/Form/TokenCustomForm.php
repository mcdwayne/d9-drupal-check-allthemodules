<?php

namespace Drupal\token_custom\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\token_custom\Entity\TokenCustomType;

/**
 * Form handler for the custom token edit forms.
 */
class TokenCustomForm extends ContentEntityForm {

  /**
   * The custom token storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $tokenCustomStorage;

  /**
   * The custom token type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $tokenCustomTypeStorage;

  /**
   * The custom token entity.
   *
   * @var \Drupal\token_custom\TokenCustomInterface
   */
  protected $entity;

  /**
   * Constructs a TokenCustomForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $token_custom_storage
   *   The custom token storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $token_custom_type_storage
   *   The custom token type storage.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityStorageInterface $token_custom_storage, EntityStorageInterface $token_custom_type_storage) {
    parent::__construct($entity_manager);
    $this->tokenCustomStorage = $token_custom_storage;
    $this->tokenCustomTypeStorage = $token_custom_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager,
      $entity_manager->getStorage('token_custom'),
      $entity_manager->getStorage('token_custom_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $token = $this->entity;

    $form = parent::form($form, $form_state, $token);

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit custom token %label', [
        '%label' => $token->label(),
      ]);
    }

    $types = TokenCustomType::loadMultiple();
    $options = [];
    foreach ($types as $type) {
      $options[$type->id()] = $type->label();
    }
    $form['type'] = [
      '#type'   => 'select',
      '#title' => 'Token type',
      '#description' => $this->t('The token type determines the availability of the token according to the data in the $data array (ex. a token of type <em>node</em> will need $data[node].'),
      '#options' => $options,
      '#maxlength' => 128,
      '#default_value' => $token->bundle(),
      '#weight' => -1,
    ];

    $form['machine_name']['widget'][0]['value']['#type'] = 'machine_name';
    $form['machine_name']['widget'][0]['value']['#machine_name'] = [
      'exists' => '\Drupal\token_custom\Entity\TokenCustom::load',
    ];

    $account = $this->currentUser();
    $form['machine_name']['#access'] = $account->hasPermission('administer custom tokens');
    $form['machine_name']['#disabled'] = !$token->isNew();
    $form['machine_name']['widget'][0]['value']['#required'] = TRUE;
    $form['machine_name']['#required'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $token = $this->entity;

    $insert = $token->isNew();
    $token->save();
    token_clear_cache();

    $context = [
      '@type' => $token->bundle(),
      '%info' => $token->label(),
    ];
    $logger = $this->logger('token_custom');
    $token_type = $this->tokenCustomTypeStorage->load($token->bundle());
    $t_args = [
      '@type' => $token_type->label(),
      '%info' => $token->label(),
    ];

    if ($insert) {
      $logger->notice('@type: added %info.', $context);
      drupal_set_message($this->t('@type %info has been created.', $t_args));
    }
    else {
      $logger->notice('@type: updated %info.', $context);
      drupal_set_message($this->t('@type %info has been updated.', $t_args));
    }

    if ($token->id()) {
      $form_state->setValue('id', $token->id());
      $form_state->set('id', $token->id());
      $form_state->setRedirectUrl($token->urlInfo('collection'));
    }
    else {
      // In the unlikely case something went wrong on save, the token will be
      // rebuilt and token form redisplayed.
      drupal_set_message($this->t('The token could not be saved.'), 'error');
      $form_state->setRebuild();
    }
  }

}
