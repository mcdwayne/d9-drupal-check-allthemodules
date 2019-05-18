<?php

namespace Drupal\scheduled_message\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class EntityTypesForm.
 *
 * @package Drupal\scheduled_message\Form
 */
class EntityTypesForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
      EntityTypeManager $entity_type_manager
    ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Class factory.
   *
   * @inheritdoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
            $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'scheduled_message.entitytypes',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scheduled_message_entity_types_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('scheduled_message.entitytypes');
    $form['entity_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Entity Type'),
      '#default_value' => $config->get('entity_type'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO: Validation of plugin parameters.
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('scheduled_message.entitytypes')
      ->set('entity_type', $form_state->getValue('entity_type'))
      ->save();
  }

}
