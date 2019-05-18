<?php

namespace Drupal\fillpdf;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountInterface;

class FillPdfAccessController implements ContainerInjectionInterface {

  use MessengerTrait;
  use LoggerChannelTrait;

  /** @var \Drupal\fillpdf\FillPdfAccessHelperInterface */
  protected $accessHelper;

  /** @var FillPdfLinkManipulatorInterface */
  protected $linkManipulator;

  /** @var RequestStack $requestStack */
  protected $requestStack;

  /** @var FillPdfContextManagerInterface */
  protected $contextManager;

  /** @var AccountInterface $currentUser */
  protected $currentUser;

  public function __construct(FillPdfAccessHelperInterface $access_helper, FillPdfLinkManipulatorInterface $link_manipulator, FillPdfContextManagerInterface $context_manager, RequestStack $request_stack, AccountInterface $current_user) {
    $this->linkManipulator = $link_manipulator;
    $this->contextManager = $context_manager;
    $this->requestStack = $request_stack;
    $this->currentUser = $current_user;
    $this->accessHelper = $access_helper;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('fillpdf.access_helper'), $container->get('fillpdf.link_manipulator'), $container->get('fillpdf.context_manager'), $container->get('request_stack'), $container->get('current_user'));
  }

  public function checkLink() {
    try {
      $context = $this->linkManipulator->parseRequest($this->requestStack->getCurrentRequest());
    }
    catch (\InvalidArgumentException $exception) {
      $message = $exception->getMessage();
      $is_admin = $this->currentUser->hasPermission('administer pdfs');
      $this->messenger()->addError($is_admin ? $message : t('An error occurred. Please notify the administrator.'));
      $this->getLogger('fillpdf')->error($message);
      return AccessResult::forbidden();
    }

    $account = $this->currentUser;

    return $this->accessHelper->canGeneratePdfFromContext($context, $account);
  }

}
