<?php

namespace Drupal\dialect\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class SharedBlockConfigForm.
 *
 * @package Drupal\dialect\Form
 */
class SharedBlockConfigForm extends ConfigFormBase {

  const FALLBACK_FLAG = 'single_node_fallback';
  const FALLBACK_LANGUAGES = 'fallback_languages';
  const FALLBACK_NODE = 'fallback_node';
  const EXCLUDED_LANGUAGES = 'excluded_languages';

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'), $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dialect.shared_block_config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shared_block_config_form';
  }

  /**
   * Checks from the configuration if fallback languages is enabled.
   *
   * @return bool
   *   Single node fallback
   */
  private function hasFallbackLanguages() {
    $config = $this->config('dialect.shared_block_config');
    return $config->get(self::FALLBACK_FLAG);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dialect.shared_block_config');

    $form[self::EXCLUDED_LANGUAGES] = [
      '#type' => 'language_select',
      '#title' => $this->t('Excluded languages'),
      '#description' => $this->t('Languages that will not be displayed on the language selector and will be redirected to the site default language.'),
      '#multiple' => TRUE,
      '#default_value' => $config->get(self::EXCLUDED_LANGUAGES),
    ];

    $form[self::FALLBACK_FLAG] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Single node fallback'),
      '#description' => $this->t('Define a single node for some languages, instead of full site translation.'),
      '#default_value' => $config->get(self::FALLBACK_FLAG),
    ];

    // @todo remove excluded languages
    $form[self::FALLBACK_LANGUAGES] = [
      '#type' => 'language_select',
      '#title' => $this->t('Fallback languages'),
      '#description' => $this->t('Languages that will use a single node as translation.'),
      '#multiple' => TRUE,
      '#default_value' => $config->get(self::FALLBACK_LANGUAGES),
      '#states' => [
        'invisible' => [
          ':input[name="' . self::FALLBACK_FLAG . '"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $node = NULL;
    if ($config->get(self::FALLBACK_NODE) !== NULL) {
      $nodeId = $config->get(self::FALLBACK_NODE);
      $node = $this->entityTypeManager->getStorage('node')->load((int) $nodeId);
    }

    // Limit entity_autocomplete to the translatable "content" (node) types
    // (@see #2863050).
    // $nodeTypes = $this->entityTypeManager->getStorage('node_type')
    // ->loadMultiple();
    // foreach ($nodeTypes as $nodeType) {
    // if ($nodeType instanceof NodeType) {
    // // Cannot find helper that indicates if an instance of NodeType
    // // is translatable.
    // // @todo review loadByProperties instead of entityQuery
    // $sampleNode = $this->entityTypeManager->getStorage('node')
    // ->loadByProperties();
    // //kint($nodeType->language()->isDefault());
    // }
    // }
    // then define the #selection_settings target_bundles.
    // @todo
    // $translatableNodeTypes = ['page'];.
    $form[self::FALLBACK_NODE] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_handler' => 'default',
      '#title' => $this->t('Fallback node'),
      '#description' => $this->t('The node for the fallback language(s).'),
      '#default_value' => $node,
      '#states' => [
        'invisible' => [
          ':input[name="' . self::FALLBACK_FLAG . '"]' => ['checked' => FALSE],
        ],
      ],
    ];

    // Exclude redirection for some pages.
    $redirect_request_path_pages = $config->get('redirect.request_path_pages');
    $description = t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", [
      '%blog' => '/blog',
      '%blog-wildcard' => '/blog/*',
      '%front' => '<front>',
    ]);
    $options = [
      $this->t('Every page except the listed pages'),
      $this->t('The listed pages only'),
    ];

    $form['page_redirect']['redirect_request_path_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add redirection to specific pages.'),
      '#options' => $options,
      '#default_value' => $config->get('redirect.request_path_mode'),
    ];

    $form['page_redirect']['redirect_request_path_pages'] = [
      '#type' => 'textarea',
      '#title' => t('Pages'),
      '#title_display' => 'invisible',
      '#default_value' => !empty($redirect_request_path_pages) ? $redirect_request_path_pages : '',
      '#description' => $description,
      '#rows' => 10,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // If excluded languages are being used.
    if (!$form_state->isValueEmpty(self::EXCLUDED_LANGUAGES)) {
      // The default language of the site cannot be redirect to itself.
      $defaultLanguage = \Drupal::service('language_manager')->getDefaultLanguage();
      if (in_array($defaultLanguage->getId(), $form_state->getValue(self::EXCLUDED_LANGUAGES))) {
        $form_state->setErrorByName(self::EXCLUDED_LANGUAGES, t('The default site language @language_id cannot be excluded.', ['@language_id' => $defaultLanguage->getId()]));
      }
    }

    // If language fallback is activated.
    if (!$form_state->isValueEmpty(self::FALLBACK_FLAG)) {

      // Trim some text values.
      $form_state->setValue('redirect_request_path_pages', trim($form_state->getValue('redirect_request_path_pages')));
      // Verify that every path is prefixed with a slash.
      $pages = preg_split('/(\r\n?|\n)/', $form_state->getValue('redirect_request_path_pages'));
      foreach ($pages as $page) {
        if (strpos($page, '/') !== 0 && $page !== '<front>') {
          $form_state->setErrorByName('redirect_request_path_pages', t('Path "@page" not prefixed with slash.', ['@page' => $page]));
          // Drupal forms show one error only.
          break;
        }
      }

      // Check if we have at least one language enabled,
      // otherwise display a warning.
      if ($form_state->getValue(self::FALLBACK_LANGUAGES) === LanguageInterface::LANGCODE_NOT_SPECIFIED) {
        drupal_set_message($this->t('You should select at least one language to enable fallback.'), 'warning');
        // If at least one language is enabled.
      }
      else {
        // If no fallback node were selected.
        if ($form_state->isValueEmpty(self::FALLBACK_NODE)) {
          $form_state->setErrorByName(self::FALLBACK_NODE, t('Define a node for the fallback language(s).'));
          // Finally, if a node was selected, check the available translations
          // compared to the languages that were selected.
        }
        else {
          // @todo use DialectManager.warnUnavailableTranslationForFallback()
          // needs DialectBlock.getFallbackLanguagesLinks() refactoring
        }
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('dialect.shared_block_config')
      ->set(self::EXCLUDED_LANGUAGES, $form_state->getValue(self::EXCLUDED_LANGUAGES))
      ->set(self::FALLBACK_FLAG, $form_state->getValue(self::FALLBACK_FLAG))
      ->set(self::FALLBACK_LANGUAGES, $form_state->getValue(self::FALLBACK_LANGUAGES))
      ->set(self::FALLBACK_NODE, $form_state->getValue(self::FALLBACK_NODE))
      ->set('redirect.request_path_mode', $form_state->getValue('redirect_request_path_mode'))
      ->set('redirect.request_path_pages', $form_state->getValue('redirect_request_path_pages'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
