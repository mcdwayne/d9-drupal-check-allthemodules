<?php

namespace Drupal\measuremail\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\measuremail\ConfigurableMeasuremailElementInterface;
use Drupal\measuremail\Plugin\MeasuremailElementsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for measuremail add and edit forms.
 */
abstract class MeasuremailFormBase extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\measuremail\MeasuremailInterface
   */
  protected $entity;

  /**
   * The measuremail entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $measuremailStorage;

  /**
   * Constructs a base class for measuremail elements add and edit forms.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $measuremail_storage
   *   The measuremail entity storage.
   */
  public function __construct(EntityStorageInterface $measuremail_storage) {
    $this->measuremailStorage = $measuremail_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('measuremail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\measuremail\MeasuremailInterface $measuremail */
    $measuremail = $this->entity;

    $settings = $measuremail->getSettings();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Measuremail form title'),
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];
    $form['name'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this->measuremailStorage, 'load'],
      ],
      '#default_value' => $this->entity->id(),
      '#required' => TRUE,
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->toUrl('edit-form'));
  }

}
