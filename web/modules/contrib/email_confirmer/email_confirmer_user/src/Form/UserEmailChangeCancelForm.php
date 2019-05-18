<?php

namespace Drupal\email_confirmer_user\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\user\UserDataInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\email_confirmer\EmailConfirmerManagerInterface;

/**
 * User pending email change cancellation form.
 */
class UserEmailChangeCancelForm extends ContentEntityConfirmFormBase {

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The email confirmer.
   *
   * @var \Drupal\email_confirmer\EmailConfirmerManagerInterface
   */
  protected $emailConfirmer;

  /**
   * Constructs a UserEmailChangeCancelForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\email_confirmer\EmailConfirmerManagerInterface $email_confirmer
   *   The email confirmer.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, UserDataInterface $user_data, EmailConfirmerManagerInterface $email_confirmer) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->userData = $user_data;
    $this->emailConfirmer = $email_confirmer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('user.data'),
      $container->get('email_confirmer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return ($this->currentUser()->id() == $this->entity->id()
      ? $this->t('Your current email address %email will be preserved.', ['%email' => $this->entity->getEmail()])
      : $this->t("The current user's email address %email will be preserved.", ['%email' => $this->entity->getEmail()])) . ' ' . parent::getDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName() {
    return $this->getFormId();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_confirmer_user_email_change_cancel';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $new_email = $this->userData->get('email_confirmer_user', $this->entity->id(), 'email_change_new_address');
    return $this->currentUser()->id() == $this->entity->id()
      ? $this->t('Are you sure you want to cancel the pending change of your email address to %email?', ['%email' => $new_email])
      : $this->t('Are you sure you want to cancel the pending change of the email address of user %user to %email?', ['%email' => $new_email, '%user' => $this->entity->getDisplayName()]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $new_email = $this->userData->get('email_confirmer_user', $this->entity->id(), 'email_change_new_address');
    $this->userData->delete('email_confirmer_user', $this->entity->id(), 'email_change_new_address');

    // The confirmation cancel URL.
    $cancel_url = NULL;

    // Cancel any pending address confirmation for the requested new email.
    /** @var \Drupal\email_confirmer\EmailConfirmationInterface $confirmation */
    foreach ($this->emailConfirmer->getConfirmations($new_email, 'pending', 0, 'email_confirmer_user') as $confirmation) {
      $confirmation->cancel();
      $confirmation->save();
      $cancel_url = $confirmation->getResponseUrl('cancel');
    }

    // Go to the (last) confirmation cancel URL, the user edit form otherwise.
    $form_state->setRedirectUrl($cancel_url ?: $this->entity->toUrl('edit-form'));
  }

}
