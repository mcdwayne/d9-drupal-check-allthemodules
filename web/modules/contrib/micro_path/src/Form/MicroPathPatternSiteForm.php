<?php

namespace Drupal\micro_path\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\micro_site\Entity\Site;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\pathauto\AliasTypeManager;
use Drupal\sitemap\Form\SitemapSettingsForm;
use Drupal\system\Entity\Menu;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class SiteMapForm.
 */
class MicroPathPatternSiteForm extends FormBase {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The micro site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The alias type manager.
   *
   * @var \Drupal\pathauto\AliasTypeManager
   */
  protected $aliasTypeManager;

  /**
   * The settings data saved on the micro site entity.
   *
   * @var array
   */
  protected $data;

  /**
   * MicroPathPatternSiteForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $negotiator
   *   The micro site negotiator.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\pathauto\AliasTypeManager $alias_type_manager
   *   The plugin alias type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, SiteNegotiatorInterface $negotiator, EntityTypeBundleInfoInterface $entity_type_bundle_info, LanguageManagerInterface $language_manager, AliasTypeManager $alias_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->negotiator = $negotiator;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->languageManager = $language_manager;
    $this->aliasTypeManager = $alias_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('micro_site.negotiator'),
      $container->get('entity_type.bundle.info'),
      $container->get('language_manager'),
      $container->get('plugin.manager.alias_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'micro_path_pattern_site_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SiteInterface $site = NULL) {
    if (!$site instanceof SiteInterface) {
      $form = [
        '#type' => 'markup',
        '#markup' => $this->t('Path Pattern site settings is only available in a micro site context.'),
      ];
      return $form;
    }
    $this->data = $site->getData('micro_path');

    $form['site_id'] = [
      '#type' => 'value',
      '#value' => $site->id(),
    ];

    $form['summary'] = [
      '#type' => 'details',
      '#title' => $this->t('Summary of existing patterns'),
      '#open' => FALSE,
    ];

    $form['summary']['table'] = $this->getTablePatterns($site);

    $content_entity_types = $this->getContentEntityType();
    foreach ($content_entity_types as $id => $label) {
      $form[$id] = [
        '#type' => 'details',
        '#title' => $label,
        '#open' => TRUE,
        '#tree' => TRUE,
        '#prefix' => '<div id="wrapper-' . $id . '">',
        '#suffix' => '</div>',
      ];

      $count = $form_state->get('count_' . $id);
      if ($count === NULL) {
        $patterns = !empty($this->data[$id]) ? $this->data[$id] : [];
        $count = count($patterns);
        $form_state->set('count_' . $id, $count);
      }

      $form[$id]['patterns'] = [
        '#type' => 'container',
        '#open' => TRUE,
        '#prefix' => '<div id="patterns-' . $id . '">',
        '#suffix' => '</div>',
      ];

      $data_values = !empty($this->data[$id]) ? $this->data[$id] : [];
      $default_values = !empty($form_state->getUserInput()[$id]['patterns']) ? $form_state->getUserInput()[$id]['patterns'] : $data_values;

      for ($i = 0; $i < $count; $i++) {

        $form[$id]['patterns'][$i] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Pattern #@i', ['@i' => $i]),
          '#attributes' => [
            'class' => ['pattern-wrapper'],
          ],
          '#tree' => TRUE,
        ];

        $form[$id]['patterns'][$i]['bundle'] = [
          '#type' => 'select',
          '#title' => $this->t('Bundle'),
          '#description' => t('Select the bundle on which apply the pattern'),
          '#options' => $this->getBundles($id, $site),
          '#default_value' => $default_values[$i]['bundle'] ?: '',
          '#required' => TRUE,
        ];

        $form[$id]['patterns'][$i]['langcode'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Language'),
          '#options' => $this->getLanguages(),
          '#description' => $this->t('Leave empty to apply the pattern for all language'),
          '#default_value' => isset($default_values[$i]['langcode']) ? $default_values[$i]['langcode'] : [],
        ];

        $form[$id]['patterns'][$i]['pattern'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Pattern'),
          '#description' => $this->t('The pattern to apply'),
          '#default_value' => $default_values[$i]['pattern'] ?: '',
          '#size' => 65,
          '#maxlength' => 1280,
          '#element_validate' => array('token_element_validate', 'micro_path_pattern_validate'),
          '#after_build' => array('token_element_validate'),
          '#token_types' => [$id],
          '#min_tokens' => 1,
          '#required' => TRUE,
        ];

        $form[$id]['patterns'][$i]['delete'] = [
          '#type' => 'submit',
          '#value' => $this->t('Delete pattern'),
          '#name' => 'op-delete-pattern-' . $id . '-'  . $i,
          '#submit' => ['::deleteCallbackPatterns'],
          '#ajax' => [
            'callback' => '::addMoreCallbackPatterns',
            'wrapper' => 'patterns-' . $id,
          ],
          '#attributes' => [
            'data-delete-id' => $i,
            'data-id' => $id,
            'class' => ['button--border', 'button--danger'],
          ]
        ];

      }

      // Show the token help relevant to this pattern type.
      $form[$id]['token_help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [$id],
      ];

//
//      $form[$id]['add_more'] = [
//        '#type' => 'container',
//      ];

      $form[$id]['actions'] = [
        '#type' => 'actions',
      ];
      $form[$id]['actions']['add'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add a pattern @label', ['@label' => $label]),
        '#name' => 'op-add-pattern-' . $id,
        '#submit' => ['::addCallbackPatterns'],
        '#ajax' => [
          'callback' => '::addMoreCallbackPatterns',
          'wrapper' => 'patterns-' . $id,
        ],
        '#attributes' => [
          'data-id' => $id,
        ]
      ];

    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    // By default, render the form using system-config-form.html.twig.
    $form['#theme'] = 'system_config_form';
    $form['#attached']['library'][] = 'micro_path/admin';

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $site_id = $form_state->getValue('site_id');
    $site = Site::load($site_id);
    if (!$site instanceof SiteInterface) {
      $form_state->setError($form, $this->t('An error occurs. Impossible to find the site entity.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $site_id = $form_state->getValue('site_id');
    $site = Site::load($site_id);
    if (!$site instanceof SiteInterface) {
      return;
    }
    $values = $form_state->getValues();
    $data = $this->getData($values);
    $site->setData('micro_path', $data);
    $site->save();

  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addMoreCallbackPatterns(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getTriggeringElement()['#attributes']['data-id'];
    return $form[$id]['patterns'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addCallbackPatterns(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getTriggeringElement()['#attributes']['data-id'];
    $count = $form_state->get('count_' . $id);
    $add_button = $count + 1;
    $form_state->set('count_' . $id, $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "delete" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function deleteCallbackPatterns(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getTriggeringElement()['#attributes']['data-id'];
    $i = $form_state->getTriggeringElement()['#attributes']['data-delete-id'];
    $count = $form_state->get('count_' . $id);
    $user_input = $form_state->getUserInput();
    unset($user_input[$id]['patterns'][$i]);
    // Rebase the keys;
    $user_input[$id]['patterns'] = array_values($user_input[$id]['patterns']);
    $form_state->setUserInput($user_input);
    $form_state->set('count_' . $id, $count - 1);
    $form_state->setRebuild();
  }

  protected function getData($values) {
    $data = [];
    $entity_type_ids = $this->getContentEntityType(FALSE);
    foreach ($entity_type_ids as $entity_type_id) {
      if (isset($values[$entity_type_id])) {
        if (!isset($values[$entity_type_id]['patterns'])) {
          continue;
        }

        foreach ($values[$entity_type_id]['patterns'] as $key => $value) {
          unset($values[$entity_type_id]['patterns'][$key]['delete']);
          $values[$entity_type_id]['patterns'][$key]['langcode'] = array_filter($values[$entity_type_id]['patterns'][$key]['langcode']);
        }
        $data[$entity_type_id] = $values[$entity_type_id]['patterns'];
      }
    }

    return $data;
  }

  /**
   * Get the current language id.
   *
   * @return string
   */
  protected function getCurrentLanguageId() {
    return $this->languageManager->getCurrentLanguage()->getId();
  }

  /**
   * Get an array of languages available.
   *
   * @param bool $use_label
   *
   * @return array
   */
  protected function getLanguages($use_label = TRUE) {
    $options = [];
//    if (!$this->languageManager->isMultilingual()) {
//      return $options;
//    }
    $languages = $this->languageManager->getLanguages();
    foreach ($languages as $language) {
      $options[$language->getId()] = $use_label ? $language->getName() : $language->getId();
    }
    return $options;
  }

  /**
   * Get an array of content entity type.
   *
   * @param bool $use_label
   *
   * @return array
   */
  protected function getContentEntityType($use_label = TRUE) {
    $options = [];
    $content_entity_types = array_filter($this->entityTypeManager->getDefinitions(), function ($entity_type) {
      return $entity_type->getGroup() === 'content';
    });

    foreach ($content_entity_types as $key => $content_entity_type) {
      $options[$content_entity_type->id()] = $use_label ? $content_entity_type->getLabel() : $content_entity_type->id();
    }

    // @TODO Currently we allow only to override pattern for node
    // because taxonomy term can be shared between micro site et it's more
    // complex to handle.
    $allowed_entity_types = [
      'node' => 'node',
//      'taxonomy_term' => 'taxonomy_term',
    ];
    $options = array_intersect_key($options, $allowed_entity_types);
    return $options;
  }

  /**
   * Get the bundle for a given entity type id.
   *
   * @param $entity_type_id
   *
   * @return array
   */
  protected function getBundles($entity_type_id, SiteInterface $site) {
    $options = [];
    $entity_type_bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    foreach ($entity_type_bundles as $key => $entity_type_bundle) {
      $options[$key] = $entity_type_bundle['label'];
    }
    /** @var \Drupal\micro_site\Entity\SiteTypeInterface $site_type */
    $site_type = $site->type->entity;
    switch ($entity_type_id) {
      case 'node':
        $bundles = $site_type->getTypes();
        break;
      case 'taxonomy_term':
        $bundles = $site_type->getVocabularies();
        break;
      default:
        $bundles = [];
    }
    $options = array_intersect_key($options, $bundles);
    return $options;
  }

  protected function getTablePatterns(SiteInterface $site) {
    $element = [
      '#type' => 'table',
      '#header' => $this->getHeaderPatterns(),
      '#empty' => $this->t('There are no global patterns yet.'),
    ];

    $patterns = $this->entityTypeManager->getStorage('pathauto_pattern')->loadMultiple();
    /* @var \Drupal\pathauto\PathautoPatternInterface $entity */
    foreach ($patterns as $entity) {
      // Dot not display pattern set for other site type.
      $conditions = $entity->getSelectionConditions();
      foreach ($conditions as $id => $condition) {
        if ($condition->getPluginId() == 'site_type') {
          $configuration = $condition->getConfiguration();
          if (!empty($configuration['site_type']) && !in_array($site->bundle(), $configuration['site_type'])) {
            continue 2;
          }
        }
      }
      $row = $this->getRowPatterns($entity);
      if (isset($row['label'])) {
        $row['label'] = ['#markup' => $row['label']];
      }
      $element[$entity->id()] = $row;
    }

    return $element;

  }

  protected function getHeaderPatterns() {
    $header = [];
    $header['label'] = $this->t('Label');
    $header['pattern'] = $this->t('Pattern');
    $header['type'] = $this->t('Pattern type');
    $header['conditions'] = $this->t('Conditions');
    return $header;
  }

  public function getRowPatterns($entity) {
    /* @var \Drupal\pathauto\PathautoPatternInterface $entity */
    $row['label'] = $entity->label();
    $row['patern']['#markup'] = $entity->getPattern();
    $row['type']['#markup'] = $entity->getAliasType()->getLabel();
    $row['conditions']['#theme'] = 'item_list';
    foreach ($entity->getSelectionConditions() as $condition) {
      $row['conditions']['#items'][] = $condition->summary();
    }
    return $row;
  }



}
