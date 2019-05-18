<?php

namespace Drupal\civicrm_member_roles\Form;

use Drupal\civicrm_member_roles\Batch\Sync;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ManualSyncForm.
 */
class ManualSyncForm extends FormBase {

  /**
   * CiviCRM member roles sync batch.
   *
   * @var \Drupal\civicrm_member_roles\Batch\Sync
   */
  protected $sync;

  /**
   * CivicrmMemberRoleRuleForm constructor.
   *
   * @param \Drupal\civicrm_member_roles\Batch\Sync $sync
   *   CiviCRM member roles service.
   */
  public function __construct(Sync $sync) {
    $this->sync = $sync;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('civicrm_member_roles.batch.sync'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civicrm_member_roles_manual_sync';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['manual_sync'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Manual Synchronization:'),
    ];

    $form['manual_sync']['manual_sync_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Synchronize CiviMember Membership Types to Drupal Roles now'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = $this->sync->getBatch();
    batch_set($batch);
  }

}
