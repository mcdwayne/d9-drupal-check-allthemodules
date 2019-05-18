<?php

namespace Drupal\entity_list\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\entity_list\Entity\EntityListInterface;
use Drupal\entity_list\Plugin\EntityListDisplayManager;
use Drupal\entity_list\Plugin\EntityListQueryManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityListForm.
 */
class EntityListForm extends EntityForm {

  protected $bundleInfo;

  protected $entityListQueryManager;

  protected $entityListDisplayManager;

  protected $languageManager;

  /**
   * EntityListForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle info service.
   * @param \Drupal\entity_list\Plugin\EntityListQueryManager $entity_list_query_manager
   *   The entity list query manager.
   * @param \Drupal\entity_list\Plugin\EntityListDisplayManager $entity_list_display_manager
   *   The entity list display manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(EntityTypeBundleInfoInterface $bundle_info, EntityListQueryManager $entity_list_query_manager, EntityListDisplayManager $entity_list_display_manager, LanguageManagerInterface $language_manager) {
    $this->bundleInfo = $bundle_info;
    $this->entityListQueryManager = $entity_list_query_manager;
    $this->entityListDisplayManager = $entity_list_display_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   *
   * Override default create method to inject the cup of tea command service.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.entity_list_query'),
      $container->get('plugin.manager.entity_list_display'),
      $container->get('language_manager')
    );
  }

  /**
   * Return an array representing the tabs and fieldset for convenience.
   *
   * @param \Drupal\entity_list\Entity\EntityListInterface $entity_list
   *   An entity list object.
   *
   * @return array
   *   An array representing the tabs and fieldset.
   */
  protected function getFormTabs(EntityListInterface $entity_list) {
    return [
      'query_details' => [
        '#title' => $this->t('Source'),
        '#description' => $this->t('Select one or more content types to fill the list'),
        'query' => [
          '#title' => $this->t('Query'),
          '#prefix' => '<div id="query-wrapper">',
          '#suffix' => '</div>',
          '#manager' => $this->entityListQueryManager,
          '#get_selected_plugin' => [
            ['query', 'plugin'],
            $entity_list->get('query')['plugin'] ?? 'default_entity_list_query',
          ],
          '#get_settings' => [
            ['query'],
            $entity_list->get('query') ?? [],
          ],
          '#ajax_update' => [
            'query-wrapper' => ['query_details', 'query'],
          ],
        ],
      ],
      'display_details' => [
        '#title' => $this->t('Display'),
        '#description' => $this->t('Manage display settings.'),
        'display' => [
          '#title' => $this->t('Display'),
          '#prefix' => '<div id="display-wrapper">',
          '#suffix' => '</div>',
          '#manager' => $this->entityListDisplayManager,
          '#get_selected_plugin' => [
            ['display', 'plugin'],
            $entity_list->get('display')['plugin'] ?? 'default_entity_list_display',
          ],
          '#get_settings' => [
            ['display'],
            $entity_list->get('display') ?? [],
          ],
          '#ajax_update' => [
            'display-wrapper' => ['display_details', 'display'],
          ],
        ],
      ],
      //      'filter_details' => [
      //        '#title' => $this->t('Filter'),
      //        '#description' => $this->t('Manage filter settings.'),
      //        'filter' => [
      //          '#title' => $this->t('Filter'),
      //          '#prefix' => '<div id="filter-wrapper">',
      //          '#suffix' => '</div>',
      //        ],
      //      ],
    ];
  }

  /**
   * Build the tab plugin.
   *
   * @param array $tab
   *   The tab info from $this->getFormTabs().
   * @param \Drupal\entity_list\Entity\EntityListInterface $entity_list
   *   The current entity list object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   An array representing the vertical tab.
   */
  protected function buildTab(array $tab, EntityListInterface $entity_list, FormStateInterface $form_state) {
    foreach ($tab as $key => &$item) {
      if (strpos($key, '#') !== 0) {
        $item = $this->buildFieldset($item, $entity_list, $form_state);
      }
    }
    $element = [
      '#type' => 'details',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => FALSE,
      '#group' => 'vertical_tabs',
    ];
    return $element + $tab;
  }

  /**
   * Build the fieldset inside the vertical tab.
   *
   * @param array $fieldset
   *   The fieldset info from $this->getFormTabs().
   * @param \Drupal\entity_list\Entity\EntityListInterface $entity_list
   *   The current entity list object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   An array representing the fieldset inside a vertical tab.
   */
  protected function buildFieldset(array $fieldset, EntityListInterface $entity_list, FormStateInterface $form_state) {
    $element = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
    ];
    $element += $fieldset;

    /** @var \Drupal\Core\Plugin\DefaultPluginManager $manager */
    $manager = $fieldset['#manager'] ?? NULL;
    if (!empty($manager)) {
      $plugin_options = [];
      foreach ($manager->getDefinitions() as $key => $plugin) {
        $plugin_options[$key] = $plugin['label'];
      }

      $selected_plugin = call_user_func_array(
        [$form_state, 'getValue'],
        $fieldset['#get_selected_plugin']
      );

      $element['plugin'] = [
        '#type' => 'select',
        '#title' => $this->t('Plugin'),
        '#options' => $plugin_options,
        '#required' => TRUE,
        '#default_value' => $selected_plugin,
        '#ajax' => [
          'callback' => [get_class($this), 'update'],
        ],
        '#ajax_update' => $fieldset['#ajax_update'] ?? [],
      ];
      if (count($plugin_options) < 2) {
        $element['plugin']['#type'] = 'hidden';
      }

      if (!empty($selected_plugin) && $manager->hasDefinition($selected_plugin)) {
        try {
          $instance = $manager->createInstance($selected_plugin, [
            'entity' => $entity_list,
            'settings' => call_user_func_array(
              [$form_state, 'getValue'],
              $fieldset['#get_settings']
            ),
          ]);
        }
        catch (PluginException $e) {
          drupal_set_message($e->getMessage(), 'error');
        }
        if (!empty($instance)) {
          $element += $instance->settingsForm($form_state);
        }
      }

      unset($element['#manager']);
      unset($element['#get_selected_plugin']);
      unset($element['#get_settings']);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\entity_list\Entity\EntityList $entity_list */
    $entity_list = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity_list->label(),
      '#description' => $this->t("Label for the Entity list."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_list->id(),
      '#machine_name' => [
        'exists' => '\Drupal\entity_list\Entity\EntityList::load',
      ],
      '#disabled' => !$entity_list->isNew(),
    ];

    $form['debug'] = [
      '#type' => 'container',
      '#prefix' => '<div id="debug">',
      '#suffix' => '</div>',
    ];

    $form['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-query-details',
    ];

    $tabs = $this->getFormTabs($entity_list);
    foreach ($tabs as $key => $tab) {
      $form[$key] = $this->buildTab($tab, $entity_list, $form_state);
    }

    return $form;
  }

  /**
   * Ajax callback to update a form elements.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public static function update(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $element = $form_state->getTriggeringElement();
    $ajax_update = self::getAjaxUpdate($element, $form);
    foreach ($ajax_update as $id => $path) {
      $response->addCommand(new ReplaceCommand("#$id", self::getFormElementFromPath($path, $form)));
    }
    $response->addCommand(new AppendCommand('#debug', ['#type' => 'status_messages']));
    return $response;
  }

  /**
   * Try to find a property #ajax_update on the triggering element.
   *
   * If the property was not found in the triggering element, try to find it
   * recursively in the parent elements.
   *
   * @param array $element
   *   The triggering element or a parent.
   * @param array $form
   *   The complete form.
   *
   * @return array
   *   The #ajax_update array or an empty array.
   */
  public static function getAjaxUpdate(array $element, array $form) {
    if (!empty($element['#ajax_update'])) {
      return $element['#ajax_update'];
    }
    $parents = array_slice($element['#array_parents'] ?? [], 0, -1);
    if (!empty($parents)) {
      return self::getAjaxUpdate(self::getFormElementFromPath($parents, $form), $form);
    }
    return [];
  }

  /**
   * Helper method to get form element from a path.
   *
   * @param array $path
   *   The path to the form element.
   * @param array $form
   *   An array of the current form element according to the current path
   *   element.
   *
   * @return array
   *   A form element.
   */
  public static function getFormElementFromPath(array $path, array $form) {
    if (!empty($path)) {
      $key = array_shift($path);
      return self::getFormElementFromPath($path, $form[$key]);
    }
    return $form;
  }

  /**
   * Ajax callback to update the display settings.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function ajaxUpdateDisplayPlugin(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#display-wrapper', $form['display_details']['display']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_list = $this->entity;
    $status = $entity_list->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Entity list.', [
          '%label' => $entity_list->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Entity list.', [
          '%label' => $entity_list->label(),
        ]));
    }
    $form_state->setRedirectUrl($entity_list->toUrl('collection'));
  }

}
