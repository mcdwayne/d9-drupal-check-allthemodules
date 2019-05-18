<?php

/**
 * @file
 * Contains \Drupal\redirect_deleted_entities\RedirectManager.
 */

namespace Drupal\redirect_deleted_entities;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Utility\Token;

/**
 * Provides methods for managing redirects on entity deletion.
 */
class RedirectManager implements RedirectManagerInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The token utility.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a new RedirectManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Utility\Token $token
   *   The token utility.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Token $token, EntityTypeManagerInterface $entity_type_manager, AliasManagerInterface $alias_manager) {
    $this->configFactory = $config_factory;
    $this->token = $token;
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasManager = $alias_manager;
  }


  /**
   * {@inheritdoc}
   */
  public function createRedirect(EntityInterface $entity) {
    $redirect_destination = $this->getRedirectByEntity($entity->getEntityTypeId(), $entity->bundle());
    if (!$redirect_destination) {
      return;
    }

    $url = '/' . $entity->toUrl()->getInternalPath();
    $alias = $this->aliasManager->getAliasByPath($url);
    if (!$alias) {
      return;
    }

    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->getRedirectEntityStorage()->create();
    $redirect->setRedirect($redirect_destination);
    $redirect->setSource($alias);
    $redirect->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectByEntity($entity_type_id, $bundle = '', $language = LanguageInterface::LANGCODE_NOT_SPECIFIED) {
    $config = $this->configFactory->get('redirect_deleted_entities.redirects');

    $pattern = '';
    $variables = [];
    $variables[] = "{$entity_type_id}.bundles.{$bundle}.languages.{$language}";
    if ($language != LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      $variables[] = "{$entity_type_id}.bundles.{$bundle}_{$language}.default";
    }
    if ($bundle) {
      $variables[] = "{$entity_type_id}.bundles.{$bundle}.default";
    }
    $variables[] = "{$entity_type_id}.default";

    foreach ($variables as $variable) {
      if ($pattern = trim($config->get('redirects.' . $variable))) {
        break;
      }
    }

    return $pattern;
  }


  /**
   * Gets the redirect entity type.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  protected function getRedirectEntityStorage() {
    return $this->entityTypeManager->getStorage('redirect');
  }

}
