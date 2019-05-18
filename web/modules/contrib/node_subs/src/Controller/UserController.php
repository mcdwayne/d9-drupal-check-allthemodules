<?php

namespace Drupal\node_subs\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node_subs\Service\AccountService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Egulias\EmailValidator\EmailValidatorInterface;
use Drupal\node_subs\Service\NodeService;

/**
 * Class UserController.
 */
class UserController extends ControllerBase {

  /**
   * \Drupal\Core\Logger\LoggerChannelInterface definition.
   * 
   * @var \Drupal\Core\Logger\LoggerChannelInterface The registered logger for node_subs module channel.
   */
  protected $logger;

  /**
   * @var string|null The client IP address
   */
  protected $ip;

  /**
   * Egulias\EmailValidator\EmailValidatorInterface definition.
   *
   * @var \Egulias\EmailValidator\EmailValidatorInterface
   */
  protected $emailValidator;
  /**
   * Drupal\node_subs\Service\NodeService definition.
   *
   * @var \Drupal\node_subs\Service\NodeService
   */
  protected $nodeService;
  /**
   * Drupal\node_subs\Service\AccountService definition.
   *
   * @var \Drupal\node_subs\Service\AccountService
   */
  protected $accountService;

  /**
   * Constructs a new UserController object.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, RequestStack $request_stack, EmailValidatorInterface $email_validator, NodeService $node_service, AccountService $account_service) {
    $this->logger = $logger_factory->get('node_subs');
    $this->emailValidator = $email_validator;
    $this->ip = $request_stack->getCurrentRequest()->getClientIp();
    $this->nodeService = $node_service;
    $this->account = $account_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
      $container->get('request_stack'),
      $container->get('email.validator'),
      $container->get('node_subs.nodes'),
      $container->get('node_subs.account')
    );
  }

  /**
   * Confirmation page.
   *
   * @return array
   *   Return Confirmation string.
   */
  public function confirm() {
    return [
      '#type' => 'markup',
      '#prefix' => '<div id="node_subs-confirmation-text">',
      '#suffix' => '</div>',
      '#markup' => $this->nodeService->getText('confirmation')
    ];
  }
  /**
   * Unsubscribe page .
   *
   * @return string
   *   Return Hello string.
   */
  public function unsubscribe($user_token) {
    $email = isset($_GET['email']) ? $_GET['email'] : FALSE;
    $check = FALSE;
    if ($email) {
      if (!$this->emailValidator->isValid($email)) {
        $this->logger->error('Invalid email unsubscribe request. Email is @email, ip: @ip', ['@email' => $email, '@ip' => $this->ip]);
      }
      else {
        $account = $this->account->loadByEmail($email);
        if (!$account) {
          $this->logger->warning('Invalid account unsubscribe request. Email is @email, ip: @ip', ['@email' => $email, '@ip' => $this->ip]);
        }
        else {
          $check = $this->account->getToken($email) === $user_token;
          if (!$check) {
            $mail = $email ? $email : 'not defined';
            $this->logger->warning('Invalid token unsubscribe request. Email is @email, ip: @ip', ['@email' => $mail, '@ip' => $this->ip]);
          }
        }
      }
    }
    // todo: make it better.
    if (!$check) {
      $out['error'] = [
        '#markup' => $this->t('We cant unsubscribe you.'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
      return $out;
    }
    if ($account->status) {
      $out['info'] = [
        '#markup' => $this->t('Email @email has been unsubscribed', ['@email' => $email]),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
      $account->status = 0;
      $this->account->save($account);
      $this->logger->info('User has been unsubscribed. Email is @email, ip: @ip', ['@email' => $email, '@ip' => $this->ip]);
    }
    else {
      $out['info'] = array(
        '#markup' => $this->t('Email @email is already unsubscribed', ['@email' => $email]),
        '#prefix' => '<p>',
        '#suffix' => '</p>'
      );
    }
    return $out;
  }
  /**
   * Ajaxsubmit.
   * todo: make it work.
   */
  public function AjaxSubmit() {
    $response =  new AjaxResponse();
    return $response;
  }

}
