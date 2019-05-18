<?php

namespace Drupal\hidden_tab\Entity\Helper;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\hidden_tab\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * For configuration entities, helps safely build a list of entities.
 */
abstract class ConfigListBuilderBase extends ConfigEntityListBuilder {

  /**
   * To see what operations user has access to.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $current_user;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type,
                              EntityStorageInterface $storage,
                              AccountProxyInterface $current_user) {
    parent::__construct($entity_type, $storage);
    $this->current_user = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container,
                                        EntityTypeInterface $entity_type) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public final function buildRow(EntityInterface $entity) {
    try {
      return $this->unsafeBuildRow($entity) + parent::buildRow($entity);
    }
    catch (\Throwable $error0) {
      Utility::renderLog($error0, $this->entityTypeId, '~');
      $ret['label'] = $entity->id();
      for ($i = 0; $i < (count($this->buildHeader()) - 1); $i++) {
        $ret[] = Utility::CROSS;
      }
      return $ret;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $op = parent::getOperations($entity);
    if ($this->current_user->hasPermission(Utility::THE_MIGHTY_DANGEROUS_LAYOUT_PERMISSION)) {
      $layout = [
        'layout' => [
          'title' => $this->t('Layout'),
          'weight' => 1,
          'url' => Url::fromRoute('entity.hidden_tab_page.layout_form',
            ['hidden_tab_page' => $entity->id()]),
        ],
      ];
      $op = $layout + $op;
    }
    return $op;
  }

  /**
   * Helps build a row, displaying an entity, in the table of entities.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity to render properties for.
   * @param array $props
   *   Properties of the entity to render.
   *
   * @return array
   *   The renderable array.
   */
  protected function configRowsBuilder(ConfigEntityInterface $entity, array $props) {
    $t = $entity->getEntityTypeId();
    $i = $entity->id();
    /** @var \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface $entity */
    $ret = [];
    foreach ($props as $prop) {
      switch ($prop) {
        case 'target_user':
          try {
            if (!$entity->targetUserId()) {
              $ret['target_user'] = Utility::CROSS;
            }
            else {
              $ret['target_user'] = Link::createFromRoute(
                $entity->targetUserEntity()->label(),
                'entity.user.canonical', [
                'user' => $entity->targetUserId(),
              ]);
            }
          }
          catch (\Throwable $error0) {
            Utility::renderLog($error0, $t, 'target_user', $i, NULL, [], FALSE);
            try {
              $ret['target_user'] = $entity->targetUserId();
            }
            catch (\Throwable $error1) {
              Utility::renderLog($error1, $t, 'target_user', $i);
              $ret['target_user'] = Utility::WARNING;
            }
          }
          break;

        case 'target_entity':
          try {
            if (!$entity->targetEntityId()) {
              $ret['target_entity'] = Utility::CROSS;
            }
            else {
              $ret['target_entity'] = Link::createFromRoute(
                $entity->targetEntity()->label(),
                'entity.' . $entity->targetEntityType() . '.canonical', [
                  $entity->targetEntityType() => $entity->targetUserId(),
                ]
              );
            }
          }
          catch (\Throwable $error0) {
            Utility::renderLog($error0, $t, 'target_entity', $i, NULL, [], FALSE);
            try {
              $ret['target_entity'] = $entity->targetEntityId();
            }
            catch (\Throwable $error1) {
              Utility::renderLog($error1, $t, 'target_entity', $i);
              $ret['target_entity'] = Utility::WARNING;
            }
          }
          break;

        case 'target_hidden_tab_page':
          try {
            if (!$entity->targetPageId()) {
              $ret['target_hidden_tab_page'] = Utility::CROSS;
            }
            else {
              $ret['target_hidden_tab_page'] = Link::createFromRoute(
                $entity->targetPageEntity()->label(),
                'entity.hidden_tab_page.edit_form', [
                'hidden_tab_page' => $entity->targetPageId(),
              ]);
            }
          }
          catch (\Throwable $error0) {
            Utility::renderLog($error0, $t, 'target_hidden_tab_page', $i, NULL, [], FALSE);
            try {
              $ret['target_hidden_tab_page'] = $entity->targetPageId();
            }
            catch (\Throwable $error1) {
              Utility::renderLog($error1, $t, 'target_hidden_tab_page', $i);
              $ret['target_hidden_tab_page'] = Utility::WARNING;
            }
          }
          break;

        case 'id':
          try {
            $ret['id'] = $entity->toLink();
          }
          catch (\Throwable $error0) {
            Utility::renderLog($error0, $t, 'uri', $i, NULL, [], FALSE);
            try {
              $ret['id'] = $entity->label();
            }
            catch (\Throwable $error1) {
              Utility::renderLog($error1, $t, 'label', $i);
              $ret['id'] = Utility::WARNING;
            }
          }
          break;

        default:
          try {
            $ret[$prop] = $entity->get($prop) ?: Utility::CROSS;
          }
          catch (\Throwable $error1) {
            Utility::renderLog($error1, $t, $prop, $i);
            $ret[$prop] = Utility::CROSS;
          }
      }
    }
    return $ret;
  }

  /**
   * Helper for buildRow(), same as buildRow() but may freely throw exceptions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to build row from.
   *
   * @return array
   *   Built row.
   */
  protected abstract function unsafeBuildRow(EntityInterface $entity): array;

}
