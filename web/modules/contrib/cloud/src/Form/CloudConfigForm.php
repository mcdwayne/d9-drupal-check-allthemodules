<?php

namespace Drupal\cloud\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Cloud config edit forms.
 *
 * @ingroup cloud
 */
class CloudConfigForm extends ContentEntityForm {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityRepositoryInterface $entity_repository,
                              Messenger $messenger) {
    parent::__construct($entity_repository);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\cloud\Entity\CloudConfig */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 22,
      ];
    }

    $form['cloud_context'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine Name'),
      '#description' => $this->t('A unique machine name for the cloud provider.'),
      '#default_value' => $this->entity->getCloudContext(),
      '#disabled' => !$this->entity->isNew(),
      '#machine_name' => [
        'exists' => [$this->entity, 'checkCloudContext'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(REQUEST_TIME);
      $entity->setRevisionUserId($this->currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label Cloud config.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label Cloud config.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.cloud_config.canonical', ['cloud_config' => $entity->id()]);
  }

}
