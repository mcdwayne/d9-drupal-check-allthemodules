<?php

namespace Drupal\applenews\Form;

use Drupal\applenews\Plugin\ApplenewsComponentTypeManager;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ApplenewsTemplateForm.
 *
 * @package Drupal\applenews\Form
 */
class ApplenewsTemplateForm extends EntityForm {

  /**
   * Component type manager.
   *
   * @var \Drupal\applenews\Plugin\ApplenewsComponentTypeManager
   */
  protected $applenewsComponentTypeManager;

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an ApplenewsTemplateForm object.
   *
   * @param \Drupal\applenews\Plugin\ApplenewsComponentTypeManager $component_type_manager
   *   Component type manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ApplenewsComponentTypeManager $component_type_manager, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->applenewsComponentTypeManager = $component_type_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.applenews_component_type'),
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\applenews\Entity\ApplenewsTemplate $template */
    $template = $this->entity;
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

    $form['#prefix'] = '<div id="applenews-template-form-wrapper">';
    $form['#suffix'] = '<div>';

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $template->label(),
      '#description' => $this->t("Label for this template."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $template->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$template->isNew(),
    ];

    $node_type_options = [];
    foreach ($node_types as $id => $node_type) {
      $node_type_options[$id] = $node_type->label();
    }

    $form['node_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Node Type'),
      '#description' => $this->t('The node type to which this template should apply.'),
      '#options' => $node_type_options,
      '#default_value' => $template->getNodeType(),
      '#required' => TRUE,
    ];

    $form['layout'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Layout'),
      '#description' => $this->t('For more information: <a href="https://developer.apple.com/library/content/documentation/General/Conceptual/Apple_News_Format_Ref/Layout.html#//apple_ref/doc/uid/TP40015408-CH65-SW1">https://developer.apple.com/library/content/documentation/General/Conceptual/Apple_News_Format_Ref/Layout.html#//apple_ref/doc/uid/TP40015408-CH65-SW1</a>'),
    ];

    $layout = $template->getLayout();

    $form['layout']['columns'] = [
      '#type' => 'number',
      '#title' => $this->t('Columns'),
      '#description' => $this->t('The number of columns this article was designed for. You must have at least one column.'),
      '#required' => TRUE,
      '#min' => 1,
      '#default_value' => $layout['columns'] ? $layout['columns'] : 7,
    ];

    $form['layout']['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#description' => $this->t('The width (in points) this article was designed for. This property is used to calculate down-scaling scenarios for smaller devices.'),
      '#required' => TRUE,
      '#default_value' => $layout['width'] ? $layout['width'] : 1024,
      '#min' => 1,
    ];

    $form['layout']['gutter'] = [
      '#type' => 'number',
      '#title' => $this->t('Gutter'),
      '#description' => $this->t('The gutter size for the article (in points). The gutter provides spacing between columns.'),
      '#default_value' => $layout['gutter'] ? $layout['gutter'] : 20,
    ];

    $form['layout']['margin'] = [
      '#type' => 'number',
      '#title' => $this->t('Margin'),
      '#description' => $this->t('The outer (left and right) margins of the article, in points.'),
      '#default_value' => $layout['margin'] ? $layout['margin'] : 60,
    ];

    $components = $template->getComponents();

    $form['components_list'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Components'),
    ];

    $form['components_list']['components_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Type'),
        $this->t('Data Mapping'),
        $this->t('Operations'),
        $this->t('Weight'),
        $this->t('Parent'),
      ],
      '#empty' => $this->t('This template has no components yet.'),
      '#prefix' => '<div id="components-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'row-parent-id',
          'source' => 'row-id',
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'component-weight',
        ],
      ],
    ];

    $rows = [];
    foreach ($components as $id => $component) {
      $rows[$id] = $this->getComponentRow($component, $form_state);
      $component_plugin = $this->applenewsComponentTypeManager->createInstance($component['id']);
      // If not a nested component, it cannot be a parent of other components.
      if ($component_plugin->getComponentType() != 'nested') {
        $rows[$id]['#attributes']['class'][] = 'tabledrag-leaf';
      }
      else {
        $rows += $this->getChildComponentRows($component, $form_state);
        $rows[$id]['type']['#markup'] = '<strong>' . $component['id'] . '</strong>';
      }
    }

    $form['components_list']['components_table'] += $rows;

    $form['add_components'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add Components'),
      '#prefix' => '<div id="add-components-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['add_components']['component_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Component types'),
      '#options' => $this->getComponentOptions(),
    ];

    $form['add_components']['add_component_form'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add new component'),
      '#submit' => ['::setComponentFormStep'],
      '#name' => 'add_component_form',
      '#ajax' => [
        'callback' => '::addComponentForm',
        'wrapper' => 'add-components-fieldset-wrapper',
      ],
    ];

    $input = $form_state->getUserInput();
    $component_type = $form_state->get('sub_form_component_type');
    if ($component_type) {
      $component_plugin = $this->applenewsComponentTypeManager->createInstance($component_type);
      $form['add_components'] += $component_plugin->settingsForm($form, $form_state);
      unset($form['add_components']['add_component_form']);
      unset($form['add_components']['component_type']);
    }

    if ($component_type || isset($input['save_component'])) {

      $form['add_components']['component_actions'] = [
        '#type' => 'actions',
      ];

      $form['add_components']['component_actions']['save_component'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save Component'),
        '#name' => 'save_component',
        '#submit' => ['::addComponent'],
        '#ajax' => [
          'callback' => '::saveComponent',
          'wrapper' => 'applenews-template-form-wrapper',
        ],
      ];

      $form['add_components']['component_actions']['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
        '#button_type' => 'danger',
        '#submit' => ['::resetTempFormValues'],
        '#ajax' => [
          'callback' => '::cancelComponentForm',
          'wrapper' => 'add-components-fieldset-wrapper',
        ],
      ];

    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->saveComponentOrder($form_state);
    $template = $this->entity;
    $status = $template->save();

    if ($status) {
      $this->messenger()->addStatus($this->t('Saved the %label template.', ['%label' => $template->label()]));
    }
    else {
      $this->messenger()->addError($this->t('The %label template was not saved.', ['%label' => $template->label()]));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

  /**
   * Helper function to check whether an Example configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('applenews_template')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * Get all the available Applenews component plugins.
   *
   * @return array
   *   An array of component options suitable for a select element.
   */
  protected function getComponentOptions() {
    $component_options = [];
    foreach ($this->applenewsComponentTypeManager->getDefinitions() as $id => $component_type) {
      $component_options[$id] = $component_type['label'];
    }
    return $component_options;
  }

  /**
   * Ajax submit handler when someone clicks "Add new component".
   *
   * Stores the selected component type for later use.
   *
   * @param array $form
   *   An array of from definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state array.
   */
  public function setComponentFormStep(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $form_state->set('sub_form_component_type', $input['component_type']);
    $form_state->setRebuild();
  }

  /**
   * Ajax submit handler used when a "Cancel" button is clicked.
   *
   * @param array $form
   *   An array of from definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state array.
   */
  public function resetTempFormValues(array &$form, FormStateInterface $form_state) {
    $form_state->set('sub_form_component_type', NULL);
    $form_state->set('delete_component', NULL);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback responsible for displaying the component form.
   *
   * Triggered when the "Add new component" button is clicked.
   *
   * @param array $form
   *   An array of from definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state array.
   *
   * @return mixed
   *   An array of new component.
   */
  public function addComponentForm(array &$form, FormStateInterface $form_state) {
    return $form['add_components'];
  }

  /**
   * Ajax callback respsonsible for returning the updated components table.
   *
   * Triggered when either the "delete" button or either of its confirmation
   * buttons are clicked.
   *
   * @param array $form
   *   An array of from definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state array.
   *
   * @return mixed
   *   An array of component table.
   */
  public function refreshComponentTable(array &$form, FormStateInterface $form_state) {
    return $form['components_list']['components_table'];
  }

  /**
   * Ajax submit handler responsible for saving a new component.
   *
   * Triggered when the "Save Component" button is clicked.
   *
   * @param array $form
   *   An array of from definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state array.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addComponent(array &$form, FormStateInterface $form_state) {
    $form_state->set('sub_form_component_type', NULL);
    if ($component = $this->getNewComponentValues($form_state)) {
      $this->entity->addComponent($component);
    }

    $this->entity->save();
    drupal_set_message('Component added successfully.');
    $form_state->setRebuild();
  }

  /**
   * Ajax callback that refreshed the whole form.
   *
   * Triggered when the "Save Component" button is clicked.
   *
   * @param array $form
   *   An array of from definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state array.
   *
   * @return array
   *   An array of form definition.
   */
  public function saveComponent(array &$form, FormStateInterface $form_state) {
    // @todo return commands and replace both form and component table so we don't have to replace the whole form.
    return $form;
  }

  /**
   * Ajax callback that gets rid of the new component form.
   *
   * Triggered when the "Cancel" button is clicked on the new component form.
   *
   * @param array $form
   *   An array of from definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state array.
   *
   * @return mixed
   *   Component definition array.
   */
  public function cancelComponentForm(array &$form, FormStateInterface $form_state) {
    return $form['add_components'];
  }

  /**
   * Ajax submit handler that stores the row the "delete" button was clicked on.
   *
   * @param array $form
   *   An array of from definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state array.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setDeleteComponentForm(array &$form, FormStateInterface $form_state) {
    $this->saveComponentOrder($form_state);
    $id = $this->getTriggeringRowIndex($form_state->getTriggeringElement());
    $form_state->set('delete_component', $id);
    $form_state->setRebuild();
  }

  /**
   * Ajax submit handler.
   *
   * Responsible for deleting a component from the table and entity. Triggered
   * when the "Yes" button is clicked in confirmation.
   */
  public function deleteComponent(array &$form, FormStateInterface $form_state) {
    $form_state->set('delete_component', NULL);
    $id = $this->getTriggeringRowIndex($form_state->getTriggeringElement());
    $this->entity->deleteComponent($id);
    if (count($this->entity->getComponents()) == 0) {
      $this->entity->save();
    }
    else {
      $this->saveComponentOrder($form_state);
    }
    drupal_set_message('Component deleted.');
    $form_state->setRebuild();
  }

  /**
   * Format the values from a newly added component into an array.
   *
   * @todo Replace with a value object
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array
   *   An array in the proper format to pass to AppleTemplate::addComponent()
   */
  protected function getNewComponentValues(FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['component_settings']['id'])) {
      $components = $this->entity->getComponents();
      $last_component = end($components);
      return [
        'uuid' => \Drupal::service('uuid')->generate(),
        'id' => $values['component_settings']['id'],
        'weight' => $last_component['weight'] + 1,
        'component_layout' => $values['component_settings']['component_layout'],
        'component_data' => $values['component_settings']['component_data'],
      ];
    }

    return [];
  }

  /**
   * Helper function to get the parent of a button that was pressed.
   *
   * Used for component deletion and confirmation forms.
   *
   * @param array $triggering_element
   *   An array of element definition.
   *
   * @return string
   *   String row index.
   */
  protected function getTriggeringRowIndex(array $triggering_element) {
    return $triggering_element['#parents'][1];
  }

  /**
   * Get form elements to display to confirm a component will be deleted.
   *
   * Used in the components list table.
   *
   * @return array
   *   The form array containing a Yes and Cancel button.
   */
  protected function getComponentRowDeleteConfirmation() {
    $operations = [];

    $operations['yes'] = [
      '#type' => 'submit',
      '#value' => $this->t('Yes'),
      '#submit' => ['::deleteComponent'],
      '#button_type' => 'primary',
      '#prefix' => '<span>' . $this->t('Are you sure?') . '</span>',
      '#ajax' => [
        'callback' => '::refreshComponentTable',
        'wrapper' => 'components-fieldset-wrapper',
      ],
    ];

    $operations['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::resetTempFormValues'],
      '#ajax' => [
        'callback' => '::refreshComponentTable',
        'wrapper' => 'components-fieldset-wrapper',
      ],
    ];

    return $operations;
  }

  /**
   * Sorts the components based on their new weights from the draggable table.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state array.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function saveComponentOrder(FormStateInterface $form_state) {
    $component_table = $form_state->getValue('components_table');
    $components = $this->entity->getComponents();
    if ($components) {
      foreach ($component_table as $id => $new_component_values) {

        // If something was moved out of a parent relationship.
        if (!isset($components[$id]) && !$new_component_values['parent_id']) {
          if ($former_child = $this->entity->getComponent($id)) {
            $components[$id] = $former_child;
            $this->deleteChildComponent($components, $id);
          }
        }

        if ($new_component_values['parent_id']) {
          if ($child_component = $this->entity->getComponent($id)) {
            // In case it was a child of another parent, go ahead and delete it.
            $this->deleteChildComponent($components, $id);
            $this->addChildComponent($components, $new_component_values['parent_id'], $child_component);
          }
          unset($components[$id]);
        }
        else {
          $components[$id]['weight'] = $new_component_values['weight'];
        }

        // Clean up, in case was triggered by a deletion.
        if (!$components[$id]['id']) {
          unset($components[$id]);
        }

      }

      // Find all nested components and sort their children.
      foreach ($components as $id => $component) {
        if (isset($component['component_data']['components'])) {
          uasort($component['component_data']['components'], [$this->entity, 'sortHelper']);
        }
      }

      $this->entity->setComponents($components);
      $this->entity->save();
    }
  }

  /**
   * Deletes child component.
   *
   * @param array $components
   *   Component object.
   * @param string $child_id
   *   Child id to delete.
   */
  protected function deleteChildComponent(array &$components, $child_id) {
    foreach ($components as &$component) {
      foreach ($component['component_data']['components'] as $id => $child_component) {
        if ($id == $child_id) {
          unset($component['component_data']['components'][$id]);
          if (!$component['component_data']['components']) {
            $component['component_data']['components'] = NULL;
          }
          break;
        }
        if (isset($child_component['component_data']['components'])) {
          $this->deleteChildComponent($child_component['component_data']['components'], $child_id);
        }
      }
    }
  }

  /**
   * Adds a child component.
   *
   * @param array $components
   *   Component object.
   * @param string $parent_id
   *   Parent id.
   * @param array $child_component
   *   Child component to add.
   *
   * @return bool
   *   TRUE if added successfully, FALSE otherwise.
   */
  protected function addChildComponent(array &$components, $parent_id, array $child_component) {
    // Go through top level components.
    foreach ($components as $id => &$component) {
      if ($id == $parent_id) {
        $component['component_data']['components'][$child_component['uuid']] = $child_component;
        return TRUE;
      }
    }

    // Go through any children they might have if we haven't found
    // the parent id.
    foreach ($components as $id => &$component) {
      return $this->addChildComponent($component['component_data']['components'], $parent_id, $child_component);
    }

    return FALSE;
  }

  /**
   * Return formatted component data as a summary.
   *
   * @param array $component
   *   Component definition.
   *
   * @return string
   *   String component data to display.
   */
  protected function displayComponentData(array $component) {
    $return = '';
    foreach ($component['component_data'] as $key => $data) {
      if (is_array($data) && $key != 'components') {
        $data = Json::encode($data);
        $return .= $key . ': ' . $data . '<br />';
      }
    }
    return $return;
  }

  /**
   * Constructs a component row.
   *
   * @param array $component
   *   Component object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param null|string $parent_id
   *   Parent id, if available.
   *
   * @return array
   *   An array of row definition.
   */
  protected function getComponentRow(array $component, FormStateInterface $form_state, $parent_id = NULL) {
    $row = [];
    $row['#attributes']['class'][] = 'draggable';

    $row['type'] = [
      '#markup' => $component['id'],
    ];
    $row['field'] = [
      '#markup' => $this->displayComponentData($component),
    ];
    $row['operations'] = [
      '#type' => 'actions',
    ];

    if ($form_state->get('delete_component') === $component['uuid']) {
      $row['operations'] = $this->getComponentRowDeleteConfirmation();
    }
    else {
      $row['operations']['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('delete'),
        '#name' => 'component_delete_' . $component['uuid'],
        '#submit' => ['::setDeleteComponentForm'],
        '#ajax' => [
          'callback' => '::refreshComponentTable',
          'wrapper' => 'components-fieldset-wrapper',
        ],
      ];
    }

    $row['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#title_display' => 'invisible',
      '#default_value' => $component['weight'],
      '#attributes' => ['class' => ['component-weight']],
    ];

    $row['parent_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Parent ID'),
      '#default_value' => $parent_id,
      '#attributes' => [
        'class' => [
          'row-parent-id',
        ],
      ],
    ];

    // Needed for tabledrag functionality in determining a parent.
    $row['row_id'] = [
      '#type' => 'hidden',
      '#value' => $component['uuid'],
      '#attributes' => [
        'class' => [
          'row-id',
        ],
      ],
    ];

    return $row;
  }

  /**
   * Gets child component row definition to render.
   *
   * @param array $component
   *   Component object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param int $depth
   *   Depth.
   *
   * @return array
   *   An array of row definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getChildComponentRows(array $component, FormStateInterface $form_state, $depth = 1) {
    $rows = [];
    foreach ($component['component_data']['components'] as $id => $child_component) {
      $rows[$id] = $this->getComponentRow($child_component, $form_state, $component['uuid']);
      $indentation = [
        '#theme' => 'indentation',
        '#size' => $depth,
      ];
      $rows[$id]['type']['#prefix'] = $this->renderer->render($indentation);
      $component_plugin = $this->applenewsComponentTypeManager->createInstance($child_component['id']);
      // If not a nested component, it cannot be a parent of other components.
      if ($component_plugin->getComponentType() != 'nested') {
        $rows[$id]['#attributes']['class'][] = 'tabledrag-leaf';
      }
      else {
        $rows[$id]['type']['#markup'] = '<strong>' . $child_component['id'] . '</strong>';
        $rows += $this->getChildComponentRows($child_component, $form_state, $depth + 1);
      }
    }

    return $rows;
  }

}
