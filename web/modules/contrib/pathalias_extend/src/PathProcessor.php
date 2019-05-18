<?php

namespace Drupal\pathalias_extend;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes pathes with extended path aliases.
 */
class PathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * Alias storage.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * Entity type manager.
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
   * Path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs a PathProcessor object.
   *
   * @param \Drupal\Core\Path\AliasStorageInterface $alias_storage
   *   Alias Storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   Path matcher.
   */
  public function __construct(AliasStorageInterface $alias_storage, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, PathMatcherInterface $path_matcher) {
    $this->aliasStorage = $alias_storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    return $this->processPath('inbound', $path, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $langcode = isset($options['language']) ? $options['language']->getId() : NULL;
    return $this->processPath('outbound', $path, $request, $langcode);
  }

  /**
   * Process a path.
   *
   * @param string $type
   *   Processing type. Either 'inbound' or 'outbound'.
   * @param string $path
   *   Path to process.
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   Request for path to process.
   * @param string|null $langcode
   *   Language code for path to process. If NULL, current URL language will be
   *   used.
   *
   * @return string
   *   Processed path.
   */
  protected function processPath(string $type, string $path, Request $request = NULL, $langcode = NULL): string {
    $minimum_slashes = $type === 'inbound' ? 2 : 3;
    if (empty($path) || substr_count($path, '/') < $minimum_slashes) {
      // This path is definetely not in our scope of path candidates.
      return $path;
    }
    if ($langcode === NULL) {
      $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();
    }
    $function = $type === 'inbound' ? 'lookupPathSource' : 'lookupPathAlias';
    $result = $this->aliasStorage->{$function}($path, $langcode);
    if ($result !== FALSE && ($type === 'outbound' || $path !== $result)) {
      // The path is aliased. Nothing to do for us.
      return $path;
    }

    // Front-to-back parsing even though it's more unspecific, so that we won't
    // accidentally allow e.g. /example/extend/extend/extend.
    $slash_pos = $type === 'inbound' ? strpos($path, '/', 1) : strpos($path, '/', strpos($path, '/', 1) + 1);
    $subpath = substr($path, 0, $slash_pos);
    while ($subpath) {
      $result = $this->aliasStorage->{$function}($subpath, $langcode);
      if ($result) {
        $url = \Drupal::pathValidator()->getUrlIfValidWithoutAccessCheck($subpath);
        if (!$url) {
          // If this doesn't work, something is seriously wrong.
          return $path;
        }
        if (preg_match('/^entity\.([^.]+)\.canonical$/', $url->getRouteName(), $matches)) {
          // This is an entity route. Get entity type from route name.
          $entity_type = $matches[1];

          // Get bundle.
          $parameters = $url->getRouteParameters();
          $eid = isset($parameters[$entity_type]) ? $parameters[$entity_type] : 0;
          if (!empty($eid)) {
            $entity = $this->entityTypeManager->getStorage($entity_type)->load($eid);
            if ($entity instanceof ContentEntityInterface) {
              $bundle = $entity->bundle();

              // Get suffix candidates.
              $storage = $this->entityTypeManager->getStorage('pathalias_extend_suffix');
              $pattern = substr($path, $slash_pos);
              $suffixes = $storage
                ->getQuery()
                ->condition('target_entity_type_id', $entity_type)
                ->condition('target_bundle_id', $bundle)
                ->condition('status', TRUE)
                ->execute();

              if (count($suffixes) > 0) {
                $valid_extension = FALSE;
                $extension = substr($path, $slash_pos);
                $suffixes = $storage->loadMultiple($suffixes);
                foreach ($suffixes as $suffix) {
                  $pattern = $suffix->getPattern();
                  if (empty($pattern)) {
                    continue;
                  }
                  if ($this->pathMatcher->matchPath($extension, $pattern)) {
                    $valid_extension = TRUE;
                    break;
                  }
                }

                if ($valid_extension) {
                  // Save an alias, if configured.
                  if ($suffix->getCreateAlias()) {
                    $source = $type === 'inbound' ? $result . $extension : $path;
                    $target = $type === 'inbound' ? $path : $result . $extension;
                    $this->aliasStorage->save($source, $target, $langcode);
                  }

                  if ($type === 'inbound' && $request !== NULL) {
                    // Disable redirect route normalizer to prevent 301
                    // redirects. This modification will not be cached, so it
                    // will work the first time only. However, it is enough for
                    // those cases, where we create an alias for the extended
                    // alias. If a user disabled this setting, they need to set
                    // this attribute on the target route themselves.
                    $request->attributes->set('_disable_route_normalizer', TRUE);
                  }

                  // Return target path.
                  return $result . $extension;
                }
              }
            }
          }
        }
      }
      $slash_pos = strpos($path, '/', $slash_pos + 1);
      $subpath = substr($path, 0, $slash_pos);
    }

    return $path;
  }

}
