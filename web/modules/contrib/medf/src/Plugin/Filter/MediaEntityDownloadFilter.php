<?php

namespace Drupal\media_entity_download_filter\Plugin\Filter;

use Drupal\Component\Utility\Html;
use \Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\linkit\SubstitutionManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Linkit filter which generate file url for media.
 *
 * Note this must run before any Xss::filter() calls, because that strips
 * disallowed protocols. That effectively means this must run before the
 * \Drupal\filter\Plugin\Filter\FilterHtml filter. Hence the very low weight.
 *
 * @Filter(
 *   id = "media_entity_download_filter",
 *   title = @Translation("Link media direct to download url"),
 *   description = @Translation("Updates content links inserted by Linkit to point to the current download URL, and have the current title."),
 *   type =  Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   weight = -10
 * )
 */
class MediaEntityDownloadFilter extends FilterBase implements ContainerFactoryPluginInterface {

  const FIELD_FILE_NAME = 'field_file';

  const MEDIA_ENTITY = 'media';

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The substitution manager.
   *
   * @var \Drupal\linkit\SubstitutionManagerInterface
   */
  protected $substitutionManager;

  /**
   * Constructs a LinkitFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityRepositoryInterface $entity_repository, SubstitutionManagerInterface $substitution_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityRepository = $entity_repository;
    $this->substitutionManager = $substitution_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('plugin.manager.linkit.substitution')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    /** @var \Drupal\filter\FilterProcessResult $entity */
    $result = new FilterProcessResult($text);

    if (strpos($text, 'data-entity-type') === FALSE && strpos($text, 'data-entity-uuid') === FALSE) {
      return $result;
    }

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    /** @var \DOMElement[] $domElements */
    $domElements = $xpath->query('//a[@data-entity-type and @data-entity-uuid]');

    foreach ($domElements as $element) {
      $this->findAndReplaceMediaEntityLink($result, $element, $langcode);
    }

    $result->setProcessedText(Html::serialize($dom));

    return $result;
  }

  /**
   * @param \Drupal\filter\FilterProcessResult $result
   * @param \DOMElement $element
   * @param $langcode
   */
  private function findAndReplaceMediaEntityLink(FilterProcessResult $result, \DOMElement $element, $langcode) {
    // Load the appropriate translation of the linked entity.
    $entity_type = $element->getAttribute('data-entity-type');
    $uuid = $element->getAttribute('data-entity-uuid');

    if (!$this->isValidMediaReference($entity_type, $uuid)) {
      return;
    }
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->loadTranslatedEntity($entity_type, $uuid, $langcode);

    if (!$entity) {
      return;
    }
    /** @var \Drupal\Core\Url $url */
    $url = $this->getUrl($entity);

    if (!$url) {
      return;
    }

    $access = $entity->access('view', NULL, TRUE);
    // Set the appropriate title attribute.
    if (!$access->isForbidden() && !$element->getAttribute('title')) {
      $element->setAttribute('title', $entity->label());
    }

    $element->setAttribute('href', $url->toString());
    // The processed text now depends on:
    $result
      // - the linked entity access for the current user.
      ->addCacheableDependency($access)
      // - the generated URL (which has undergone path & route
      // processing)
      ->addCacheableDependency($url)
      // - the linked entity (whose URL and title may change)
      ->addCacheableDependency($entity);
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\Core\Url|null
   */
  private function getUrl(EntityInterface $entity) {
    if (!$entity->hasField(self::FIELD_FILE_NAME)) {
      return NULL;
    }

    $fileUri = $entity->get(self::FIELD_FILE_NAME)
      ->first()
      ->entity
      ->getFileUri();
    $url = file_create_url($fileUri);

    return Url::fromUri($url);
  }

  /**
   * @param string $entity_type
   * @param string $uuid
   *
   * @return bool
   */
  private function isValidMediaReference($entity_type, $uuid) {
    if (!$entity_type) {
      return FALSE;
    }

    if (!$uuid) {
      return FALSE;
    }

    return $entity_type === self::MEDIA_ENTITY;
  }

  /**
   * @param string $entity_type
   * @param string $uuid
   * @param string $langcode
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  private function loadTranslatedEntity($entity_type, $uuid, $langcode) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->entityRepository
      ->loadEntityByUuid($entity_type, $uuid);

    if (!$entity) {
      return $entity;
    }

    return $this->entityRepository
      ->getTranslationFromContext($entity, $langcode);
  }
}
