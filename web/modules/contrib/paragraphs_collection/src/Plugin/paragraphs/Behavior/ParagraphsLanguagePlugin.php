<?php

namespace Drupal\paragraphs_collection\Plugin\paragraphs\Behavior;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a way to hide specific paragraphs depending on the current language.
 *
 * @ParagraphsBehavior(
 *   id = "language",
 *   label = @Translation("Visibility per language"),
 *   description = @Translation("Restricts visibility of a paragraph per language. Usage on children of a container paragraph which uses a container behavior like Grid layout can have unexpected visual results."),
 *   weight = 0
 * )
 */
class ParagraphsLanguagePlugin extends ParagraphsBehaviorBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The module handler.
   */
  protected $moduleHandler;

  /**
   * ParagraphsLanguagePlugin constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManager $entity_field_manager, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager);

    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('language_manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    if (!$this->languageManager->isMultilingual()) {
      return [];
    }

    foreach ($this->languageManager->getLanguages() as $language_code => $language) {
      $options[$language_code] = $language->getName();
    }

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['paragraphs-plugin-inline-container'],
      ],
    ];

    $form['container']['visibility'] = [
      '#type' => 'select',
      '#title' => $this->t('Language visibility'),
      '#options' => [
        'always' => $this->t('- Always visible -'),
        'hide' => $this->t('Hide for'),
        'show' => $this->t('Show for'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['container', 'visibility']),
      '#multiple' => FALSE,
      '#attributes' => [
        'id' => ['paragraphs-behavior-language-behavior-form-visibility-' . $paragraph->id()],
        'class' => ['paragraphs-plugin-form-element']
      ],
    ];

    $use_select2 = $this->moduleHandler->moduleExists('select2');
    $form['container']['languages'] = [
      '#type' => $use_select2 ? 'select2' : 'select',
      '#options' => $options,
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => 'none',
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), ['container', 'languages']),
      '#states' => [
        'invisible' => [
          ':input[id="paragraphs-behavior-language-behavior-form-visibility-' . $paragraph->id() . '"]' => ['value' => 'always'],
        ],
      ],
      '#multiple' => TRUE,
      '#attributes' => [
        'class' => ['paragraphs-behavior-language-behavior-form-languages', 'paragraphs-plugin-form-element'],
      ],
    ];

    if ($use_select2) {
      $form['container']['languages']['#select2']['width'] = 'auto';
    }

    $form['#attached']['library'][] = 'paragraphs_collection/plugin_admin';
    $form['container']['#attributes']['class'][] = 'paragraphs-behavior-language-behavior-form';

    return $form;
  }

  /**
   * Check the access for the paragraph based on the visibility setting.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   * @param string $operation
   *   The operation.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The logged in user.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public static function determineParagraphAccess(ParagraphInterface $paragraph, $operation, AccountInterface $account) {
    $access_result = AccessResult::neutral();
    /** @var \Drupal\paragraphs\Entity\ParagraphsType $type */
    $type = $paragraph->getParagraphType();

    if ($operation === 'view' && $type->hasEnabledBehaviorPlugin('language')) {
      $visibility = $paragraph->getBehaviorSetting('language', ['container', 'visibility']);
      if (in_array($visibility, ['show', 'hide'], TRUE)) {
        $languages = $paragraph->getBehaviorSetting('language', ['container', 'languages']) ?: [];
        $current_language = \Drupal::languageManager()->getCurrentLanguage();

        // In the 'show' visibility mode: Hide the paragraph, if the current
        // language is not among the selected ones.
        if ($visibility == 'show') {
          $access_result = AccessResult::forbiddenIf(!in_array($current_language->getId(), $languages));
        }
        // In the 'hide' visibility mode: Hide the paragraph, if the current
        // language is among the selected ones.
        else {
          $access_result = AccessResult::forbiddenIf(in_array($current_language->getId(), $languages));
        }
      }
    }

    return $access_result->addCacheableDependency($paragraph)->addCacheableDependency($type);
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraphs_entity, EntityViewDisplayInterface $display, $view_mode) {
    // Do nothing.
  }

}
