<?php

namespace Drupal\cloud\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for copying a server template.
 */
class CloudServerTemplateCopyConfirm extends ContentEntityConfirmFormBase {

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
  public function getQuestion() {
    $entity = $this->entity;
    return $this->t('Are you sure you want to copy %name?', [
      '%name' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Copy');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['copy_server_template_name'] = [
      '#title' => $this->t('New Server template name'),
      '#type' => 'textfield',
      '#description' => $this->t('The new server template name to use.'),
      '#default_value' => $this->t('Copy of @name',
        [
          '@name' => $this->entity->getName(),
        ]),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity = $this->entity;
    $url = $entity->toUrl('canonical');
    $url->setRouteParameter('cloud_context', $entity->getCloudContext());
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $entity = $this->entity;

    // Create the new server template.
    $new_entity = $entity->createDuplicate();
    $new_entity->setName($form_state->getValue('copy_server_template_name'));
    $new_entity->validate();
    $new_entity->save();
    $this->messenger->addMessage(
      $this->t('Server template copied.')
    );
    $form_state->setRedirectUrl($new_entity->toUrl('canonical'));
  }

}
