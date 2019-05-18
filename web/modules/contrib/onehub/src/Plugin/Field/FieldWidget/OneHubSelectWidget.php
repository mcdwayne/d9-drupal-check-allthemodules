<?php

namespace Drupal\onehub\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\onehub\OneHubApi;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Plugin implementation of the 'onehub_select' widget.
 *
 * @FieldWidget(
 *   id = "onehub_select",
 *   label = @Translation("OneHub Select"),
 *   field_types = {
 *     "onehub_select"
 *   }
 * )
 */
class OneHubSelectWidget extends WidgetBase implements ContainerFactoryPluginInterface {

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
   * Constructs an OneHubSelectWidget object.
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

    $this->required = $element['#required'];
    $this->multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

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
}