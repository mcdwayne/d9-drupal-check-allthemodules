<?php

namespace Drupal\activity\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create activities form.
 */
class CreateActivityForm extends MultiStepFormBase {

  /**
   * Constructs an CreateActivityForm.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Session\SessionManagerInterface $sessionManager
   *   The session manager.
   * @param \Drupal\user\PrivateTempStoreFactory $tempStoreFactory
   *   The temp store factory.
   */
  public function __construct(AccountInterface $currentUser, SessionManagerInterface $sessionManager, PrivateTempStoreFactory $tempStoreFactory) {
    parent::__construct($currentUser, $sessionManager, $tempStoreFactory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('session_manager'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'create_activities_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Events - when to trigger actions.
    $options = [
      'comment_insert' => t('Save new comment'),
      'comment_update' => t('Update comment'),
      'comment_delete' => t('Delete comment'),
      'node_insert' => t('Save new node'),
      'node_update' => t('Update node'),
      'node_delete' => t('Delete node'),
      'user_insert' => t('Save new user'),
      'user_update' => t('Update user'),
      'user_delete' => t('Delete user'),
    ];
    $form = parent::buildForm($form, $form_state);

    // Event name.
    $form['activity_label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => '',
      '#required' => TRUE,
      '#size' => 30,
      '#attributes' => [
        'class' => [
          'activity_label',
        ],
      ],
    ];

    $form['activity_actions'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose your hook'),
      '#required' => TRUE,
      '#default_value' => 1,
      '#options' => $options,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Create'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $hook = $form_state->getValue('activity_actions');
    $label = $form_state->getValue('activity_label');
    $this->store->set('hook', $hook);
    $this->store->set('label', $label);
    // Move to next form to configure the event.
    $url = Url::fromUri('internal:/admin/activity/configure/new');
    $form_state->setRedirectUrl($url);
  }

}
