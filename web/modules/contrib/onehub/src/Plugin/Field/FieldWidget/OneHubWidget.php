<?php

namespace Drupal\onehub\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\onehub\OneHubApi;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\WidgetBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Plugin implementation of the 'onehub_file' widget.
 *
 * @FieldWidget(
 *   id = "onehub_file",
 *   label = @Translation("OneHub File"),
 *   field_types = {
 *     "onehub"
 *   }
 * )
 */
class OneHubWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection to which to dump route information.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * The OneHubApi object.
   *
   * @var \Drupal\onehub\OneHubApi
   */
  protected $oh;


  /**
   * Constructs an OneHubWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\onehub\OneHubApi $oh
   *   OneHub instantiated object.
   * @param \Drupal\Core\Database\Connection $db
   *   The database connection to be used.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, OneHubApi $oh, Connection $db) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->oh = $oh;
    $this->db = $db;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $onehub = new OneHubApi();
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $onehub,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_settings = $this->getFieldSettings();

    // The field settings include defaults for the field type. However, this
    // widget is a base class for other widgets (e.g., ImageWidget) that may act
    // on field types without these expected settings.
    $field_settings += [
      'display_default' => NULL,
      'display_field' => NULL,
      'description_field' => NULL,
    ];

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $defaults = [
      'fids' => [],
      'display' => (bool) $field_settings['display_default'],
      'description' => '',
    ];

    // Grabs our delta fields for wrappers.
    $item = $items[$delta];

    $wrapper = 'onehub-select-' . $delta;

    // Grab the workspaces.
    $workspaces = $this->oh->listWorkspaces();

    // Sets default values
    $selected_ws = isset($item->workspace) ? $item->workspace : NULL;
    $selected_folder = isset($item->folder) ? $item->folder : NULL;

    // Fixes multi-item widget loading issues.
    $trigger = $form_state->getTriggeringElement();
    if ($trigger['#type'] == 'submit') {
      $values = $form_state->getValues();
      foreach ($values as $value) {
        $selected_ws = $value[$delta]['workspace'];
        $selected_folder = $value[$delta]['folder'];
        break;
      }
    }

    // What we are wrapping our form with.
    $element['#prefix'] = '<div id="onehub-markup-file">';
    $element['#suffix'] = '</div>';
    $element['#field_name'] = $this->fieldDefinition->getName();

    // Load up the file to be displayed.
    if ($item->target_id !== NULL && empty($_POST)) {
      $results = $this->db->select('onehub', 'o')
        ->fields('o')
        ->condition('original_fid', $item->target_id)
        ->execute()
        ->fetchAll();

      $folders = $this->oh->listFolders($selected_ws);

      foreach ($results as $result) {
        $markup  = '<p>Filename: ' . $result->filename . '</p>';
        $markup .= '<p>Workspace: ' . $workspaces[$result->workspace] . '</p>';
        $markup .= '<p>Folder: ' . $folders[$result->folder] . '</p>';

        $element['onehub'] = [
          '#type' => 'item',
          '#markup' => $markup,
        ];

        $element['remove'] = [
          '#type' => 'submit',
          '#value' => t('Remove'),
        ];

        $element['remove']['#submit'][] = [$this, 'submitRemoveFile'];
        $element['remove']['#limit_validation_errors'] = [];
      }
    }
    // If it is a new file.
    else {

      $element['onehub_file'] = [
        '#type' => 'managed_file',
        '#upload_location' => $item->getUploadLocation(),
        '#upload_validators' => $item->getUploadValidators(),
        '#weight' => $delta,
      ];

      // The OneHub fields
      $element['workspace'] = [
        '#title' => t('OneHub Workspace'),
        '#type' => 'select',
        '#options' => $workspaces,
        '#empty_option' => '<' . t('Select a Workspace') . '>',
        '#description' => t('Select a workspace to upload the file to.'),
        '#default_value' => $selected_ws,
        '#weight' => 20,
        '#required' => FALSE,
        '#ajax' => [
          'callback' => [$this, 'ajaxPopulateFolders'],
          'wrapper' => $wrapper,
        ],
      ];

      // Grab the folders if the workspace is set.
      if ($selected_ws !== NULL) {
        $folders = $this->oh->listFolders($selected_ws);
      }

      $element['folder'] = [
        '#title' => t('OneHub Folder'),
        '#type' => 'select',
        '#description' => t('Select a folder to upload the file to.'),
        '#default_value' => $selected_folder,
        '#options' => isset($folders) ? $folders : [],
        '#empty_option' => '<' . t('Select a Workspace Above') . '>',
        '#weight' => 21,
        '#prefix' => '<div id="' . $wrapper . '">',
        '#suffix' => '</div>',
        '#required' => FALSE,
        '#validated' => 'true',
      ];
    }

    $element['#theme_wrappers'][] = 'fieldset';

    return $element;
  }

  /**
   * Ajax call that dynamically populates a field.
   *
   * @param  array $form
   *   The form object.
   * @param  FormStateInterface $form_state
   *   The form state object.
   *
   * @return object
   *   The new dynamically changed element.
   */
  public function ajaxPopulateFolders(array &$form, FormStateInterface &$form_state) {
    // Grab the workspace field.
    $workspace = $form_state->getTriggeringElement();

    // Splice out this array so we can easily send it back.
    $folder = NestedArray::getValue($form, array_slice($workspace['#array_parents'], 0, -1));
    $element = $folder['folder'];

    // Our selected workspace id from above.
    $selected_ws = $workspace['#value'];

    // Grabs out folders and sets the folder element options.
    $folders = $this->oh->listFolders($selected_ws);
    $element['#options'] = $folders;
    $element['#required'] = TRUE;

    // Rebuild for safe measure.
    $form_state->setRebuild();

    return $element;
  }

  public function submitRemoveFile(array &$form, FormStateInterface &$form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Rebuild the widget so it can add the managed file items in the formElement.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    static::setWidgetState($parents, $field_name, $form_state, $field_state);
    $form_state->setRebuild();
  }

  /**
   * Ajax call that dynamically populates a field.
   *
   * @param  array $form
   *   The form object.
   * @param  FormStateInterface $form_state
   *   The form state object.
   *
   * @return object
   *   The new dynamically changed element.
   */
  public function ajaxRemoveFile(array &$form, FormStateInterface &$form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#onehub-markup-file', $form));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Need to alter the values properly here.
    // @see Drupal\file\Plugin\Field\FieldWidget\FileWidget
    $new_values = [];
    foreach ($values as &$value) {
      foreach ($value as $name => $fid) {
        if ($name == 'onehub_file') {
          $new_value = $value;
          $new_value['target_id'] = $fid[0];
          unset($new_value[$name]);
          $new_values[] = $new_value;
        }
      }
    }
    return $new_values;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    parent::extractFormValues($items, $form, $form_state);

    // @see Drupal\file\Plugin\Field\FieldWidget\FileWidget
    $field_name = $this->fieldDefinition->getName();
    $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
    $field_state['items'] = $items->getValue();
    static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
  }
}
