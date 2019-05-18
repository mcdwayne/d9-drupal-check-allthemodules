<?php

namespace Drupal\cloud\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Cloud Server Template edit forms.
 *
 * @ingroup cloud_server_template
 */
class CloudServerTemplateForm extends ContentEntityForm {

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
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {
    /* @var $entity \Drupal\cloud\Entity\CloudServerTemplate */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 99,
      ];
    }

    $entity = $this->entity;

    // Setup the cloud_context based on value passed in the path.
    $form['cloud_context']['#disabled'] = TRUE;
    if ($entity->isNew()) {
      $form['cloud_context']['widget'][0]['value']['#default_value'] = $cloud_context;
    }
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
        $this->messenger->addMessage($this->t('Created the %label Cloud Server Template.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label Cloud Server Template.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.cloud_server_template.canonical', ['cloud_server_template' => $entity->id(), 'cloud_context' => $entity->getCloudContext()]);
  }

}
