<?php

namespace Drupal\random_reference_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Session\Session;
use Drupal\Core\Access\CsrfTokenGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Plugin implementation of the 'random js entity reference ID' formatter.
 *
 * @FieldFormatter(
 *   id = "random_entity_reference_id_view",
 *   label = @Translation("Random Rendered entity"),
 *   description = @Translation("Display one randomly picked entity from referenced entities."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class RandomEntityReferenceIdFormatter extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityDisplayRepositoryInterface $entity_display_repository, LanguageManagerInterface $language_manager, Session $session, CsrfTokenGenerator $csrf_token_generator) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityDisplayRepository = $entity_display_repository;
    $this->languageManager = $language_manager;
    $this->session = $session;
    $this->csrfToken = $csrf_token_generator;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['view_mode'], $configuration['third_party_settings'],
      $container->get('entity_display.repository'),
      $container->get('language_manager'),
      $container->get('session'),
      $container->get('csrf_token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view_mode' => 'default',
      'quantity' => 1,
      'link' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['view_mode'] = [
      '#type' => 'select',
      '#options' => $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type')),
      '#title' => $this->t('View mode'),
      '#default_value' => $this->getSetting('view_mode'),
      '#required' => TRUE,
    ];

    $elements['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity'),
      '#description' => $this->t('The amount of items to be rendered.'),
      '#default_value' => $this->getSetting('quantity') ?: 1,
      '#required' => FALSE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $view_modes = $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type'));
    $view_mode = $this->getSetting('view_mode');
    $summary[] = $this->t('Rendered as @mode', ['@mode' => isset($view_modes[$view_mode]) ? $view_modes[$view_mode] : $view_mode]);
    $summary[] = $this->t('Quantity @quantity', ['@quantity' => $this->getSetting('quantity') ?: 1]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $view_mode = $this->getSetting('view_mode');

    $candidates = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $entity) {
      if ($entity->id()) {
        $candidates[] = $entity->id() . "/" . $entity->getEntityTypeId();
      }
    }

    if (empty($candidates)) {
      return [];
    }

    // Build the properties merged with field #theme.
    // @see: $this->view() method.
    return [
      '#attributes' => [
        'class' => [
          'random-entity-placeholder',
          'random-entity-placeholder--hidden',
        ],
        'data-entity-random-candidates' => implode(',', $candidates),
        'data-entity-random-viewmode' => $view_mode,
        'data-entity-random-quantity' => $this->getSetting('quantity'),
      ],
      '#attached' => [
        'library' => ['random_reference_formatter/randomEntity'],
        'drupalSettings' => [
          'randomEntityCallbackURL' => $this->getCallbackUrl(),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    // Default the language to the current content language.
    if (empty($langcode)) {
      $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    }
    $elements = $this->viewElements($items, $langcode);
    if (empty($elements)) {
      return [];
    }

    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $field_name = $this->fieldDefinition->getName();
    $info = [
      '#theme' => 'field',
      '#title' => $this->fieldDefinition->getLabel(),
      '#label_display' => $this->label,
      '#view_mode' => $this->viewMode,
      '#language' => $items->getLangcode(),
      '#field_name' => $field_name,
      '#field_type' => $this->fieldDefinition->getType(),
      '#field_translatable' => $this->fieldDefinition->isTranslatable(),
      '#entity_type' => $entity_type,
      '#bundle' => $entity->bundle(),
      '#object' => $entity,
      '#items' => [],
      '#formatter' => $this->getPluginId(),
      '#is_multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
    ];

    $elements = array_merge($info, $elements);

    return $elements;
  }

  /**
   * Get the callback URL supporting CSRF token.
   *
   * @return string
   *   The callback URL.
   */
  private function getCallbackUrl() {

    // Start a session for Anonymous users so the CSRF token works.
    if ($this->session->isStarted() === FALSE) {
      $this->session->start();
      $this->session->set('random_reference_formatter_init', TRUE);
    }

    $url = Url::fromRoute('random_reference_formatter.callback');
    $token = $this->csrfToken->get($url->getInternalPath());
    $url->setOptions(['query' => ['token' => $token]]);

    return $url->toString();
  }

}
