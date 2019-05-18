<?php

namespace Drupal\aws;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of aws_profile entities.
 *
 * @see \Drupal\aws\Entity\Profile
 */
class ProfileListBuilder extends ConfigEntityListBuilder implements FormInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new BlockListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, FormBuilderInterface $form_builder) {
    parent::__construct($entity_type, $storage);

    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The block list as a renderable array.
   */
  public function render() {
    return $this->formBuilder->getForm($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aws_profile_admin_display_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['profiles'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Profile'),
        $this->t('Access Key'),
        $this->t('Region'),
        $this->t('Operations'),
      ],
    ];

    foreach ($this->load() as $entity_id => $entity) {
      $form['profiles'][$entity_id]['profile'] = [
        '#plain_text' => $entity->label(),
      ];
      $form['profiles'][$entity_id]['access_key'] = [
        '#plain_text' => $entity->getAccessKey(),
      ];
      $form['profiles'][$entity_id]['region'] = [
        '#plain_text' => $entity->getRegion(),
      ];
      $form['profiles'][$entity_id]['operations'] = $this->buildOperations($entity);
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Profile.'),
      '#button_type' => 'primary',
      '#submit' => ['::redirectToAddProfile'],
    ];

    return $form;
  }

  /**
   * Submit handler.
   *
   * @see ::buildForm()
   */
  public function redirectToAddProfile(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.aws_profile.add_form');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No submit.
  }

}
