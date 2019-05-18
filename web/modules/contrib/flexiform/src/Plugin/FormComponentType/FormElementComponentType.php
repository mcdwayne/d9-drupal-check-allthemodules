<?php

namespace Drupal\flexiform\Plugin\FormComponentType;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\Element;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Drupal\flexiform\FormComponent\FormComponentTypeCreateableBase;
use Drupal\flexiform\FormElementPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for field widget component types.
 *
 * @FormComponentType(
 *   id = "form_element",
 *   label = @Translation("Form Element"),
 *   component_class = "Drupal\flexiform\Plugin\FormComponentType\FormElementComponent",
 * )
 */
class FormElementComponentType extends FormComponentTypeCreateableBase implements ContainerFactoryPluginInterface {
  use ContextAwarePluginAssignmentTrait;

  /**
   * The form element plugin manager.
   *
   * @var \Drupal\flexiform\FormElementPluginManager
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.flexiform.form_element')
    );
  }

  /**
   * Construct a new form element component type object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\flexiform\FormElementPluginManager $plugin_manager
   *   The form element plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormElementPluginManager $plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function componentRows(EntityDisplayFormBase $form_object, array $form, FormStateInterface $form_state) {
    $rows = [];
    foreach ($this->getFormDisplay()->getComponents() as $component_name => $options) {
      if (isset($options['component_type']) && $options['component_type'] == $this->getPluginId()) {
        $rows[$component_name] = $this->buildComponentRow($form_object, $component_name, $form, $form_state);
      }
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function addComponentForm(array $form, FormStateInterface $form_state) {
    // Build the parents for the form element selector.
    $parents = $form['#parents'];
    $parents[] = 'form_element';

    $available_plugins = $this->pluginManager->getDefinitionsForContexts($this->getFormEntityManager()->getContexts());

    $form['#prefix'] = '<div id="flexiform-form-element-add-wrapper">';
    $form['#suffix'] = '</div>';

    $plugin_options = [];
    foreach ($available_plugins as $plugin_id => $plugin_definition) {
      if (empty($plugin_definition['no_ui'])) {
        $plugin_options[$plugin_id] = $plugin_definition['label'];
      }
    }
    $form['form_element'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => $plugin_options,
      '#title' => $this->t('Form Element'),
      '#ajax' => [
        'callback' => [$this, 'ajaxFormElementSelect'],
        'wrapper' => 'flexiform-form-element-add-wrapper',
      ],
    ];

    if ($plugin_id = NestedArray::getValue($form_state->getUserInput(), $parents)) {
      $plugin = $this->pluginManager->createInstance($plugin_id);

      if ($plugin instanceof ContextAwarePluginInterface) {
        $contexts = $this->getFormEntityManager()->getContexts();
        $form['context_mapping'] = [
          '#parents' => ['options', 'settings', 'context_mapping'],
        ] + $this->addContextAssignmentElement($plugin, $contexts);

        foreach (Element::children($form['context_mapping']) as $mapping_key) {
          $form['context_mapping'][$mapping_key]['#empty_option'] = $this->t('- Select -');
        }
      }

      $form += $plugin->settingsForm($form, $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function addComponentFormSubmit(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxFormElementSelect(array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $array_parents = $element['#array_parents'];
    array_pop($array_parents);

    return NestedArray::getValue($form, $array_parents);
  }

}
