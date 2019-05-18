<?php

namespace Drupal\fake_path_alias;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\PathProcessor\PathProcessorAlias;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound and outbound paths using path alias lookups.
 */
class PathProcessorFakeAlias extends PathProcessorAlias implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * The path alias storage service.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Current language id.
   *
   * @var string
   */
  protected $currentLangId;

  /**
   * Default language id.
   *
   * @var string
   */
  protected $defaultLangId;

  /**
   * Constructs a PathProcessorFakeAlias object.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   An alias manager for looking up the system path.
   * @param \Drupal\Core\Path\AliasStorageInterface $alias_storage
   *   The path alias storage.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(AliasManagerInterface $alias_manager, AliasStorageInterface $alias_storage, LanguageManagerInterface $language_manager) {
    parent::__construct($alias_manager);

    $this->aliasStorage = $alias_storage;
    $this->languageManager = $language_manager;
    $this->currentLangId = $this->languageManager->getCurrentLanguage()->getId();
    $this->defaultLangId = $this->languageManager->getDefaultLanguage()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $path = parent::processInbound($path, $request);

    // Current language has no alias, but original has one.
    if (!$this->aliasStorage->aliasExists($path, $this->currentLangId)
        && $this->aliasStorage->aliasExists($path, $this->defaultLangId)
    ) {
      // Get node source path from passed alias in original language.
      $path = $this->aliasManager->getPathByAlias($path, $this->defaultLangId);
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $path = parent::processOutbound($path, $options, $request, $bubbleable_metadata);

    if (empty($options['alias'])) {
      // Alias doesn't exist for current language and source too.
      if (!$this->aliasStorage->aliasExists($path, $this->currentLangId)
          && !$this->aliasStorage->lookupPathSource($path, $this->currentLangId)
      ) {
        // Instead of original node source path, get node alias.
        $path = $this->aliasManager->getAliasByPath($path, $this->defaultLangId);
      }
    }

    return $path;
  }

}
