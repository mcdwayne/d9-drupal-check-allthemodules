<?php

namespace Drupal\crisis_mode\Form;

use Drupal\block\Entity\Block;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Configure crisis mode settings for this site.
 */
class CrisisModeSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Language\LanguageManagerInterface definition.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
  LanguageManagerInterface $languageManager,
  EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $logger) {
    parent::__construct($config_factory);
    $this->languageManager = $languageManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crisis_mode_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('crisis_mode.settings');

    $form['crisis_mode_enabler'] = [
      '#type' => 'details',
      '#title' => $this->t('Crisis Mode'),
      '#markup' => $this->t('In case of a crisis or any other big incident happening, activate the <strong>Crisis Situation</strong> flag below and click save.<p><strong>Caution: Only use in case of crisis!</strong></p><div>'),
      '#open' => TRUE,
    ];

    $form['crisis_mode_enabler']['crisis_mode_active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Crisis Situation'),
      '#default_value' => $config->get('crisis_mode_active'),
    ];

    $form['crisis_mode_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Crisis Mode Settings'),
      '#open' => TRUE,
    ];

    $form['crisis_mode_settings']['crisis_mode_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Crisis Mode Block Title'),
      '#default_value' => $config->get('crisis_mode_title'),
      '#required' => TRUE,
    ];

    $form['crisis_mode_settings']['crisis_mode_text'] = [
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#title' => $this->t('Crisis Text'),
      '#description' => $this->t('This text will be shown everywhere on the website.'),
      '#default_value' => $config->get('crisis_mode_text.value'),
      '#required' => TRUE,
    ];

    if ($config->get('crisis_mode_node')) {
      $node = $this->entityTypeManager->getStorage('node')->load($config->get('crisis_mode_node'));
    }
    else {
      $node = '';
    }
    $form['crisis_mode_settings']['crisis_mode_node'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#default_value' => $node,
      '#title' => $this->t('Link to page with more information'),
    ];

    $form['crisis_mode_settings']['crisis_mode_link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Button Text'),
      '#default_value' => $config->get('crisis_mode_link_title'),
    ];

    $active_theme = $this->configFactory->get('system.theme')->get('default');
    $regions = system_region_list($active_theme);
    $form['crisis_mode_settings']['crisis_mode_region'] = [
      '#title' => $this->t('Choose a region'),
      '#description' => $this->t('The region the block will appear (always as first block in the chosen region). Default is <b>Content</b> if none other is selected.'),
      '#type' => 'select',
      '#options' => $regions,
      '#empty_option' => $this->t('-- Choose a region --'),
      '#default_value' => $config->get('crisis_mode_region'),
    ];

    if ($this->languageManager->isMultilingual()) {
      $languages = $this->languageManager->getLanguages();
      $lang_options = [];
      foreach ($languages as $key => $language) {
        $lang_options[$key] = $language->getName();
      }

      if ($config->get('crisis_mode_language_restriction')) {
        $lang_config = $config->get('crisis_mode_language_restriction');
      }
      else {
        $lang_config = [];
      }

      $form['crisis_mode_settings']['crisis_mode_language_restriction'] = [
        '#title' => $this->t('Language restriction'),
        '#description' => $this->t('Restrict to languages. The Block will only be visible in the selected languages. None selected means the block will be shown in all languages.'),
        '#type' => 'checkboxes',
        '#options' => $lang_options,
        '#default_value' => $lang_config,
      ];

    }

    $form['crisis_mode_settings']['crisis_mode_background_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Background Color'),
      '#default_value' => $config->get('crisis_mode_background_color'),
    ];

    $form['crisis_mode_settings']['crisis_mode_background_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Crisis Mode Block Background-Image'),
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size' => [25600000],
      ],
      '#theme' => 'image_widget',
      '#preview_image_style' => 'medium',
      '#upload_location' => 'public://crisis_mode',
      '#required' => FALSE,
      '#default_value' => $config->get('crisis_mode_background_image'),
    ];

    $form['crisis_mode_settings']['crisis_mode_block_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Crisis Mode Block Image'),
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_size' => [25600000],
      ],
      '#theme' => 'image_widget',
      '#preview_image_style' => 'medium',
      '#upload_location' => 'public://crisis_mode',
      '#required' => FALSE,
      '#default_value' => $config->get('crisis_mode_block_image'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('crisis_mode.settings');
    $values = $form_state->cleanValues()->getValues();

    // Write config.
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    if ($form_state->getValue('crisis_mode_active')) {
      $block = Block::load('crisismodeblock');
      $block->enable();
      $block->save();
      drupal_set_message($this->t('Crisis Mode enabled!'));
      $this->logger->get('Crisis Mode')
        ->notice($this->t('Crisis Mode Enabled'));
    }
    else {
      $block = Block::load('crisismodeblock');
      $block->disable();
      $block->save();
      drupal_set_message($this->t('Crisis Mode disabled!'));
      $this->logger->get('Crisis Mode')
        ->notice($this->t('Crisis Mode Disabled'));
    }

    if ($form_state->getValue('crisis_mode_region')) {
      $block = Block::load('crisismodeblock');
      $block->setRegion($form_state->getValue('crisis_mode_region'));
      $block->save();
    }

    if ($form_state->getValue('crisis_mode_language_restriction')) {
      $languages_selected = $form_state
        ->getValue('crisis_mode_language_restriction');
      if ($languages_selected) {
        $languages = [];
        foreach ($languages_selected as $key => $item) {
          if ($item) {
            $languages[$key] = $item;
          }
        }
        $block = Block::load('crisismodeblock');
        $lang_context = '@language.current_language_context:language_interface';
        $visibility = $block->getVisibility();
        $visibility['language']['langcodes'] = $languages;
        $visibility['language']['context_mapping']['language'] = $lang_context;
        $block->setVisibilityConfig('language', $visibility['language']);
        $block->save();
      }

    }
    drupal_flush_all_caches();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'crisis_mode.settings',
    ];
  }

}
