<?php

namespace Drupal\micro_path\PathProcessor;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\Core\Path\AliasManagerInterface;

/**
 * A micro_path processor for inbound and outbound paths.
 *
 * This processor is meant to override the core alias processing when a micro
 * path exists for the current site. For this reason the processing order is
 * important. The inbound processing needs to happen before the path module
 * alias processor so that we can turn micro path aliases into system paths
 * first. The outbound processing needs to happen after path module alias
 * processing so that we can be sure it doesn't mess with our micro path alias
 * after we're done with it.
 */
class MicroPathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * A language manager for looking up the current language.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * A domain path loader for loading domain path entities.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * An alias manager for looking up the system path.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * A domain negotiator for looking up the current domain.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * DomainPathProcessor constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The site negotiator.
   */
  public function __construct(LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, AliasManagerInterface $alias_manager, SiteNegotiatorInterface $site_negotiator) {
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasManager = $alias_manager;
    $this->negotiator = $site_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if ($active_site = $this->negotiator->getActiveSite()) {
      $properties = [
        'alias' => $path,
        'site_id' => $active_site->id(),
        'language' => $this->languageManager->getCurrentLanguage()->getId(),
      ];
      $micro_paths = $this->entityTypeManager->getStorage('micro_path')->loadByProperties($properties);
    }
    if (empty($micro_paths)) {
      return $path;
    }
    $micro_path = reset($micro_paths);

    return $micro_path->getSource();
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if (empty($options['alias']) && ($active_site = $this->negotiator->getActiveSite())) {
      // It's possible the path module has aliased this path already so we're
      // going to revert that.
      $unaliased_path = $this->aliasManager->getPathByAlias($path);
      $properties = [
        'source' => $unaliased_path,
        'site_id' => $active_site->id(),
        'language' => $this->languageManager->getCurrentLanguage()->getId(),
      ];
      $micro_paths = $this->entityTypeManager->getStorage('micro_path')->loadByProperties($properties);
      if (empty($micro_paths)) {
        return $path;
      }
      $micro_path = reset($micro_paths);
      // If the unaliased path matches our micro path source (internal url)
      // then we have a match and we output the alias, otherwise we just pass
      // the original $path along.
      return $micro_path->getAlias();
    }

    return $path;
  }

}
