<?php

namespace Drupal\widget_engine_entity_form\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\entity_browser\Plugin\Field\FieldWidget\EntityReferenceBrowserWidget;

/**
 * Plugin implementation of the 'entity_reference' widget for entity browser.
 *
 * @FieldWidget(
 *   id = "widget_entity_browser_entity_reference",
 *   label = @Translation("Widget entity browser"),
 *   description = @Translation("Uses entity browser to select entities."),
 *   multiple_values = TRUE,
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class WidgetEngineEntityReferenceBrowserWidget extends EntityReferenceBrowserWidget {

  /**
   * The depth of the delete button.
   *
   * This property exists so it can be changed if subclasses.
   *
   * @var int
   */
  protected static $deleteDepth = 5;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'placeholder' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $placeholder_default_value = $this->getSetting('placeholder');
    $element['placeholder'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Text placeholder'),
      '#description' => $this->t('This text will be show when field is empty.'),
      '#format' => !empty($placeholder_default_value) ? $placeholder_default_value['format'] : filter_default_format(),
      '#default_value' => !empty($placeholder_default_value) ? $placeholder_default_value['value'] : '',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity_type = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
    $entities = $this->formElementEntities($items, $element, $form_state);

    // Get correct ordered list of entity IDs.
    $ids = array_map(
      function (EntityInterface $entity) {
        return $entity->id();
      },
      $entities
    );

    // We store current entity IDs as we might need them in future requests. If
    // some other part of the form triggers an AJAX request with
    // #limit_validation_errors we won't have access to the value of the
    // target_id element and won't be able to build the form as a result of
    // that. This will cause missing submit (Remove, Edit, ...) elements, which
    // might result in unpredictable results.
    $form_state->set(['entity_browser_widget', $this->getFormStateKey($items)], $ids);

    // Set placeholder text to form state storage.
    $form_state->set('placeholder', $this->getSetting('placeholder'));

    $hidden_id = Html::getUniqueId('edit-' . $this->fieldDefinition->getName() . '-target-id');
    $details_id = Html::getUniqueId('edit-' . $this->fieldDefinition->getName());

    $element += [
      '#id' => $details_id,
      '#type' => 'details',
      '#open' => !empty($entities) || $this->getSetting('open'),
      '#required' => $this->fieldDefinition->isRequired(),
      // We are not using Entity browser's hidden element since we maintain
      // selected entities in it during entire process.
      'target_id' => [
        '#type' => 'hidden',
        '#id' => $hidden_id,
        // We need to repeat ID here as it is otherwise skipped when rendering.
        '#attributes' => [
          'id' => $hidden_id,
          'class' => 'widgets-list-wrapper',
        ],
        '#default_value' => implode(' ', array_map(
          function (EntityInterface $item) {
            return $item->getEntityTypeId() . ':' . $item->id();
          },
          $entities
        )),
        // #ajax is officially not supported for hidden elements but if we
        // specify event manually it works.
        '#ajax' => [
          'callback' => [get_class($this), 'updateWidgetCallback'],
          'wrapper' => $details_id,
          'event' => 'entity_browser_value_updated',
        ],
      ],
    ];

    // Get configuration required to check entity browser availability.
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $selection_mode = $this->getSetting('selection_mode');

    // Enable entity browser if requirements for that are fulfilled.
    if (EntityBrowserElement::isEntityBrowserAvailable($selection_mode, $cardinality, count($ids))) {
      $element['entity_browser'] = [
        '#type' => 'entity_browser',
        '#entity_browser' => $this->getSetting('entity_browser'),
        '#cardinality' => $cardinality,
        '#selection_mode' => $selection_mode,
        '#default_value' => $entities,
        '#entity_browser_validators' => ['entity_type' => ['type' => $entity_type]],
        '#custom_hidden_id' => $hidden_id,
        '#process' => [
          ['\Drupal\entity_browser\Element\EntityBrowserElement', 'processEntityBrowser'],
          [get_called_class(), 'processEntityBrowser'],
        ],
      ];

    }
    $element['#attached']['library'][] = 'entity_browser/entity_reference';
    $element['#attached']['library'][] = 'widget_engine_entity_form/entity_browser_widget_preview';
    $element['#attached']['drupalSettings']['tokens'] = [
      'token_preview' => \Drupal::csrfToken()->get('widgetTokenPreview'),
      'token_save' => \Drupal::csrfToken()->get('widgetTokenPreviewSave'),
    ];
    $element['#attributes']['class'][] = 'widgets-list-wrapper';

    $field_parents = $element['#field_parents'];

    $element['current'] = $this->displayCurrentSelection($details_id, $field_parents, $entities, $form_state->get('langcode'));

    return $element;
  }

  /**
   * Render API callback: Processes the entity browser element.
   */
  public static function processEntityBrowser(&$element, FormStateInterface $form_state, &$complete_form) {
    $uuid = key($element['#attached']['drupalSettings']['entity_browser']);

    if (empty($element['#default_value'])) {
      $storage = $form_state->getStorage();
      $placeholder = $storage['placeholder'];
      $element['placeholder'] = [
        '#type' => 'item',
        '#title' => '',
        '#markup' => check_markup($placeholder['value'], $placeholder['format']),
        '#weight' => -1,
      ];
    }
    $element['#attached']['drupalSettings']['entity_browser'][$uuid]['selector'] = '#' . $element['#custom_hidden_id'];
    // Update entity browser controls.
    $element['entity_browser']['#attributes'] = [
      'class' => ['eb-main-controls'],
    ];
    $element['entity_browser']['open_modal']['#attributes']['class'] = [
      'open-modal-main',
    ];
    // Change label for basic button.
    $element['entity_browser']['open_modal']['#value'] = t('Select widgets');
    // Add new button "Create new widget".
    $button = $element['entity_browser']['open_modal'];
    $callback_class = get_called_class();
    if (!empty($form_state->getStorage()['form_display'])) {
      $form_display = $form_state->getStorage()['form_display'];
      if (!empty($form_display->getPluginCollections()['widgets'])) {
        $plugin_collections = $form_display->getPluginCollections()['widgets'];
        if ($plugin_collections->has('widget_entity_browser_entity_reference')) {
          $callback_class = $plugin_collections->get('widget_entity_browser_entity_reference');
        }
      }
    }

    $button['#ajax']['callback'] = [$callback_class, 'openModal'];
    $element['entity_browser']['open_modal_add'] = $button;
    $element['entity_browser']['open_modal_add']['#attributes']['class'] = [
      'open-modal-add-main',
    ];
    $element['entity_browser']['open_modal_add']['#weight'] = -1;
    $element['entity_browser']['open_modal_add']['#value'] = t('Add a new widget');

    // Get entity browser element.
    $parents = $element['#array_parents'];
    $copy_form = &$complete_form;
    foreach ($parents as $parent) {
      if ($parent == 'entity_browser') {
        break;
      }
      $copy_form = &$copy_form[$parent];
    }

    if ($element['#default_value']) {
      // Create new group of controls 'Secondary'.
      $copy_form['secondary_controls'] = [
        '#theme_wrappers' => ['container'],
        '#attributes' => [
          'class' => ['eb-secondary-controls'],
        ],
        'open_modal_add_secondary' => [
          '#type' => 'button',
          '#value' => $element['entity_browser']['open_modal_add']['#value'],
          '#attributes' => [
            'class' => ['open-modal-add-secondary'],
          ],
        ],
        'open_modal_secondary' => [
          '#type' => 'button',
          '#value' => $element['entity_browser']['open_modal']['#value'],
          '#attributes' => [
            'class' => [
              'open-modal-secondary',
            ],
          ],
        ],

      ];
    }

    return $element;
  }

  /**
   * Helper function for getting configs by entity browser machine name.
   *
   * @param string $entity_browser_name
   *   Machine name of entity browser.
   *
   * @return array
   *   Array with configs for provided entity browser by its name.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function getEntityBrowserConfig($entity_browser_name) {
    $display_settings = [
      'width' => '',
      'height' => '',
      'link_text' => t('Select widgets'),
    ];

    $configs = \Drupal::entityTypeManager()
      ->getStorage('entity_browser')
      ->load($entity_browser_name);

    if ($configs) {
      $display_settings = $configs->display_configuration;
    }

    return $display_settings;
  }

  /**
   * Generates the content and opens the modal.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function openModal(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#parents'];
    array_pop($parents);
    $parents = array_merge($parents, ['path']);
    $input = $form_state->getUserInput();
    $src = NestedArray::getValue($input, $parents);
    $src .= '&add_tab=true';

    $field_name = $triggering_element['#parents'][0];
    $element_name = $form[$field_name]['widget']['entity_browser']['#entity_browser'];
    $name = 'entity_browser_iframe_' . $element_name;
    $settings = static::getEntityBrowserConfig($element_name);
    $content = [
      '#prefix' => '<div class="ajax-progress-throbber"></div>',
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'src' => $src,
        'class' => 'entity-browser-modal-iframe',
        'width' => '100%',
        'height' => $settings['height'] - 90,
        'frameborder' => 0,
        'style' => 'padding:0; position:relative; z-index:10002;',
        'name' => $name,
        'id' => $name,
      ],
    ];
    $html = drupal_render($content);

    $response = new AjaxResponse();
    $response->addCommand(new OpenDialogCommand('#' . Html::getUniqueId($field_name . '-' . $element_name . '-dialog'), $settings['link_text'], $html, [
      'width' => 'auto',
      'height' => 'auto',
      'modal' => TRUE,
      'maxWidth' => $settings['width'],
      'maxHeight' => $settings['height'],
      'fluid' => 1,
      'autoResize' => 0,
      'resizable' => 0,
    ]));
    return $response;
  }

  /**
   * AJAX form callback.
   */
  public static function updateWidgetCallback(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    // AJAX requests can be triggered by hidden "target_id" element when
    // entities are added or by one of the "Remove" buttons. Depending on that
    // we need to figure out where root of the widget is in the form structure
    // and use this information to return correct part of the form.
    if (!empty($trigger['#ajax']['event']) && $trigger['#ajax']['event'] == 'entity_browser_value_updated') {
      $parents = array_slice($trigger['#array_parents'], 0, -1);
    }
    elseif ($trigger['#type'] == 'submit' && strpos($trigger['#name'], '_remove_')) {
      $parents = array_slice($trigger['#array_parents'], 0, -static::$deleteDepth);
    }

    return NestedArray::getValue($form, $parents);
  }

  /**
   * Submit callback for remove buttons.
   */
  public static function removeItemSubmit(&$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element['#attributes']['data-entity-id']) && isset($triggering_element['#attributes']['data-row-id'])) {
      $id = $triggering_element['#attributes']['data-entity-id'];
      $row_id = $triggering_element['#attributes']['data-row-id'];
      $parents = array_slice($triggering_element['#parents'], 0, -static::$deleteDepth);
      $array_parents = array_slice($triggering_element['#array_parents'], 0, -static::$deleteDepth);

      // Find and remove correct entity.
      $values = explode(' ', $form_state->getValue(array_merge($parents, ['target_id'])));
      foreach ($values as $index => $item) {
        if ($item == $id && $index == $row_id) {
          array_splice($values, $index, 1);

          break;
        }
      }
      $target_id_value = implode(' ', $values);

      // Set new value for this widget.
      $target_id_element = &NestedArray::getValue($form, array_merge($array_parents, ['target_id']));
      $form_state->setValueForElement($target_id_element, $target_id_value);
      NestedArray::setValue($form_state->getUserInput(), $target_id_element['#parents'], $target_id_value);

      // Rebuild form.
      $form_state->setRebuild();
    }
  }

  /**
   * Builds the render array for displaying the current results.
   *
   * @param string $details_id
   *   The ID for the details element.
   * @param string[] $field_parents
   *   Field parents.
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   Array of referenced entities.
   *
   * @return array
   *   The render array for the current selection.
   */
  protected function displayCurrentSelection($details_id, $field_parents, $entities, $langcode = NULL) {

    $field_widget_display = $this->fieldDisplayManager->createInstance(
      $this->getSetting('field_widget_display'),
      $this->getSetting('field_widget_display_settings') + ['entity_type' => $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type')]
    );

    return [
      '#theme_wrappers' => ['container'],
      '#attributes' => ['class' => ['entities-list']],
      'items' => array_map(
        function (ContentEntityInterface $entity, $row_id) use ($field_widget_display, $details_id, $field_parents, $langcode) {
          $display = $field_widget_display->view($entity);
          $edit_button_access = $this->getSetting('field_widget_edit');
          if ($entity->getEntityTypeId() == 'file') {
            // On file entities, the "edit" button shouldn't be visible unless
            // the module "file_entity" is present, which will allow them to be
            // edited on their own form.
            $edit_button_access &= $this->moduleHandler->moduleExists('file_entity');
          }
          if (is_string($display)) {
            $display = ['#markup' => $display];
          }
          return [
            '#theme_wrappers' => ['container'],
            '#attributes' => [
              'class' => ['item-container', Html::getClass($field_widget_display->getPluginId())],
              'data-entity-id' => $entity->getEntityTypeId() . ':' . $entity->id(),
              'data-row-id' => $row_id,
            ],
            'display' => $display,
            'operations' => [
              '#theme_wrappers' => ['container'],
              '#attributes' => [
                'class' => ['widget-actions'],
              ],
              'remove_button' => [
                '#type' => 'submit',
                '#value' => $this->t('Remove'),
                '#ajax' => [
                  'callback' => [get_class($this), 'updateWidgetCallback'],
                  'wrapper' => $details_id,
                ],
                '#submit' => [[get_class($this), 'removeItemSubmit']],
                '#name' => $this->fieldDefinition->getName() . '_remove_' . $entity->id() . '_' . $row_id . '_' . md5(json_encode($field_parents)),
                '#limit_validation_errors' => [array_merge($field_parents, [$this->fieldDefinition->getName()])],
                '#attributes' => [
                  'data-entity-id' => $entity->getEntityTypeId() . ':' . $entity->id(),
                  'data-row-id' => $row_id,
                ],
                '#access' => (bool) $this->getSetting('field_widget_remove'),
              ],
              'edit_button' => [
                '#type' => 'submit',
                '#value' => $this->t('Edit'),
                '#ajax' => [
                  'url' => Url::fromRoute(
                    'entity_browser.edit_form', [
                      'entity_type' => $entity->getEntityTypeId(),
                      'entity' => $entity->id(),
                    ]
                  ),
                  'options' => [
                    'query' => [
                      'details_id' => $details_id,
                      'langcode' => $langcode,
                    ],
                  ],
                ],
                '#access' => $edit_button_access,
              ],
            ],
          ];
        },
        $entities,
        empty($entities) ? [] : range(0, count($entities) - 1)
      ),
    ];
  }

  /**
   * Determines the entities used for the form element.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field item to extract the entities from.
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The list of entities for the form element.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function formElementEntities(FieldItemListInterface $items, array $element, FormStateInterface $form_state) {
    $entities = [];
    $entity_type = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
    $entity_storage = $this->entityTypeManager->getStorage($entity_type);

    // Find IDs from target_id element (it stores selected entities in form).
    // This was added to help solve a really edge casey bug in IEF.
    if (($target_id_entities = $this->getEntitiesByTargetId($element, $form_state)) !== FALSE) {
      return $target_id_entities;
    }

    // Determine if we're submitting and if submit came from this widget.
    $is_relevant_submit = FALSE;
    $trigger = $form_state->getTriggeringElement();
    // Can be triggered by hidden target_id element or "Remove" button.
    if ($trigger &&
      (end($trigger['#parents']) === 'target_id' || (end($trigger['#parents']) === 'remove_button'))) {
      $is_relevant_submit = TRUE;

      // In case there are more instances of this widget on the same page we
      // need to check if submit came from this instance.
      $field_name_key = end($trigger['#parents']) === 'target_id' ? 2 : static::$deleteDepth + 1;
      $field_name_key = count($trigger['#parents']) - $field_name_key;
      $is_relevant_submit &= ($trigger['#parents'][$field_name_key] === $this->fieldDefinition->getName()) &&
        (array_slice($trigger['#parents'], 0, count($element['#field_parents'])) == $element['#field_parents']);
    }

    if ($is_relevant_submit) {
      // Submit was triggered by hidden "target_id" element when entities were
      // added via entity browser.
      if (!empty($trigger['#ajax']['event']) && $trigger['#ajax']['event'] == 'entity_browser_value_updated') {
        $parents = $trigger['#parents'];
      }
      // Submit was triggered by one of the "Remove" buttons. We need to walk
      // few levels up to read value of "target_id" element.
      elseif ($trigger['#type'] == 'submit' && strpos($trigger['#name'], $this->fieldDefinition->getName() . '_remove_') === 0) {
        $parents = array_merge(array_slice($trigger['#parents'], 0, -static::$deleteDepth), ['target_id']);
      }

      if (isset($parents) && $value = $form_state->getValue($parents)) {
        $entities = EntityBrowserElement::processEntityIds($value);
        return $entities;
      }
      return $entities;
    }
    // IDs from a previous request might be saved in the form state.
    elseif ($form_state->has([
      'entity_browser_widget',
      $this->getFormStateKey($items),
    ])
    ) {
      $stored_ids = $form_state->get([
        'entity_browser_widget',
        $this->getFormStateKey($items),
      ]);
      $indexed_entities = $entity_storage->loadMultiple($stored_ids);

      // Selection can contain same entity multiple times. Since loadMultiple()
      // returns unique list of entities, it's necessary to recreate list of
      // entities in order to preserve selection of duplicated entities.
      foreach ($stored_ids as $entity_id) {
        if (isset($indexed_entities[$entity_id])) {
          $entities[] = $indexed_entities[$entity_id];
        }
      }
      return $entities;
    }
    // We are loading for for the first time so we need to load any existing
    // values that might already exist on the entity. Also, remove any leftover
    // data from removed entity references.
    else {
      foreach ($items as $item) {
        if (isset($item->target_id)) {
          $entity = $entity_storage->load($item->target_id);
          if (!empty($entity)) {
            $entities[] = $entity;
          }
        }
      }
      return $entities;
    }
  }

}
