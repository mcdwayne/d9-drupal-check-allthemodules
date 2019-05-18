<?php

namespace Drupal\private_messages\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\private_messages\Plugin\Action\UserBlockMessagingAction;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;

/**
 * Class BlockUserForm.
 *
 * @package Drupal\private_messages\Form
 */
class BlockUserForm extends FormBase {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  public function __construct(
    AccountProxy $current_user
  ) {
    $this->currentUser = $current_user;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'form-compact pb-1';

    $form['user'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => $this->t('Block user'),
      '#attributes' => [
        'placeholder' => $this->t('Enter username')
      ],
      '#maxlength' => 128,
      '#size' => 128,
      '#selection_settings' => [
        'include_anonymous' => FALSE,
        'test' => 1
      ],
      '#selection_handler' => 'private_messages:user'
    ];

    $form['submit'] = [
        '#attributes' => [
          'class' => ['btn-secondary']
        ],
        '#type' => 'submit',
        '#value' => $this->t('Block'),
        '#ajax' => [
          'callback' => '::blockUserAjaxCallback',
          'progress' => ['type' => 'throbber', 'message' => NULL],
        ]
    ];

    return $form;
  }

  /**
   * Ajax callback implementation.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function blockUserAjaxCallback(array &$form, FormStateInterface $form_state)
  {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#block-user-form', $form));
    $response->addCommand(new HtmlCommand('#blocked-users-view-wrapper', views_embed_view('blocked_users','block_list')));

    return $response;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    $blocked_user = $form_state->getValue('user');

//    $block_user = new UserBlockMessagingAction(
//      ['uid' => $blocked_user ],
//      'user_block_messaging_action',
//      ['type' => 'user']
//    );

    /** @var \Drupal\user\UserInterface $user */
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($this->currentUser->id());
    $user->field_blocked_user->appendItem([
      'target_id' => $blocked_user
    ]);
    $user->save();
  }

}
