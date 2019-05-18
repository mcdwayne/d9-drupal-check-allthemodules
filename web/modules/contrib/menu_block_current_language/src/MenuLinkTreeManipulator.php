<?php

namespace Drupal\menu_block_current_language;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\InaccessibleMenuLink;
use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\locale\StringStorageInterface;
use Drupal\menu_block_current_language\Event\Events;
use Drupal\menu_block_current_language\Event\HasTranslationEvent;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Drupal\views\Plugin\Menu\ViewsMenuLink;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class MenuLinkTreeManipulator.
 *
 * @package Drupal\menu_block_current_language\Menu
 */
class MenuLinkTreeManipulator {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The locale storage.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $localeStorage;

  /**
   * MenuLinkTreeManipulator constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\locale\StringStorageInterface $locale_storage
   *   The locale storage.
   */
  public function __construct(LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, EventDispatcherInterface $event_dispatcher, StringStorageInterface $locale_storage) {
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->eventDispatcher = $event_dispatcher;
    $this->localeStorage = $locale_storage;
  }

  /**
   * Load entity with given menu link.
   *
   * @param \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $link
   *   The menu link.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface|null
   *   Boolean if menu link has no metadata. NULL if entity not found and
   *   an EntityInterface if found.
   */
  protected function getEntity(MenuLinkContent $link) {
    // MenuLinkContent::getEntity() has protected visibility and cannot be used
    // to directly fetch the entity.
    $metadata = $link->getMetaData();

    if (empty($metadata['entity_id'])) {
      return FALSE;
    }
    return $this->entityTypeManager
      ->getStorage('menu_link_content')
      ->load($metadata['entity_id']);
  }

  /**
   * Check if given string has a string translation.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $markup
   *   The markup.
   *
   * @return bool
   *   TRUE if found translation, FALSE if not.
   */
  protected function hasStringTranslation(TranslatableMarkup $markup) {
    // Skip this check for source language.
    // @todo This might cause some issues if string source language is not english.
    if ($this->languageManager->getCurrentLanguage() == $this->languageManager->getDefaultLanguage()) {
      return TRUE;
    }
    $conditions = [
      'language' => $this->languageManager->getCurrentLanguage()->getId(),
      'translated' => TRUE,
    ];
    // Attempt to load translated menu links for current language.
    $translations = $this->localeStorage->getTranslations($conditions, [
      'filters' => ['source' => $markup->getUntranslatedString()],
    ]);
    /** @var \Drupal\locale\TranslationString $translation */
    foreach ($translations as $translation) {
      // No translation found / original string found.
      if ($translation->isNew()) {
        continue;
      }
      // Make sure source strings are identical as getTranslations()
      // load strings with wildcard (%string%) and might return
      // an unexpected results.
      if ($translation->source == $markup->getUntranslatedString()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Filter out links that are not translated to the current language.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   * @param array $providers
   *   The menu block translatable link types.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function filterLanguages(array $tree, array $providers = []) {
    $current_language = $this->languageManager->getCurrentLanguage()->getId();

    foreach ($tree as $index => $item) {
      // Handle expanded menu links.
      if ($item->hasChildren) {
        $item->subtree = $this->filterLanguages($item->subtree, $providers);
      }
      $link = $item->link;

      // MenuLinkDefault links have no common provider so fallback to 'default'.
      $provider = $link instanceof MenuLinkDefault ? 'default' : $link->getProvider();
      // Skip checks for disabled core providers. Isset check is used
      // to determine whether provider should be checked and empty whether
      // the provider is enabled or not (0 = disabled).
      if (isset($providers[$provider]) && empty($providers[$provider])) {
        continue;
      }
      /** @var \Drupal\menu_block_current_language\Event\HasTranslationEvent $event */
      // Allow other modules to determine visibility as well.
      $event = $this->eventDispatcher->dispatch(Events::HAS_TRANSLATION, new HasTranslationEvent($link, TRUE));

      // This only works with translated menu links.
      if ($link instanceof MenuLinkContent && $entity = $this->getEntity($link)) {
        /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $entity */
        if (!$entity->isTranslatable()) {
          // Skip untranslatable items.
          continue;
        }
        if (!$entity->hasTranslation($current_language)) {
          $event->setHasTranslation(FALSE);
        }
      }
      // String translated menu links.
      elseif ($link->getPluginDefinition()['title'] instanceof TranslatableMarkup) {
        /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $markup */
        $markup = $link->getPluginDefinition()['title'];

        if (!$this->hasStringTranslation($markup)) {
          $event->setHasTranslation(FALSE);
        }
      }
      elseif ($link instanceof ViewsMenuLink) {
        $view_id = sprintf('views.view.%s', $link->getMetaData()['view_id']);

        // Make sure that original configuration exists for given view.
        if (!$original = $this->configFactory->get($view_id)->get('langcode')) {
          continue;
        }
        // ConfigurableLanguageManager::getLnguageConfigOverride() always
        // returns a new configuration override for the original language.
        if ($current_language === $original) {
          continue;
        }
        /** @var \Drupal\language\Config\LanguageConfigOverride $config */
        $config = $this->languageManager->getLanguageConfigOverride($current_language, $view_id);
        // Configuration override will be marked as a new if one does not
        // exist for the current language (thus has no translation).
        if ($config->isNew()) {
          $event->setHasTranslation(FALSE);
        }
      }
      // Allow custom menu link types to expose multilingual capabilities
      // through an interface.
      elseif ($link instanceof MenuLinkTranslatableInterface) {
        if (!$link->hasTranslation($current_language)) {
          $event->setHasTranslation(FALSE);
        }
      }
      if ($event->hasTranslation() === FALSE) {
        $tree[$index]->access = AccessResult::forbidden();
      }
    }
    return $tree;
  }

}
