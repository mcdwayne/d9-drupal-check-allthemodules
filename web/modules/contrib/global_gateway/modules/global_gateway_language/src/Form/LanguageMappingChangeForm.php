<?php

namespace Drupal\global_gateway_language\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\global_gateway\Mapper\MapperPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigEntityFormBase.
 *
 * Typically, we need to build the same form for both adding a new entity,
 * and editing an existing entity.
 */
class LanguageMappingChangeForm extends FormBase {

  protected $languageManager;
  protected $mapper;

  /**
   * {@inheritdoc}
   */
  public function __construct(LanguageManagerInterface $languageManager, MapperPluginManager $mapperManager) {
    $this->languageManager = $languageManager;
    $this->mapper = $mapperManager->createInstance('region_languages');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('plugin.manager.global_gateway.mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'global_gateway_language_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $languages_data = (array) $form_state->getValue('languages');

    $region = \Drupal::routeMatch()->getParameter('region_code');
    $mapping = $this->mapper
      ->setRegion($region)
      ->getEntity();

    $form['region'] = [
      '#type'  => 'hidden',
      '#value' => $region,
    ];

    $form['description'] = [
      '#markup' => $this->t('<p>Languages to region mapping allows you to associate languages with particular regions.</p>'),
    ];

    $wrapper = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'languages-wrap',
      ],
    ];

    $wrapper['languages'] = [
      '#type'   => 'table',
      '#description' => $this->t('List of languages for selected regions'),
      '#header' => [
        'language' => $this->t('Language'),
        'remove'  => $this->t('Remove'),
      ],
      '#tree'   => TRUE,
      '#prefix' => '<div id="languages-list">',
      '#suffix' => '</div>',
      '#empty'  => $this->t('There is no mapped languages yet.'),
    ];

    $wrapper['add_more'] = self::buildAjaxButton(
      'add_more',
      'updateLanguagesCallback',
      'languages-list',
      $this->t('Add Language')
    );
    $wrapper['remove'] = self::buildAjaxButton(
      'remove',
      'updateLanguagesCallback',
      'languages-list',
      $this->t('Remove Language')
    );

    $trigger   = $form_state->getTriggeringElement();
    $add_empty = !empty($trigger) && $trigger['#name'] == 'add_more' ? TRUE : FALSE;

    // Load default settings from config.
    if (empty($languages_data) && !empty($mapping)) {
      $languages_data = $mapping->getLanguages();
    }

    $wrapper['languages'] += $this
      ->buildLanguageRows($languages_data, $add_empty);

    self::processRemoveFormItems(
      $wrapper['languages'],
      $languages_data,
      'code'
    );

    $form['languages_wrap'] = $wrapper;

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
    ];

    return $form;
  }

  /**
   * Returns list of language names from the language manager service.
   */
  public function getLanguageList() {
    $languages = [];
    $language_list = $this->languageManager->getLanguages();
    foreach ($language_list as $lang_code => $language) {
      $languages[$lang_code] = $language->getName();
    }
    return $languages;
  }

  /**
   * Build typical ajax button element array.
   */
  private static function buildAjaxButton($id, $callback, $wrapper, $label) {
    return [
      '#type'   => 'submit',
      '#name'   => $id,
      '#value'  => $label,
      '#submit' => [[self::class, 'rebuildForm']],
      '#ajax'   => [
        'callback' => [self::class, $callback],
        'wrapper'  => $wrapper,
        'effect'   => 'fade',
      ],
      '#attributes' => [
        'class' => [
          'button-action',
          'button--small',
        ],
      ],
    ];
  }

  /**
   * Removes 'remove' form values.
   */
  protected static function processRemoveFormItems(&$elements, $form_data, $property) {
    foreach ($form_data as $key => $item) {
      if (isset($item['remove'])
        && $item['remove']
        && $elements[$key][$property]['#default_value'] == $item[$property]
      ) {
        unset($elements[$key]);
      }
    }
  }

  /**
   * Builds language rows with 'empty' option.
   */
  public function buildLanguageRows($items = [], $add_empty = FALSE) {
    $items = array_filter($items);
    $rows  = [];
    $id    = 0;

    foreach ($items as $item) {
      $id++;
      $rows[$id] = $this->buildLanguageRow($item);
    }

    if ($add_empty) {
      $item = ['code' => 'none'];
      $rows[$id + 1] = $this->buildLanguageRow($item);
    }
    return $rows;
  }

  /**
   * Builds one row with language select.
   */
  public function buildLanguageRow($item) {
    $row = [];

    $row['code'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Language'),
      '#title_display' => 'invisible',
      '#options'       => $this->getLanguageList(),
      '#default_value' => $item['code'] ?: 'none',
    ];

    /*if (self::softDependenciesMeet()) {
    $row['region_code']['#type'] = 'select_icons';
    $row['region_code']['#options_attributes'] = self::getOptionAttributes();
    }*/

    $row['remove'] = [
      '#type'          => 'checkbox',
      '#default_value' => 0,
    ];

    return $row;
  }

  /**
   * Ajax submit handler for "add_more" and "remove" buttons.
   *
   * @param array $form
   *   The entire form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function rebuildForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for "add_more" and "remove" button.
   *
   * @param array $form
   *   The entire form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   Form array.
   */
  public static function updateLanguagesCallback(array &$form, FormStateInterface $form_state) {
    return $form['languages_wrap']['languages'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $region = $form_state->getValue('region');
    $languages = $form_state->getValue('languages');

    foreach ($languages as &$item) {
      unset($item['remove']);
    }

    $languages = array_unique($languages, SORT_REGULAR);
    $languages = array_values($languages);

    $mapping = $this->mapper
      ->setRegion($region)
      ->getEntity();

    if (!$mapping) {
      $mapping = $this->mapper->createEntity([
        'region' => $region,
      ]);
    }

    $mapping->setLanguages($languages);
    $mapping->save();

    $form_state->setRedirect('global_gateway_ui.region', [
      'region_code' => $mapping->id(),
    ]);
  }

}
