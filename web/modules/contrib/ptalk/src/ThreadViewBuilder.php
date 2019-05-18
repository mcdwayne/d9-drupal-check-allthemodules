<?php

namespace Drupal\ptalk;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ptalk\Entity\Thread;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View builder handler for threads.
 */
class ThreadViewBuilder extends EntityViewBuilder {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ThreadViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, AccountInterface $current_user) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);
    if (!$entity->in_preview) {
      $build['#cache']['contexts'][] = 'ptalk_thread_participant_id';
      $build['messages'] = [
        '#lazy_builder' => [
          'message.lazy_builders:renderMessages', [
            $entity->id(),
            $view_mode,
          ],
        ],
        '#create_placeholder' => TRUE,
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    /** @var \Drupal\ptalk\ThreadInterface[] $entities */
    if (empty($entities)) {
      return;
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {
      $build[$id]['links'] = [
        '#lazy_builder' => [
          get_called_class() . '::renderLinks', [
            $entity->id(),
            $view_mode,
            !empty($entity->in_preview),
          ],
        ],
        '#create_placeholder' => TRUE,
      ];

      $limit_participants = \Drupal::config('ptalk.settings')->get('ptalk_limit_participants');
      $participants = ptalk_generate_user_array($entity->participants->value);
      $participants = ptalk_format_participants($participants, FALSE, (int) $limit_participants, FALSE);

      $build[$id]['participants'] = [
        '#type' => 'item',
        '#markup' => $participants,
      ];
    }
  }

  /**
   * #lazy_builder callback; builds a thread links.
   *
   * @param string $thread_id
   *   The ptalk_thread entity ID.
   * @param string $view_mode
   *   The view mode in which the ptalk_thread is being viewed.
   * @param bool $is_in_preview
   *   Whether the ptalk_thread is currently being previewed.
   *
   * @return array
   *   A renderable array representing the ptalk_thread links.
   */
  public static function renderLinks($thread_id, $view_mode, $is_in_preview) {
    $links = [
      '#theme' => 'links__ptalk_thread',
      '#pre_render' => ['drupal_pre_render_links'],
      '#attributes' => ['class' => ['links', 'inline']],
    ];

    if (!$is_in_preview) {
      /** @var \Drupal\ptalk\ThreadInterface $entity */
      $entity = Thread::load($thread_id);
      $links['ptalk_thread'] = static::buildLinks($entity);

      // Allow other modules to alter the thread links.
      $hook_context = [
        'view_mode' => $view_mode,
      ];
      \Drupal::moduleHandler()->alter('ptalk_thread_links', $links, $entity, $hook_context);
    }
    return $links;
  }

  /**
   * Build the default link (reply) for a thread.
   *
   * @param \Drupal\ptalk\ThreadInterface $entity
   *   The ptalk_thread object.
   *
   * @return array
   *   An array that can be processed by drupal_pre_render_links().
   */
  protected static function buildLinks(ThreadInterface $entity) {
    $links = [];

    if (\Drupal::currentUser()->hasPermission('reply private conversation')) {
      $links['message-add'] = [
        'title' => t('A new message'),
        'attributes' => ['title' => t('Join to conversation.')],
        'fragment' => 'ptalk-message-form',
        'url' => $entity->urlInfo(),
      ];
    }

    return [
      '#theme' => 'links__ptalk_thread',
      '#links' => $links,
      '#attributes' => ['class' => ['links', 'inline']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $ptalk_thread, EntityViewDisplayInterface $display, $view_mode) {
    parent::alterBuild($build, $ptalk_thread, $display, $view_mode);
  }

}
