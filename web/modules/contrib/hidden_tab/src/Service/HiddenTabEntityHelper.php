<?php

namespace Drupal\hidden_tab\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\hidden_tab\Entity\HiddenTabMailerInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Entity\HiddenTabPlacementInterface;
use Drupal\node\Entity\NodeType;

/**
 * {@inheritdoc}
 */
class HiddenTabEntityHelper implements HiddenTabEntityHelperInterface {

  /**
   * To find pages from.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $pageStorage;

  /**
   * To find placements from.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $placementStorage;

  /**
   * To find mailers from.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mailerStorage;

  /**
   * HiddenTabEntityHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $em
   *   To get page storage from.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $em) {
    $this->pageStorage = $em->getStorage('hidden_tab_page');
    $this->placementStorage = $em->getStorage('hidden_tab_placement');
    $this->mailerStorage = $em->getStorage('hidden_tab_mailer');
  }

  /**
   * {@inheritdoc}
   */
  public function pageByTabUri($tab_uri): ?HiddenTabPageInterface {
    $page = $this->pageStorage->loadByProperties([
      'tab_uri' => $tab_uri,
    ]);
    if ($page && is_array($page) && count($page)) {
      foreach ($page as $p) {
        return $p;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function pageBySecretUri($secret_uri): ?HiddenTabPageInterface {
    $page = $this->pageStorage->loadByProperties([
      'secret_uri' => $secret_uri,
    ]);
    if ($page && is_array($page) && count($page)) {
      foreach ($page as $p) {
        return $p;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function page(string $id): ?HiddenTabPageInterface {
    /** @noinspection PhpIncompatibleReturnTypeInspection */
    return $this->pageStorage->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function pages(): array {
    return $this->pageStorage->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public function allPagesForSelectElement(): array {
    $options = [];
    foreach ($this->pages() as $page) {
      $options[$page->id()] = $page->label();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function placementsOfPage(string $page_id): array {
    return $this->placementStorage->loadByProperties([
      'target_hidden_tab_page' => $page_id,
    ]);
  }

  // =========================================================================

  /**
   * {@inheritdoc}
   */
  public function placement(string $id): ?HiddenTabPlacementInterface {
    /** @noinspection PhpIncompatibleReturnTypeInspection */
    return $this->placementStorage->load($id);
  }

  // =========================================================================

  /**
   * {@inheritdoc}
   */
  public function entityMailers(HiddenTabPageInterface $page, EntityInterface $entity): array {
    $mailers = $this->mailerStorage->loadByProperties([
      'target_hidden_tab_page' => $page->id(),
    ]);
    $mailers = array_filter($mailers, function (HiddenTabMailerInterface $m) use ($page, $entity): bool {
      return (
        !$m->isEnabled()
        || (static::isVal($page->targetEntityId()) && $m->targetEntityId() != $entity->id())
        || $page->targetEntityType() && $m->targetEntityType() !== $entity->getEntityTypeId()
        || $page->targetEntityBundle() && $m->targetEntityBundle() !== $entity->bundle()
        || static::isVal($page->targetUserId())
      )
        ? FALSE
        : TRUE;
    });
    return $mailers;
  }

  /**
   * Ensures zero is taken into account as an existing entity id, but not null.
   *
   * @param int|string $val
   *   Id to check.
   *
   * @return bool
   *   If this is an entity id.
   */
  private static function isVal($val): bool {
    return $val === 0 || $val === '0' || $val;
  }

  // =========================================================================

  /**
   * Build list of bundles for select element.
   *
   * TODO refactor and remove after generic entity type
   *
   * @param bool $any
   *   If none option should be added.
   *
   * @return array
   */
  public static function nodeBundlesSelectList($any = FALSE): array {
    $options = $any ? ['' => t('Any')] : [];
    foreach (NodeType::loadMultiple() as $type) {
      /** @noinspection PhpUndefinedMethodInspection */
      $options[$type->id()] = $type->label();
    }
    return $options;
  }

  /**
   * Check to see if a Uri already exists or not.
   *
   * Machine name element callback.
   *
   * TODO FIXME check all drupal Uris.
   *
   * @param string $uri
   *   Uri to check.
   *
   * @return bool
   *   True if is, false otherwise.
   */
  public static function uriExists(string $uri): bool {
    $helper = \Drupal::service('hidden_tab.entity_helper');
    if ($helper->pageByTabUri($uri)) {
      return TRUE;
    }
    return $helper->pageBySecretUri($uri) ? TRUE : FALSE;
  }

  /**
   * TODO remove this call, use container.
   */
  public static function instance(): HiddenTabEntityHelperInterface {
    return \Drupal::service('hidden_tab.entity_helper');
  }

}
