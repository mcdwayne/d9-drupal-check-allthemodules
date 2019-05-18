<?php

namespace Drupal\invite\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\invite\InviteConstants;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\invite\InviteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;

/**
 * Class InviteWithdrawForm.
 *
 * @package Drupal\invite\Form
 */
class InviteWithdrawForm extends FormBase {

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $inviteStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a InviteAcceptController object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $invite_storage
   *   Invite storage.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityStorageInterface $invite_storage, EntityTypeManager $entityTypeManager, MessengerInterface $messenger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->inviteStorage = $invite_storage;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('invite'),
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'invite_withdraw_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, InviteInterface $invite = NULL) {
    /** @var \Drupal\invite\InviteInterface $invite */
    $form['#title'] = $this->t('Withdraw invite for @field_invite_email_address', ['@field_invite_email_address' => $invite->field_invite_email_address->value]);
    $this->inviteStorage = $invite;
    $form['actions']['#type'] = 'actions';
    $form['withdraw_invite'] = [
      '#type' => 'submit',
      '#title' => $this->t('Withdraw Invite'),
      '#description' => $this->t('Withdraw current invite.'),
      '#button_type' => 'primary',
      '#value' => $this->t('Withdraw Invite'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\invite\InviteInterface $invite */
    $invite = $this->inviteStorage;
    $invite->setStatus(InviteConstants::INVITE_WITHDRAWN);
    $invite->save();
    $this->messenger->addStatus($this->t('The invitation has been withdrawn'));
    $url = Url::fromRoute('invite.invite_list');
    $form_state->setRedirectUrl($url);
  }

}
