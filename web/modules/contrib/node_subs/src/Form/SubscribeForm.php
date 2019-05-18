<?php

namespace Drupal\node_subs\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Egulias\EmailValidator\EmailValidatorInterface;
use Drupal\node_subs\Service\AccountService;
use Drupal\node_subs\Service\NodeService;

/**
 * Class SubscribeForm.
 */
class SubscribeForm extends FormBase {

  /**
   * Drupal\Core\Config\ConfigManagerInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;
  /**
   * Egulias\EmailValidator\EmailValidatorInterface definition.
   *
   * @var \Egulias\EmailValidator\EmailValidatorInterface
   */
  protected $emailValidator;
  /**
   * Drupal\node_subs\Service\AccountService definition.
   *
   * @var \Drupal\node_subs\Service\AccountService
   */
  protected $account;
  /**
   * Drupal\node_subs\Service\NodeService definition.
   *
   * @var \Drupal\node_subs\Service\NodeService
   */
  protected $nodeService;


  /**
   * Constructs a new SubscribeForm object.
   */
  public function __construct(
    ConfigManagerInterface $config_manager,
    EmailValidatorInterface $email_validator,
    AccountService $account_service,
    NodeService $node_service
  ) {
    $this->configManager = $config_manager;
    $this->emailValidator = $email_validator;
    $this->account = $account_service;
    $this->nodeService= $node_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.manager'),
      $container->get('email.validator'),
      $container->get('node_subs.account'),
      $container->get('node_subs.nodes')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscribe_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['messages'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'node_subs-messages'
        ],
        'id' => Html::cleanCssIdentifier('form-system-messages')
      ],
    ];
    $form['ajax_container'] = [
      '#type' => 'fieldset',
      '#attributes' => [
        'class' => [
          'node_subs-ajax-container'
        ],
      ],
    ];

    $form['ajax_container']['email'] = [
      '#title' => t('E-mail'),
      '#type' => 'email',
      '#required' => TRUE,
      '#weight' => 1,
    ];

    $form['ajax_container']['actions'] = [
      '#type' => 'actions',
      '#weight' => 2,
    ];

    $form['ajax_container']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => [
        'class' => ['submit-ajax', 'use-ajax'],
      ],
      '#ajax' => [
        'callback' => '::submitAjax',
        'event' => 'click',
      ]
    );

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
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
    $form_state->cleanValues();
    $data = $this->process($form_state);

    $message = $this->nodeService->getText($data['message_key']);

    if ($data['message_key'] == 'exists') {
      $message_type = 'warning';
      $form_state->setStorage(['node_subs_message' => $message]);
    }
    else {
      $form_state->setStorage(['account' => $data['account'], 'node_subs_message' => $message]);
      $form_state->setRedirect('node_subs.subscribe');
      $message_type = 'status';
    }
    drupal_set_message($message, $message_type);

  }

  public function process($form_state) {
    $values = $form_state->getValues();
    $return_data = [];
    $return_data['message_key'] = 'confirmation';
    $account = $this->account->loadByEmail($values['email']);
    if (!$account) {
      $name = empty($values['name']) ? FALSE : trim($values['name']);
      $account = $this->account->create($values['email'], $name);
    }
    else {
      if ($account->status) {
        $return_data['message_key'] = 'exists';
      }
      else {
        $account->status = 1;
        $this->account->save($account);
      }
    }
    $return_data['account'] = $account;
    return $return_data;
  }

  public function submitAjax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $messages = [
      '#theme' => 'status_messages',
      '#message_list' => drupal_get_messages(),
      '#status_headings' => [
        'status' => t('Status message'),
        'error' => t('Error message'),
        'warning' => t('Warning message'),
      ],
    ];

    $notification_type = $form_state->getBuildInfo()['notification_type' ?? 'messages'];

    switch ($notification_type) {
      case 'messages':
        $messages = \Drupal::service('renderer')->render($messages);
        $response->addCommand(new HtmlCommand('#form-system-messages', $messages));
        break;
      case 'popup':
        $message = $form_state->getStorage()['node_subs_message'];
        $response->addCommand(new OpenModalDialogCommand('Subscribing result', $message));
        break;
    }

    return $response;

  }

}
