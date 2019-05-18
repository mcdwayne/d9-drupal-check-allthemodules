<?php

namespace Drupal\forward;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\Core\Utility\Token;
use Drupal\forward\Form\ForwardForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a class for building markup for a Forward inline form on an entity.
 */
class ForwardFormBuilder implements ForwardFormBuilderInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * The flood interface.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $floodInterface;

  /**
   * The account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * The render service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The event dispatcher service.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * The mail service.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailer;

  /**
   * The link generation service.
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $linkGenerator;

  /**
   * Constructs a ForwardFormBuilder object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Database\Connection
   *   The database connection.
   * @param \Drupal\Core\Utility\Token
   *   The token service.
   * @param \Drupal\Core\Flood\FloodInterface
   *   The flood interface.
   * @param \Drupal\Core\Session\AccountSwitcherInterface
   *   The account switcher service.
   * @param \Drupal\Core\Render\RendererInterface
   *   The render service.
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   *   The event dispatcher service.
   * @param \Drupal\Core\Mail\MailManager
   *   The mail service.
   * @param \Drupal\Core\Utility\LinkGenerator
   *   The link generation service.
   */
  public function __construct(FormBuilderInterface $form_builder, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack, Connection $database, Token $token_service, FloodInterface $flood_interface, AccountSwitcherInterface $account_switcher, RendererInterface $renderer, ContainerAwareEventDispatcher $event_dispatcher, MailManager $mailer, LinkGenerator $link_generator) {
    $this->formBuilder = $form_builder;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
    $this->database = $database;
    $this->tokenService = $token_service;
    $this->floodInterface = $flood_interface;
    $this->accountSwitcher = $account_switcher;
    $this->renderer = $renderer;
    $this->eventDispatcher = $event_dispatcher;
    $this->mailer = $mailer;
    $this->linkGenerator = $link_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('request'),
      $container->get('database'),
      $container->get('token'),
      $container->get('flood'),
      $container->get('account.switcher'),
      $container->get('renderer'),
      $container->get('event_dispatcher'),
      $container->get('plugin.manager.mail'),
      $container->get('link_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForwardEntityForm(EntityInterface $entity, array $settings) {
    // The entity must be injected first, since it is used to compute the form ID.
    $form = new ForwardForm($entity);
    // Now inject the services.
    $form->injectServices($this->moduleHandler, $this->entityTypeManager, $this->requestStack, $this->database, $this->tokenService, $this->floodInterface, $this->accountSwitcher, $this->renderer, $this->eventDispatcher, $this->mailer, $this->linkGenerator);
    $render_array = $this->formBuilder->getForm($form, $settings);
    $render_array['#weight'] = $settings['forward_interface_weight'];
    return $render_array;
  }
}
