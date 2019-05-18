<?php

namespace Drupal\dialect\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dialect\DialectManager;
use Drupal\dialect\Form\SharedBlockConfigForm;
use Drupal\node\Entity\Node;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\language\ConfigurableLanguageManager;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;

/**
 * Provides a 'Dialect language switcher' block.
 *
 * @Block(
 *  id = "dialect_block",
 *  admin_label = @Translation("Dialect language switcher"),
 *  deriver = "Drupal\language\Plugin\Derivative\LanguageBlock"
 * )
 */
class DialectBlock extends BlockBase implements ContainerFactoryPluginInterface {

  const LANGUAGE_DISPLAY_ID     = 'id';
  const LANGUAGE_DISPLAY_NAME   = 'name';
  const CONFIG_LANGUAGE_DISPLAY = 'language_display';

  // @todo remove unused services
  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\language\ConfigurableLanguageManager definition.
   *
   * @var \Drupal\language\ConfigurableLanguageManager
   */
  protected $languageManager;

  /**
   * Drupal\Core\Path\PathMatcher definition.
   *
   * @var \Drupal\Core\Path\PathMatcher
   */
  protected $pathMatcher;

  /**
   * Drupal\Core\Render\Renderer definition.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Drupal\dialect\DialectManager definition.
   *
   * @var \Drupal\dialect\DialectManager
   */
  protected $dialectManager;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configurationFactory;

  /**
   * Immutable configuration shared form a global configuration form.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  private $sharedBlockConfiguration;

  /**
   * List of language links.
   *
   * @var array
   */
  private $languageLinks;

  /**
   * DialectBlock constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity type manager definition.
   * @param \Drupal\language\ConfigurableLanguageManager $language_manager
   *   Language manager definition.
   * @param \Drupal\Core\Path\PathMatcher $path_matcher
   *   Path matcher definition.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer definition.
   * @param \Drupal\dialect\DialectManager $dialect_manager
   *   Dialect Manager definition.
   * @param Drupal\Core\Config\ConfigFactory $configuration_factory
   *   Configuration Factory definition.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManager $entity_type_manager,
                              ConfigurableLanguageManager $language_manager,
                              PathMatcher $path_matcher,
                              Renderer $renderer,
                              DialectManager $dialect_manager,
                              ConfigFactory $configuration_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->pathMatcher = $path_matcher;
    $this->renderer = $renderer;
    $this->dialectManager = $dialect_manager;
    $this->configurationFactory = $configuration_factory;
    // At a later stage, make it editable from the block configuration form
    // \Drupal::service('config.factory')->getEditable('example.settings');.
    $this->sharedBlockConfiguration = $this->configurationFactory->get('dialect.shared_block_config');
    $this->getSystemLanguageSwitchLinks();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_type.manager'), $container->get('language_manager'), $container->get('path.matcher'), $container->get('renderer'), $container->get('dialect.manager'), $container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // Drupal default display name.
      self::CONFIG_LANGUAGE_DISPLAY => self::LANGUAGE_DISPLAY_NAME,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $access = $this->languageManager->isMultilingual() ? AccessResult::allowed() : AccessResult::forbidden();
    return $access->addCacheTags(['config:configurable_language_list']);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Make cacheable in https://www.drupal.org/node/2232375.
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // @todo add link from 'dialect.shared_block_config_form' route
    drupal_set_message($this->t('Fallback language configuration can be edited in the main configuration form (Configuration > Regional and language > Dialect language fallback configuration).'), 'warning');
    $form[self::CONFIG_LANGUAGE_DISPLAY] = [
      '#type' => 'select',
      '#title' => $this->t('Language display'),
      '#description' => $this->t('Label used for the language.'),
      '#options' => [
        self::LANGUAGE_DISPLAY_ID => $this->t('Id (EN)'),
        self::LANGUAGE_DISPLAY_NAME => $this->t('Name (English)'),
      ],
      '#multiple' => FALSE,
      '#default_value' => $this->configuration[self::CONFIG_LANGUAGE_DISPLAY],
      '#weight' => 1,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // @todo consider exposing the SharedBlockConfiguration using an editable configuration
    $this->configuration[self::CONFIG_LANGUAGE_DISPLAY] = $form_state->getValue(self::CONFIG_LANGUAGE_DISPLAY);
  }

  /**
   * Selects the label format from the configuration.
   *
   * @return null|string
   *   Label
   */
  private function getCurrentLanguageLabel() {
    $result = NULL;
    switch ($this->configuration[self::CONFIG_LANGUAGE_DISPLAY]) {
      case self::LANGUAGE_DISPLAY_ID:
        $result = strtoupper($this->languageManager->getCurrentLanguage()
          ->getId());
        break;

      default:
      case self::LANGUAGE_DISPLAY_NAME:
        $result = $this->languageManager->getCurrentLanguage()->getName();
        break;
    }
    return $result;
  }

  /**
   * Sets the initial system language links.
   */
  private function getSystemLanguageSwitchLinks() {
    $route_name = $this->pathMatcher->isFrontPage() ? '<front>' : '<current>';
    $type = $this->getDerivativeId();
    $this->languageLinks = $this->languageManager->getLanguageSwitchLinks($type, Url::fromRoute($route_name));
  }

  /**
   * Optionally set the language id instead of the name.
   */
  private function setLanguageDisplay() {
    if ($this->configuration[self::CONFIG_LANGUAGE_DISPLAY] == self::LANGUAGE_DISPLAY_ID) {
      foreach ($this->languageLinks->links as $key => &$item) {
        $language = $item['language'];
        $item['title'] = strtoupper($key);
        if ($language instanceof ConfigurableLanguage) {
          $language->setName(strtoupper($key));
        }
      }
    }
  }

  /**
   * Returns the default language negotiation links.
   *
   * @return array
   *   Language switcher links
   */
  private function getDefaultLanguagesLinks() {
    $build = [];
    // Default language negotiation.
    $currentLanguageId = $this->dialectManager->getCurrentLanguageId();
    // Set default translation links.
    if (isset($this->languageLinks->links)) {
      // Remove current language from the default languages links.
      // @todo refactoring needed for fallback languages
      unset($this->languageLinks->links[$currentLanguageId]);

      // Remove excluded languages.
      $excludedLanguages = $this->dialectManager->getExcludedLanguageIds();
      if (!empty($excludedLanguages)) {
        foreach ($excludedLanguages as $languageId) {
          unset($this->languageLinks->links[$languageId]);
        }
      }

      // If the current node is a translation fallback node
      // the default links are redirected to the <front> route (@see #2863045).
      $fallbackLanguages = $this->sharedBlockConfiguration->get(SharedBlockConfigForm::FALLBACK_LANGUAGES);
      if (!empty($fallbackLanguages)
        && is_array($fallbackLanguages)
        && in_array($currentLanguageId, $fallbackLanguages)) {
        foreach ($this->languageLinks->links as $langcode => $link) {
          // @todo review params
          $url = Url::fromRoute('<front>');
          $this->languageLinks->links[$langcode]['url'] = $url;
        }
      }

      // Set display option if any.
      $this->setLanguageDisplay();

      $build['default_languages'] = [
        '#theme' => 'links__language_block',
        '#links' => $this->languageLinks->links,
        '#attributes' => [
          'class' => [
            "language-switcher-{$this->languageLinks->method_id}",
          ],
        ],
        '#set_active_class' => TRUE,
      ];
    }

    return $build;
  }

  /**
   * Returns from the configuration the fallback languages if any.
   *
   * @return array
   *   Fallback languages links
   */
  private function getFallbackLanguagesLinks() {
    $currentLanguageId = $this->dialectManager->getCurrentLanguageId();
    $fallbackLanguages = $this->sharedBlockConfiguration->get(SharedBlockConfigForm::FALLBACK_LANGUAGES);
    $fallbackNodeId = $this->sharedBlockConfiguration->get(SharedBlockConfigForm::FALLBACK_NODE);

    // Set fallback translation links
    // and unset default translation links if any.
    $fallbackLinksList = [];
    if (!empty($fallbackLanguages)) {
      // @todo refactor to allow standalone warnUnavailableTranslationForFallback()
      // Fallback node id test is done by the validator.
      $fallbackNode = $this->entityTypeManager->getStorage('node')->load((int) $fallbackNodeId);
      if ($fallbackNode instanceof Node) {
        $fallbackLinks = [];
        $translatedFallbackNodes = [];
        $untranslatedFallbackNodeLanguages = [];
        // Get the available translations for this node.
        foreach ($fallbackNode->getTranslationLanguages() as $languageId => $languageName) {
          $translatedNode = $fallbackNode->getTranslation($languageId);
          $translatedFallbackNodes[$languageId] = $translatedNode;
        }
        // Compare available translations with the desired fallback
        // defined in the block configuration.
        foreach ($fallbackLanguages as $languageId) {
          // Remove the default link in every case,
          // so we make sure that if a language has no node translation
          // it is not displayed in the language switcher.
          // (@see #2863047).
          unset($this->languageLinks->links[$languageId]);
          if (isset($translatedFallbackNodes[$languageId])) {
            // Do not repeat it, the current language is still displayed
            // as selected via the current language label.
            if ($languageId !== $currentLanguageId) {
              $title = $translatedFallbackNodes[$languageId]->getTitle();
              $url = $translatedFallbackNodes[$languageId]->toUrl('canonical');
              $link = Link::fromTextAndUrl($title, $url);
              $link = $link->toRenderable();
              $link['#attributes'] = ['class' => ['fallback-link']];
              $fallbackLinks[$languageId] = $this->renderer->render($link);
            }
          }
          else {
            // $untranslatedFallbackNodeLanguages[] = $languageId;.
            $this->dialectManager->unavailableFallbackTranslationsWarning();
          }
        }

        if (!empty($fallbackLinks)) {
          $fallbackLinksList = [
            'fallback_links' => [
              '#theme' => 'item_list',
              '#type' => 'ul',
              '#items' => $fallbackLinks,
              '#attributes' => [
                'class' => [
                  "language-switcher-fallback",
                ],
              ],
              '#set_active_class' => TRUE,
              '#wrapper_attributes' => [
                'class' => [
                  'dialect__fallback',
                ],
              ],
            ],
          ];
        }
      }
    }

    return $fallbackLinksList;
  }

  /**
   * Checks from the configuration if fallback languages is enabled.
   *
   * @return bool
   *   Single node fallback
   */
  private function hasFallbackLanguages() {
    return $this->sharedBlockConfiguration->get(
      SharedBlockConfigForm::FALLBACK_FLAG
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // @todo refactoring needed
    // getFallbackLanguagesLinks has a side effect of
    // removing the fallback language from the default languages
    // and thus works only in this method call sequence.
    $fallbackLanguages = $this->getFallbackLanguagesLinks();
    $defaultLanguages = $this->getDefaultLanguagesLinks();
    $build = [
      // @todo review theme impl of default languages and fallback languages
      // should allow to merge list items easily
      '#theme' => 'dialect',
      '#current_language_id' => $this->languageManager->getCurrentLanguage()->getId(),
      '#current_language_label' => $this->getCurrentLanguageLabel(),
      '#default_languages' => $defaultLanguages,
      '#has_fallback_languages' => $this->hasFallbackLanguages(),
      '#fallback_languages' => $fallbackLanguages,
    ];
    return $build;
  }

}
