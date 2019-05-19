<?php

namespace Drupal\third_party_services;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserDataInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\Component\Render\MarkupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Basic implementation of MediatorInterface.
 */
class Mediator implements MediatorInterface {

  /**
   * Instance of the "user.data" service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;
  /**
   * Instance of the "current_user" service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;
  /**
   * Instance of the "form_builder" service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;
  /**
   * Storage of the "taxonomy_term" entities.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $taxonomyTermStorage;
  /**
   * Instance of the "MODULE.raw_html_renderer" service.
   *
   * @var RawHtmlRenderer
   */
  protected $rawHtmlRenderer;
  /**
   * Value of the "MODULE.default_vocabulary" container property.
   *
   * @var string
   */
  protected $defaultVocabulary = '';
  /**
   * Value of the "MODULE.default_vocabulary" container property.
   *
   * @var string
   */
  protected $vocabularyRequiredField = '';
  /**
   * List of cache tags.
   *
   * @var string[]
   */
  private $cacheTags = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    UserDataInterface $user_data,
    AccountInterface $account,
    FormBuilderInterface $form_builder,
    EntityTypeManagerInterface $entity_type_manager,
    RawHtmlRenderer $raw_html_renderer,
    string $default_vocabulary,
    string $vocabulary_required_field
  ) {
    $this->userData = $user_data;
    $this->currentUser = $account;
    $this->formBuilder = $form_builder;
    $this->taxonomyTermStorage = $entity_type_manager->getStorage('taxonomy_term');
    $this->rawHtmlRenderer = $raw_html_renderer;
    $this->defaultVocabulary = $default_vocabulary;
    $this->vocabularyRequiredField = $vocabulary_required_field;

    $taxonomies = $entity_type_manager
      ->getStorage('field_config')
      ->getQuery()
      ->condition('entity_type', $this->taxonomyTermStorage->getEntityTypeId())
      ->condition('field_name', $this->vocabularyRequiredField)
      ->condition('bundle', $this->defaultVocabulary)
      ->execute();

    if (empty($taxonomies)) {
      throw new \RuntimeException(sprintf(
        'Something went entirely wrong with the "%s" vocabulary. It must exist with the "%s" field!',
        $this->defaultVocabulary,
        $this->vocabularyRequiredField
      ));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('user.data'),
      $container->get('current_user'),
      $container->get('form_builder'),
      $container->get('entity_type.manager'),
      $container->get('third_party_services.raw_html_renderer'),
      $container->getParameter('third_party_services.default_vocabulary'),
      $container->getParameter('third_party_services.vocabulary_required_field')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getServicesPatterns(): array {
    // Do not use class-level static property because it have no sense.
    static $patterns;

    if (!isset($patterns)) {
      foreach ($this->loadTerms() as $term) {
        $pattern = implode('|', array_map(function (array $item) {
          // Assume each value as part of regular expression for recognition.
          return preg_quote($item['value']);
        }, $term->get($this->vocabularyRequiredField)->getValue()));

        if ('' !== $pattern) {
          $patterns[$pattern] = $term;
        }
      }
    }

    return $patterns;
  }

  /**
   * {@inheritdoc}
   */
  public function isServiceAllowed(TermInterface $term, AccountInterface $account = NULL): bool {
    $uid = ($account ?? $this->currentUser)->id();

    if ($uid > 0) {
      static $terms = [];

      if (!isset($terms[$uid])) {
        $terms[$uid] = (array) $this->userData->get('third_party_services', $uid, 'third_party_services_allowed');
      }

      return in_array($term->uuid(), $terms[$uid]);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setAllowedServices(array $term_uuids, AccountInterface $account = NULL) {
    $uid = ($account ?? $this->currentUser)->id();

    if ($uid > 0) {
      $this->userData->set('third_party_services', $uid, 'third_party_services_allowed', $term_uuids);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadTerms(): array {
    return $this->taxonomyTermStorage->loadTree($this->defaultVocabulary, 0, NULL, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    if (empty($this->cacheTags)) {
      $this->cacheTags = array_map(function (TermInterface $term) {
        return $term->uuid();
      }, $this->loadTerms());
    }

    return $this->cacheTags;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationForm(...$arguments): array {
    $form_state = new FormState();
    $form_state->addBuildInfo('args', $arguments);

    return $this->formBuilder->buildForm(static::CONFIGURATION_FORM, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function placeholder($markup, array &$build, string $type, int $delta) {
    if (!is_string($markup) || $markup instanceof MarkupInterface) {
      $markup = $this->rawHtmlRenderer->renderRoot($markup);
    }

    foreach ($this->getServicesPatterns() as $pattern => $term) {
      $uuid = $term->uuid();

      // Prefill cache tags to not double load them in "getCacheTags" method.
      $this->cacheTags[] = $uuid;

      if (preg_match("/$pattern/", $markup)) {
        $response = $this->rawHtmlRenderer->produceResponse($build);

        $build = [
          '#type' => $type,
          '#delta' => $delta,
          '#uuid' => $uuid,
          '#label' => $term->label(),
          '#content' => $response->getContent(),
          '#theme' => 'third_party_services__placeholder',
        ];

        return $response;
      }
    }

    return NULL;
  }

}
