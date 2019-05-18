<?php

namespace Drupal\fac\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\fac\SearchPluginManager;

/**
 * Class FacConfigForm.
 *
 * @package Drupal\fac\Form
 */
class FacConfigForm extends EntityForm {

  protected $searchPluginManager;
  protected $entityDisplayRepository;
  protected $entityTypeManager;

  /**
   * Constructor for the FacConfigForm.
   *
   * @param \Drupal\fac\SearchPluginManager $search_plugin_manager
   *   The SearchPluginManager instance.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The EntityDisplayRepository instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManagerInterface instance.
   */
  public function __construct(SearchPluginManager $search_plugin_manager, EntityDisplayRepositoryInterface $entity_display_repository, EntityTypeManagerInterface $entity_type_manager) {
    $this->searchPluginManager = $search_plugin_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.search_plugin'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\fac\Entity\FacConfig $fac_config */
    $fac_config = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $fac_config->label(),
      '#description' => $this->t('Label for the Fast Autocomplete configuration.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $fac_config->id(),
      '#machine_name' => [
        'exists' => [
          $this,
          'exists',
        ],
      ],
      '#disabled' => !$fac_config->isNew(),
    ];

    $form['plugin'] = [
      '#prefix' => '<div id="plugin-subform">',
      '#suffix' => '</div>',
      '#type' => 'fieldset',
      '#title' => $this->t('Plugin'),
      '#tree' => TRUE,
    ];

    $search_plugin_options = [];
    $search_plugins = $this->searchPluginManager->getDefinitions();
    foreach ($search_plugins as $search_plugin) {
      $search_plugin_options[$search_plugin['id']] = $search_plugin['name']->render();
    }

    $search_plugin_id = $fac_config->getSearchPluginId();
    if (!empty($form_state->getValue(['plugin', 'search_plugin_id']))) {
      $search_plugin_id = $form_state->getValue(['plugin', 'search_plugin_id']);
    }

    $form['plugin']['search_plugin_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Search plugin'),
      '#options' => $search_plugin_options,
      '#default_value' => $search_plugin_id,
      '#description' => $this->t('The Search plugin to use in the Fast Autocomplete configuration.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::pluginSelection',
        'wrapper' => 'plugin-subform',
        'event' => 'change',
        'effect' => 'fade',
        'progress' => [
          'message' => $this->t('Loading search plugin options...'),
        ],
      ],
    ];

    if (!empty($search_plugin_id)) {
      $search_plugin = $this->searchPluginManager->createInstance($search_plugin_id);
      $search_plugin_config_form = $search_plugin->getConfigForm($fac_config->getSearchPluginConfig(), $form_state);
      if (!empty($search_plugin_config_form)) {
        $form['plugin']['config'] = $search_plugin_config_form;
        $form['plugin']['config']['#type'] = 'fieldset';
        $form['plugin']['config']['#title'] = $this->t('Plugin configuration');
      }
    }

    $form['behavior'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Behavior'),
    ];

    $form['behavior']['input_selectors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Inputs to enable Fast Autocomplete on'),
      '#description' => $this->t('Enter the jQuery selector(s) for text input elements to enable the Fast Autocomplete functionality on those elements. You can provide multiple selectors divided by commas.'),
      '#default_value' => $fac_config->getInputSelectors(),
      '#attributes' => [
        'placeholder' => $this->t('for example: input.form-search'),
      ],
      '#required' => TRUE,
    ];

    $form['behavior']['number_of_results'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The number of results to return'),
      '#description' => $this->t('Enter the number of results to return.'),
      '#default_value' => !empty($fac_config->getNumberOfResults()) ? $fac_config->getNumberOfResults() : 5,
      '#required' => TRUE,
      '#size' => 2,
    ];

    $form['behavior']['empty_result'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text to show when the search input gets focus and there is no search term in the input.'),
      '#description' => $this->t('Enter the HTML to show when the search input gets focus and there is no search term in the input. Useful for "quick links" for instance.'),
      '#default_value' => $fac_config->getEmptyResult(),
    ];

    $form['behavior']['all_results_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show a "view all results" link'),
      '#description' => $this->t('Enable this option to show a "view all results" link below the suggestions,'),
      '#default_value' => $fac_config->showAllResultsLink(),
    ];

    $form['behavior']['all_results_link_threshold'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum number of suggestions to show "view all results" link'),
      '#description' => $this->t('Enter the minimum number of suggestions to have to show the "view all results" link. Enter "0" to always show the "view all results" link.'),
      '#default_value' => !empty($fac_config->getAllResultsLinkThreshold()) ? $fac_config->getAllResultsLinkThreshold() : 0,
      '#size' => 2,
      '#states' => [
        'visible' => [
          'input[name="all_results_link"]' => [
            'checked' => TRUE,
          ],
        ],
        'required' => [
          'input[name="all_results_link"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $form['behavior']['view_modes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('The View mode to use for rendering each entity type in the result'),
    ];

    $view_modes = $this->entityDisplayRepository->getAllViewModes();
    $selected_view_modes = $fac_config->getViewModes();
    foreach ($view_modes as $entity_type_id => $entity_type) {
      $view_mode_options = ['default' => $this->t('Default')];
      foreach ($entity_type as $id => $view_mode) {
        $view_mode_options[$id] = $view_mode['label'];
      }

      $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type_id);
      $form['behavior']['view_modes']['view_mode_' . $entity_type_id] = [
        '#type' => 'select',
        '#title' => $entity_type_definition->getLabel(),
        '#default_value' => $selected_view_modes[$entity_type_id],
        '#options' => $view_mode_options,
      ];
    }

    $form['behavior']['key_min_length'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The minimum key length to trigger Fast Autocomplete'),
      '#description' => $this->t('Enter the minimum key length to trigger the Fast Autocomplete on an input field. The minimum value is 1.'),
      '#default_value' => !empty($fac_config->getKeyMinLength()) ? $fac_config->getKeyMinLength() : 1,
      '#required' => TRUE,
      '#size' => 2,
    ];

    $form['behavior']['key_max_length'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The maximum key length to trigger Fast Autocomplete'),
      '#description' => $this->t('Enter the maximum key length to trigger the Fast Autocomplete on an input field. The minimum value is 1.'),
      '#default_value' => !empty($fac_config->getKeyMaxLength()) ? $fac_config->getkeyMaxLength() : 10,
      '#required' => TRUE,
      '#size' => 2,
    ];

    $form['behavior']['breakpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Breakpoint'),
      '#description' => $this->t('Enter a minimum width in pixels to disable the Fast Autocomplete behavior until this minimum width is reached. Insert 0 to always enable the Fast Autocomplete behavior.'),
      '#default_value' => !empty($fac_config->getBreakpoint()) ? $fac_config->getBreakpoint() : 0,
      '#size' => 4,
    ];

    $form['behavior']['result_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Result location'),
      '#description' => $this->t('Enter a jQuery selector for a single element that is used to append the results to. If left empty, the results will be appended to the form the input is in.'),
      '#default_value' => $fac_config->getResultLocation(),
    ];

    $form['behavior']['highlighting_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Highlight the keywords in the suggestions list'),
      '#description' => $this->t('Enable this option to highlight the entered keywords in the suggestion list using mark.js.'),
      '#default_value' => $fac_config->highlightingEnabled(),
    ];

    $form['behavior']['anonymous_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Perform search as anonymous user only'),
      '#description' => $this->t('For security reasons you can choose to perform a search as an anonymous user. Otherwise the JSON files in the public files folder might expose information that should be private. If the risk is deemed acceptable this behavior can be disabled and the search will be performed as the current user. The path to the JSON files contains a hash based on the user role that is periodically changed to lower the information exposal risk'),
      '#default_value' => !empty($fac_config->anonymousSearch()) ? $fac_config->anonymousSearch() : TRUE,
    ];

    $form['json_files'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Json files'),
    ];

    $form['json_files']['clean_up_files'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Periodically clean up json files'),
      '#description' => $this->t('Enable cleaning up json files on cron to refresh the contents of the json files that contain the autocomplete suggestions.'),
      '#default_value' => !empty($fac_config->cleanUpFiles()) ? $fac_config->cleanUpFiles() : TRUE,
    ];

    $form['json_files']['files_expiry_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expiry time'),
      '#description' => $this->t('How old do the json files have to be to be considered expired? The value for this field should contain a relative string compared to now like "-1 month" or "-1 day"'),
      '#default_value' => !empty($fac_config->getFilesExpiryTime()) ? $fac_config->getFilesExpiryTime() : '-1 day',
      '#size' => 20,
      '#states' => [
        'visible' => [
          'input[name="clean_up_files"]' => [
            'checked' => TRUE,
          ],
        ],
        'required' => [
          'input[name="clean_up_files"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * AJAX callback on plugin selection.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   *
   * @return array
   *   Part of the form to replace.
   */
  public function pluginSelection(array &$form, FormStateInterface $form_state) {
    return $form['plugin'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\fac\Entity\FacConfig $fac_config */
    $fac_config = $this->entity;
    $fac_config->set('searchPluginId', $form_state->getValue([
      'plugin',
      'search_plugin_id',
    ]));

    $plugin_config = $form_state->getValue(['plugin', 'config']);
    $fac_config->set('searchPluginConfig', json_encode($plugin_config));

    $fac_config->set('inputSelectors', $form_state->getValue('input_selectors'));
    $fac_config->set('numberOfResults', $form_state->getValue('number_of_results'));
    $fac_config->set('emptyResult', $form_state->getValue('empty_result'));
    $fac_config->set('allResultsLink', $form_state->getValue('all_results_link'));
    $fac_config->set('allResultsLinkThreshold', $form_state->getValue('all_results_link_threshold'));
    $fac_config->set('viewMode', $form_state->getValue('view_mode'));
    $view_modes = $this->entityDisplayRepository->getAllViewModes();
    $selected_view_modes = [];
    $entity_type_ids = array_keys($view_modes);
    foreach ($entity_type_ids as $entity_type_id) {
      $selected_view_modes[$entity_type_id] = $form_state->getValue('view_mode_' . $entity_type_id);
    }
    $fac_config->set('viewModes', $selected_view_modes);
    $fac_config->set('keyMinLength', $form_state->getValue('key_min_length'));
    $fac_config->set('keyMaxLength', $form_state->getValue('key_max_length'));
    $fac_config->set('breakpoint', $form_state->getValue('breakpoint'));
    $fac_config->set('resultLocation', $form_state->getValue('result_location'));
    $fac_config->set('highlightingEnabled', $form_state->getValue('highlighting_enabled'));
    $fac_config->set('anonymousSearch', $form_state->getValue('anonymous_search'));
    $fac_config->set('cleanUpFiles', $form_state->getValue('clean_up_files'));
    $fac_config->set('filesExpiryTime', $form_state->getValue('files_expiry_time'));

    $status = $fac_config->save();

    if ($status) {
      $this->messenger()->addStatus($this->t('Saved the %label Example.', [
        '%label' => $fac_config->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('The %label Fast Autocomplete configuration was not saved.', [
        '%label' => $fac_config->label(),
      ]));
    }

    $form_state->setRedirect('entity.fac_config.collection');
  }

  /**
   * Checks if the given id exists.
   *
   * @param string $id
   *   The id to check.
   *
   * @return bool
   *   FALSE if the id does not exist yet. TRUE otherwise.
   */
  public function exists($id) {
    try {
      $entity = $this->entityTypeManager->getStorage('fac_config')->getQuery()
        ->condition('id', $id)
        ->execute();
      return (bool) $entity;
    }
    catch (InvalidPluginDefinitionException $e) {
      return FALSE;
    }
  }

}
