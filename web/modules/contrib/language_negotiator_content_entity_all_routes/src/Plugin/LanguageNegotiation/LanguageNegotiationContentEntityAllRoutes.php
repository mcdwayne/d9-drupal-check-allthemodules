<?php

/**
 * @file
 * Contains \Drupal\language_negotiator_content_entity_all_routes\Plugin\LanguageNegotiation\LanguageNegotiationContentEntityAllRoutes.
 */

namespace Drupal\language_negotiator_content_entity_all_routes\Plugin\LanguageNegotiation;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationContentEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Class for identifying the content translation language.
 *
 * @LanguageNegotiation(
 *   id = Drupal\language_negotiator_content_entity_all_routes\Plugin\LanguageNegotiation\LanguageNegotiationContentEntityAllRoutes::METHOD_ID,
 *   types = {Drupal\Core\Language\LanguageInterface::TYPE_CONTENT},
 *   weight = -10,
 *   name = @Translation("Content language (all routes)"),
 *   description = @Translation("Determines the content language from a request parameter (all routes)."),
 * )
 */
class LanguageNegotiationContentEntityAllRoutes extends LanguageNegotiationContentEntity {

  /**
   * The language negotiation method ID.
   */
  const METHOD_ID = 'language-content-entity-all-routes';

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Controls strict mode.
   *
   * @var bool
   */
  protected $strictMode;

  /**
   * Constructs a new LanguageNegotiationContentEntityAllRoutes instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, $strict_mode) {
    parent::__construct($entity_manager);
    $this->languageManager = $language_manager;
    $this->strictMode = $strict_mode;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $strict_mode = $container->get('config.factory')->get('language_negotiator_content_entity_all_routes.settings')->get('strict');
    return new static(
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $strict_mode
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    // As we are enforcing the language, it might happen that an entity is
    // using a locked language, which will not be returned by the language
    // manager by default. However we still want to propagate it and therefore
    // if the parent does not return a valid language, we have to explicitly
    // check the locked languages.
    $result = parent::getLangcode($request);
    if (!$result) {
      $langcode = $request->query->get(static::QUERY_PARAMETER);

      $locked_languages = $this->languageManager->getLanguages(LanguageInterface::STATE_LOCKED);
      $language_enabled = array_key_exists($langcode, $locked_languages);
      $result = $language_enabled ? $langcode : $result;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $path = parent::processOutbound($path, $options, $request, $bubbleable_metadata);

    if ($this->strictMode && !isset($options['query'][static::QUERY_PARAMETER])) {
      if ($request && !empty($options['route']) && $this->hasLowerLanguageNegotiationWeight() && $this->meetsContentEntityRoutesCondition($options['route'], $request, TRUE)) {
        $outbound_path_pattern = $options['route']->getPath();
        $entity_type_id = $this->getContentEntityPaths()[$outbound_path_pattern];
        $entity_type = $this->entityManager->getDefinition($entity_type_id);

        if ($entity_type->isTranslatable()) {
          $link_templates = $entity_type->getLinkTemplates();
          $allowed_link_templates = array_diff_key($link_templates, array_flip(['collection', 'create', 'add-page', 'add-form']));
          if (in_array($outbound_path_pattern, $allowed_link_templates)) {
            throw new \Exception("The language option for the URL to the entity route \"{$path}\" is missing, but is required in strict mode.");
          }
          // On add-form entity routes add the current content language to the
          // URL so that the entity form is initially build for the current
          // content language.
          elseif (isset($link_templates['add-form']) && ($link_templates['add-form'] === $outbound_path_pattern)) {
            $langcode = $request ? $this->getLangcode($request) : $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
            if ($langcode) {
              $options['query'][static::QUERY_PARAMETER] = $langcode;

              if ($bubbleable_metadata) {
                // Cached URLs that have been processed by this outbound path
                // processor must be:
                $bubbleable_metadata
                  // - varied by the content language query parameter.
                  ->addCacheContexts(['url.query_args:' . static::QUERY_PARAMETER]);
              }
            }
          }
        }
      }
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   *
   * Overwrite the function for the "on content entity route condition" to
   * allow for the language negotiator apply for all content entities on all
   * routes and not only e.g. for content entity "A" when being on a route of
   * content entity "A".
   */
  protected function meetsContentEntityRoutesCondition(Route $outbound_route, Request $request, $match_only_entity_routes = FALSE) {
    if ($match_only_entity_routes) {
      $outbound_path_pattern = $outbound_route->getPath();
      $result = empty($this->getContentEntityPaths()[$outbound_path_pattern]) ? FALSE : TRUE;
    }
    else {
      $result = TRUE;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function hasLowerLanguageNegotiationWeight() {
    // As we're transforming a protected method into a public one, we have to
    // explicitly check that the language negotiation method is active before
    // calling the parent implementation.
    if (!isset($this->hasLowerLanguageNegotiationWeightResult)) {
      $content_method_weights = $this->config->get('language.types')->get('negotiation.language_content.enabled') ?: [];
      if (!isset($content_method_weights[static::METHOD_ID])) {
        $this->hasLowerLanguageNegotiationWeightResult = FALSE;
      }
    }
    if (!isset($this->hasLowerLanguageNegotiationWeightResult)) {
      parent::hasLowerLanguageNegotiationWeight();
    }
    return $this->hasLowerLanguageNegotiationWeightResult;
  }

}
