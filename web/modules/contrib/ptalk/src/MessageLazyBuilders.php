<?php

namespace Drupal\ptalk;

use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines a service for private conversation #lazy_builder callbacks.
 */
class MessageLazyBuilders {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * Current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new MessageLazyBuilders object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityFormBuilderInterface $entity_form_builder, AccountInterface $current_user, ModuleHandlerInterface $module_handler, RendererInterface $renderer, RouteMatchInterface $route_match) {
    $this->entityManager = $entity_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->routeMatch = $route_match;
  }

  /**
   * #lazy_builder callback; builds links of the message.
   *
   * @param string $thread_id
   *   The ptalk_thread entity ID.
   * @param string $view_mode
   *   The view mode in which the message entity is being viewed.
   * @param bool $is_in_preview
   *   Whether the message is being previewed.
   *
   * @return array
   *   A renderable array representing the message links.
   */
  public function renderLinks($thread_id, $view_mode, $is_in_preview) {
    $links = [
      '#theme' => 'links__ptalk_message',
      '#pre_render' => ['drupal_pre_render_links'],
      '#attributes' => ['class' => ['links', 'inline']],
    ];

    if (!$is_in_preview) {
      /** @var \Drupal\ptalk\MessageInterface $entity */
      $entity = $this->entityManager->getStorage('ptalk_message')->load($thread_id);

      $links['ptalk_message'] = $this->buildLinks($entity);

      // Allow other modules to alter the message links.
      $hook_context = [
        'view_mode' => $view_mode,
      ];
      $this->moduleHandler->alter('ptalk_message_links', $links, $entity, $hook_context);
    }
    return $links;
  }

  /**
   * Build the default links for a message.
   *
   * @param \Drupal\ptalk\MessageInterface $entity
   *   The ptalk_message object.
   *
   * @return array
   *   An array to processed for the drupal_pre_render_links().
   */
  protected function buildLinks(MessageInterface $entity) {
    $links = [];

    if ($this->currentUser->hasPermission('delete message private conversation')) {
      if (!$entity->isDeleted()) {
        $links['message-delete'] = [
          'title' => t('Delete'),
          'attributes' => ['title' => t('Delete this message from the conversation.')],
          'url' => $entity->urlInfo('delete-form'),
        ];
      }
    }

    if ($this->currentUser->hasPermission('restore message private conversation')) {
      if ($entity->isDeleted()) {
        $links['message-restore'] = [
          'title' => t('Restore'),
          'attributes' => ['title' => t('Restore this message in to conversation.')],
          'url' => $entity->urlInfo('restore-form'),
        ];
      }
    }

    return [
      '#theme' => 'links__ptalk_message',
      '#links' => $links,
      '#attributes' => ['class' => ['links', 'inline']],
    ];
  }

  /**
   * #lazy_builder callback; builds a messages of the thread.
   *
   * @param string $thread_id
   *   The ptalk_thread entity ID.
   * @param string $view_mode
   *   The view mode in which the ptalk_thread entity is being viewed.
   *
   * @return array
   *   A renderable array representing the ptalk_message entities.
   */
  public function renderMessages($thread_id, $view_mode) {

    /** @var \Drupal\ptalk\ThreadInterface $entity */
    $entity = $this->entityManager->getStorage('ptalk_thread')->load($thread_id);
    $messages = $this->buildMessages($entity, $view_mode);

    return $messages;
  }

  /**
   * Build array with messages and elements for output messages on the thread page.
   *
   * @param \Drupal\ptalk\ThreadInterface $entity
   *   The ptalk_thread object.
   * @param string $view_mode
   *   The view mode in which the ptalk_thread entity is being viewed.
   *
   * @return array
   *   A renderable array with elements (messages, pager, message_form) for build messages on the thread page.
   */
  public function buildMessages(ThreadInterface $entity, $view_mode) {
    $output = [];
    $config = \Drupal::config('ptalk.settings');
    $messages_per_page = $config->get('ptalk_messages_per_page');
    $load_deleted = $this->currentUser->hasPermission('read all private conversation') ? TRUE : FALSE;
    $pager_id = 0;

    $messages = $this->entityManager->getStorage('ptalk_thread')->loadThreadMessages($entity, $this->currentUser, $load_deleted, $messages_per_page, 0);
    if ($messages) {
      $output['messages'] = $this->entityManager->getViewBuilder('ptalk_message')->viewMultiple($messages, $view_mode);
      $output['pager']['#type'] = 'pager';
      $output['pager']['#route_name'] = $this->routeMatch->getRouteObject();
      $output['pager']['#route_parameters'] = $this->routeMatch->getRawParameters()->all();
      if ($pager_id) {
        $output['pager']['#element'] = $pager_id;
      }
      $build['messages'] = $output;
    }

    if ($this->currentUser->hasPermission('reply private conversation') && !$entity->in_preview) {
      $values = [
        'thread_id' => $entity->id(),
      ];
      $message = $this->entityManager->getStorage('ptalk_message')->create($values);
      $output['message_form'] = $this->entityFormBuilder->getForm($message);
    }

    return $output;
  }

}
