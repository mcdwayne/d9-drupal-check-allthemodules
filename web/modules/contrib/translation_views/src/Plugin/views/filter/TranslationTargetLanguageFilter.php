<?php

namespace Drupal\translation_views\Plugin\views\filter;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\translation_views\TranslationViewsTargetLanguage as TargetLanguage;
use Drupal\views\Plugin\views\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides filtering by translation target language.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("translation_views_target_language")
 */
class TranslationTargetLanguageFilter extends FilterPluginBase implements ContainerFactoryPluginInterface {
  use TargetLanguage;

  /**
   * Flag about module 'translators_content' existence.
   *
   * @var bool
   */
  protected $translators_content = FALSE;
  /**
   * Translators skills service.
   *
   * @var \Drupal\translators\Services\TranslatorsSkills|null
   */
  protected $translatorSkills = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('language_manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, ModuleHandlerInterface $handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->translators_content = $handler->moduleExists('translators_content');
    if ($this->translators_content && \Drupal::hasService('translators.skills')) {
      $this->translatorSkills = \Drupal::service('translators.skills');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);

    unset($form['expose']['multiple']);
    unset($form['expose']['required']);

    if ($this->translators_content) {
      // We need to force this option to allow users to use only the languages,
      // specified as the user's translation skills.
      $form['expose']['reduce']['#default_value'] = TRUE;
      $form['expose']['reduce']['#disabled'] = TRUE;
    }

    $form['expose']['identifier'] = [
      '#type' => 'hidden',
      '#value' => static::$targetExposedKey,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['expose']['contains']['label'] = [
      'default' => $this->t('Target language'),
    ];

    $options['expose']['contains']['identifier'] = [
      'default' => static::$targetExposedKey,
    ];

    $options['value']['default'] = '';
    $options['remove']['default'] = TRUE;
    $options['exposed']['default'] = TRUE;

    if ($this->translators_content) {
      $options['limit'] = ['default' => FALSE];
      $options['column'] = ['default' => ['source' => '', 'target' => 'target']];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    $field =& $form[$this->field];
    // Show empty registered skills message inside this window.
    if ($this->translators_content
      && $this->options['limit']
      && empty($this->translatorSkills->getSkills(NULL, TRUE))) {
        $field['#options'] = ['All' => $this->t('- Any -')];
        $field['#value'] = $field['#default_value'] = 'All';
        $this->translatorSkills->showEmptyMessage();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    if ($this->translators_content) {
      // Remove the values list - we will handle them on a background basis.
      // Only if limited option is checked.
      $form['value']['#states'] = [
        'visible' => [
          'input[name="options[limit]"]' => ['checked' => FALSE],
        ],
      ];
      // Build values list independently in order to see all the options,
      // while switching "limit" option without necessity to reload the form.
      $form['value']['#options'] = $this->listLanguages(
        LanguageInterface::STATE_ALL
        | LanguageInterface::STATE_SITE_DEFAULT
        | PluginBase::INCLUDE_NEGOTIATED
      );

      $end = $form['clear_markup_end'];
      unset($form['clear_markup_end']);
      $form['limit'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Limit target languages by translation skills'),
        '#required'      => FALSE,
        '#default_value' => $this->options['limit'],
      ];
      $form['column'] = [
        '#type'          => 'checkboxes',
        '#options'       => [
          'source' => $this->t('Source languages'),
          'target'   => $this->t('Target languages'),
        ],
        '#title'         => $this->t('Translation skill'),
        '#required'      => FALSE,
        '#default_value' => $this->options['column'],
        '#states' => [
          'visible' => [
            'input[name="options[limit]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['clear_markup_end'] = $end;
      $form['value']['#prefix'] = '<div class="views-group-box views-right-60">';
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    $exposed = $form_state->get('exposed');

    $language_options = $this->buildLanguageOptions();
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();

    if (!empty($this->options['exposed'])) {
      $identifier = $this->options['expose']['identifier'];
      $user_input = $form_state->getUserInput();

      // We need set exposed input when there is no selected value by user yet.
      if ($exposed) {
        if (!isset($this->options['limit'])) {
          $this->options['limit'] = FALSE;
        }
        if (!$this->options['limit']) {
          if (!isset($user_input[$identifier])
            || (isset($user_input[$identifier]) && $user_input[$identifier] === $default_langcode)
          ) {
            $this->setExposedValue($identifier, PluginBase::VIEWS_QUERY_LANGUAGE_SITE_DEFAULT, $form_state);
          }
        }
        elseif (isset($user_input[$identifier]) && !isset($language_options[$user_input[$identifier]])) {
          $this->setExposedValue($identifier, array_keys($language_options)[0], $form_state);
          $this->value = array_keys($language_options)[0];
        }
      }
    }

    $this->always_required = TRUE;

    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Target language'),
      '#options' => $this->buildLanguageOptions(),
      '#multiple' => FALSE,
      '#required' => TRUE,
      '#default_value' => $this->value,
    ];

    $form['expose']['identifier'] = [
      '#type' => 'hidden',
      '#value' => static::$targetExposedKey,
    ];

    if (!$exposed) {
      $form['remove'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Remove rows where source language is equal to target language.'),
        '#default_value' => $this->options['remove'],
        '#weight' => -50,
      ];
    }
  }

  /**
   * Provide options for langcode dropdown.
   *
   * Options are based on configurable languages or site default one.
   */
  protected function buildLanguageOptions() {
    $options = [];
    if ($this->translators_content && $this->options['limit']) {
      $translators_languages = $this->translatorSkills->getSkills(NULL, TRUE);
      // Handle column options.
      foreach ($this->options['column'] as $name => $column) {
        if (!empty($column)) {
          foreach ($translators_languages as $langs) {
            $this->processColumnOption($langs, $name, $options);
          }
        }
      }
      return $options;
    }
    $site_default = $this->languageManager->getDefaultLanguage();
    $options = $this->listLanguages(LanguageInterface::STATE_CONFIGURABLE);

    if (isset($options[$site_default->getId()])) {
      unset($options[$site_default->getId()]);
    }
    return [PluginBase::VIEWS_QUERY_LANGUAGE_SITE_DEFAULT => $site_default->getName()] + $options;
  }

  /**
   * Process column options.
   *
   * @param array $languages
   *   Languages array.
   * @param string $column
   *   Column name.
   */
  protected function processColumnOption(array $languages, $column, &$options) {
    $key = "language_$column";
    if (isset($languages[$key])) {
      $key = $languages[$key];
      $options[$key] = $this->languageManager
        ->getLanguage($key)
        ->getName();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function listLanguages($flags = LanguageInterface::STATE_ALL, array $current_values = NULL) {
    return array_map(function ($language) {
      return (string) $language;
    }, parent::listLanguages($flags, $current_values));
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if ($this->options['remove']) {
      $this->query->addWhere(
        $this->options['group'],
        $this->view->storage->get('base_table') . '.langcode',
        '***TRANSLATION_VIEWS_TARGET_LANG***',
        '<>'
      );
    }
  }

  /**
   * Special setter for exposed value in views.
   */
  protected function setExposedValue($identifier, $value, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    $user_input[$identifier] = $value;

    $form_state->setUserInput($user_input);
    $this->view->setExposedInput($user_input);
  }

}
