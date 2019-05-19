<?php

/**
 * @file
 * Contains \Drupal\station_playlist\Controller\PlaylistsByProgramController.
 */

namespace Drupal\station_playlist\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @todo.
 */
class PlaylistsByProgramController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $playlistStorage;

  /**
   * PlaylistsByProgramController constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $playlist_storage
   */
  public function __construct(EntityStorageInterface $playlist_storage) {
    $this->playlistStorage = $playlist_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('node')
    );
  }

  /**
   * @todo.
   */
  public function title(EntityInterface $entity) {
    return $this->t('@title Playlists', ['@title' => $entity->label()]);
  }

  /**
   * @todo.
   */
  public function render(EntityInterface $entity) {
    if ($entity->bundle() !== 'station_program') {
      throw new NotFoundHttpException();
    }

    $items = [];
    foreach ($this->getPlaylists($entity) as $playlist) {
      $items[] = [
        'title' => $playlist->label(),
        'url' => $playlist->toUrl(),
      ];
    }
    return [
      '#theme' => 'links',
      '#links' => $items,
    ];
  }

  /**
   * @todo.
   */
  protected function getPlaylists(EntityInterface $entity) {
    $ids = $this->playlistStorage->getQuery()
      ->condition('type', 'station_playlist')
      ->condition('station_playlist_program', $entity->id())
      ->execute();
    return $this->playlistStorage->loadMultiple($ids);
  }

}
