<?php

namespace Drupal\private_message_nodejs\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\private_message\Entity\PrivateMessageThreadInterface;
use Drupal\private_message\Form\PrivateMessageForm;
use Drupal\private_message\Service\PrivateMessageServiceInterface;
use Drupal\private_message\Service\PrivateMessageThreadManagerInterface;
use Drupal\private_message_nodejs\Ajax\PrivateMessageNodejsTriggerInboxUpdateCommand;
use Drupal\private_message_nodejs\Ajax\PrivateMessageNodejsTriggerNewMessagesCommand;
use Drupal\private_message_nodejs\Ajax\PrivateMessageNodejsTriggerUnreadThreadCountUpdateCommand;
use Drupal\private_message_nodejs\Ajax\PrivateMessageNodejsTriggerBrowserNotificationCommand;
use Drupal\private_message_nodejs\Service\PrivateMessageNodejsService;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the private message form.
 */
class PrivateMessageNodejsForm extends PrivateMessageForm {

  /**
   * The private message nodejs service.
   *
   * @var \Drupal\private_message_nodejs\Service\PrivateMessageNodejsService
   */
  protected $privateMessageNodejsService;

  /**
   * The log service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a PrivateMessageForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typedDataManager
   *   The typed data manager service.
   * @param \Drupal\user\UserDataInterface $userData
   *   The user data service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\private_message\Service\PrivateMessageServiceInterface $privateMessageService
   *   The private message service.
   * @param \Drupal\private_message\Service\PrivateMessageThreadManagerInterface $privateMessageThreadManager
   *   The private message thread manager service.
   * @param \Drupal\private_message_nodejs\Service\PrivateMessageNodejsService $privateMessageNodejsService
   *   The private message nodejs service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The log service.
   */
  public function __construct(
    EntityManagerInterface $entityManager,
    AccountProxyInterface $currentUser,
    EntityTypeManagerInterface $entityTypeManager,
    TypedDataManagerInterface $typedDataManager,
    UserDataInterface $userData,
    ConfigFactoryInterface $configFactory,
    PrivateMessageServiceInterface $privateMessageService,
    PrivateMessageThreadManagerInterface $privateMessageThreadManager,
    PrivateMessageNodejsService $privateMessageNodejsService,
    LoggerChannelFactoryInterface $logger
  ) {
    parent::__construct($entityManager, $currentUser, $entityTypeManager, $typedDataManager, $userData, $configFactory, $privateMessageService, $privateMessageThreadManager);

    $this->privateMessageNodejsService = $privateMessageNodejsService;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('typed_data_manager'),
      $container->get('user.data'),
      $container->get('config.factory'),
      $container->get('private_message.service'),
      $container->get('private_message.thread_manager'),
      $container->get('private_message_nodejs.service'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PrivateMessageThreadInterface $privateMessageThread = NULL) {
    $form = parent::buildForm($form, $form_state, $privateMessageThread);

    $form['#attached']['library'][] = 'private_message_nodejs/form';

    return $form;
  }

  /**
   * Ajax callback for the PrivateMessageForm.
   */
  public function ajaxCallback(array $form, FormStateInterface $formState) {

    $response = parent::ajaxCallback($form, $formState);
    $response->addCommand(new PrivateMessageNodejsTriggerNewMessagesCommand());

    $uids = [];
    foreach ($formState->get('thread_members') as $member) {
      $uids[$member->id()] = $member->id();
    }

    if (!empty($uids)) {
      $response->addCommand(new PrivateMessageNodejsTriggerInboxUpdateCommand(array_values($uids)));
      $response->addCommand(new PrivateMessageNodejsTriggerUnreadThreadCountUpdateCommand(array_values($uids)));

      $config = $this->configFactory->get('private_message_nodejs.settings');
      if ($config->get('browser_notification_enable')) {
        // The current user doesn't need a browser notification of their own
        // message, so their ID is unset from the list of IDs.
        unset($uids[$this->currentUser->id()]);

        // Build the info that will become the browser notification.
        $message_info = $this->privateMessageNodejsService->buildBrowserPushNotificationData($this->entity, $formState->get('private_message_thread'));

        // A commmand is sent to notify users of the new message.
        $response->addCommand(new PrivateMessageNodejsTriggerBrowserNotificationCommand(array_values($uids), $message_info));

        if ($config->get('enable_debug')) {
          $this->logger->get('Private Message Nodejs debug: notifying browser')->notice(t('Sending browser notification to <pre>@uids</pre><br /><pre>@content</pre>', [
            '@uids' => print_r($uids, TRUE),
            '@content' => print_r($message_info, TRUE),
          ]));
        }

      }
    }

    return $response;
  }

}
