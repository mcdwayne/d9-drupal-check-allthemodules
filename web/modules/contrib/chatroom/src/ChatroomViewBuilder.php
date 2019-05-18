<?php


namespace Drupal\chatroom;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Config\Config;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Render controller for chatrooms.
 */
class ChatroomViewBuilder extends EntityViewBuilder {

  /**
   * Constructs a new ChatroomViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\Config $config
   *   The 'chatroom.settings' config.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, Config $config) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('config.factory')->get('chatroom.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);

    if (empty($entities)) {
      return;
    }

    foreach ($entities as $id => $entity) {
      $display = $displays['chatroom'];

      if ($display->getComponent('chat_window')) {
        $build[$id]['chat_window'] = array(
          '#lazy_builder' => [static::class . '::renderChat', [$entity->id()]],
          '#cache' => ['max-age' => 0],
        );
      }

      if ($display->getComponent('user_list')) {
        $build[$id]['user_list'] = array(
          '#lazy_builder' => [static::class . '::renderUserList', [$entity->id()]],
          '#cache' => ['max-age' => 0],
        );
      }

    }
  }

  /**
   * Renders the chat window.
   *
   * @param $cid
   *   Chatroom id.
   * @return array
   *   Render array.
   */
  public static function renderChat($cid) {
    $storage = \Drupal::entityManager()->getStorage('chatroom');

    $chatroom = $storage->load($cid);

    $build = [
      '#theme' => 'chatroom_irc',
      '#chatroom' => $chatroom,
    ];

    chatroom_attach_js_settings($build);

    $chatroom_settings = [
      'cid' => $chatroom->cid->value,
      'title' => $chatroom->title->value,
    ];

    // Allow modules to alter the js for this chatroom.
    \Drupal::moduleHandler()->alter('chatroom_room_settings', $chatroom_settings);

    $build['#attached']['drupalSettings']['chatroom']['chats'][$chatroom->cid->value] = $chatroom_settings;

    return $build;
  }

  /**
   * Renders the user list.
   *
   * @param $cid
   *   Chatroom id.
   * @return array
   *   Render array.
   */
  public static function renderUserList($cid) {
    $storage = \Drupal::entityManager()->getStorage('chatroom');

    $chatroom = $storage->load($cid);

    $build = [
      '#theme' => 'chatroom_irc_user_list',
      '#chatroom' => $chatroom,
    ];

    return $build;
  }

}
