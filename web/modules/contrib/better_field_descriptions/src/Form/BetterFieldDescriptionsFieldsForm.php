<?php

namespace Drupal\better_field_descriptions\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManager;

/**
 * Displays the better_field_descriptions_fields settings form.
 */
class BetterFieldDescriptionsFieldsForm extends ConfigFormBase {

  /**
   * EntityFieldManagerservices object.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  private $entityFieldManager;

  /**
   * The bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfoService;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManager $entityFieldManager, EntityTypeBundleInfoInterface $bundle_info_service) {
    parent::__construct($config_factory);
    $this->entityFieldManager = $entityFieldManager;
    $this->bundleInfoService = $bundle_info_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['better_field_descriptions.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'better_field_descriptions_fields_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get better descriptions settings.
    $bfds = $this->config('better_field_descriptions.settings')->get('better_field_descriptions_settings');
    // Get existing descriptions for fields.
    $bfd = $this->config('better_field_descriptions.settings')->get('better_field_descriptions');
    // Use default template if not configured.
    if (isset($bfd['template']) == FALSE || empty($bfd['template'])) {
      $bfd['template'] = 'better-field-descriptions-text';
    }

    // Fetching template files from this module.
    $path = drupal_get_path('module', 'better_field_descriptions');
    $files = glob("{$path}/templates/*.html.twig", GLOB_BRACE);
    $templates = [];
    foreach ($files as $key => $value) {
      $templates[] = basename($value, ".html.twig");
    }
    $form['#templates'] = $templates;
    // Collects all templates found into array for select list.
    $better_descriptions_templates = [];

    foreach ($templates as $template) {
      // Removing the '.html.twig' if exists.
      if (($pos = strpos($template, '.')) !== FALSE) {
        $template = substr($template, 0, $pos);
      }
      $better_descriptions_templates[$template] = $template;
    }

    // Possible positions for the better description.
    $positions = [
      0 => $this->t('Above title and input'),
      1 => $this->t('Below title and input'),
      2 => $this->t('Between title and input'),
    ];

    $form['descriptions'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Add/edit better descriptions to the fields below.'),
    ];

    $form['bundles'] = [
      '#type' => 'item',
      '#prefix' => '<div id="better-field-descriptions-form-id-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    // Template selection.
    $form['bundles']['template'] = [
      '#type' => 'select',
      '#title' => $this->t('Select template for the descriptions'),
      '#options' => $better_descriptions_templates,
      '#default_value' => $bfd['template'],
      '#description' => $this->t('Changing this value will trigger a theme registry rebuild. You can also provide your own template, consult the documentation.'),
    ];

    // Setting label, default if not set.
    if (isset($bfd['default_label']) == FALSE) {
      $default_label = $this->t('Description');
    }
    else {
      $default_label = $bfd['default_label'];
    }

    $form['bundles']['default_label'] = [
      '#type' => 'textfield',
      '#title' => 'Default label for all field descriptions.',
      '#default_value' => $default_label,
      '#description' => $this->t('This label will be used if not set in each of the fields below.'),
    ];

    // Get info on bundles.
    $all_bundles = $this->bundleInfoService->getAllBundleInfo();

    foreach ($bfds as $entity_type => $entity_bundles) {
      // Wrapping each bundle in a collapsed fieldset.
      $form['bundles'][$entity_type] = [
        '#type' => 'details',
        '#title' => $entity_type,
      ];
      foreach ($entity_bundles as $bundle_machine_name => $fields) {

        // Array to hold fields in the node.
        $fields_instances = [];

        // Get info on pseudo fields, like title.
        $extra_fields = $this->entityFieldManager->getExtraFields($entity_type, $bundle_machine_name, 'form');
        if (isset($extra_fields['title'])) {
          $fields_instances['title'] = $extra_fields['title'];
        }

        // Get info on regular fields to the bundle.
        $fields_instances += $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle_machine_name);

        // Wrapping each bundle in a collapsed fieldset.
        $form['bundles'][$entity_type][$bundle_machine_name] = [
          '#type' => 'details',
          '#title' => $all_bundles[$entity_type][$bundle_machine_name]['label'],
          // Controls the HTML5 'open' attribute. Defaults to FALSE.
        ];

        foreach ($fields as $field_machine_name) {

          // Skip if field no longer exists.
          if (!isset($fields_instances[$field_machine_name])) {
            continue;
          }

          // Descriptions.
          $bfd_description = '';
          if (isset($bfd[$entity_type][$bundle_machine_name][$field_machine_name]['description'])) {
            $bfd_description = $bfd[$entity_type][$bundle_machine_name][$field_machine_name]['description'];
          }
          $form['bundles'][$entity_type][$bundle_machine_name][$field_machine_name]['description'] = [
            '#type' => 'textarea',
            '#title' => $fields_instances[$field_machine_name]->getLabel() . ' (' . $field_machine_name . ')',
            '#default_value' => Xss::filter($bfd_description),
            '#description' => $this->t('Add description for @machine_name.', ['@machine_name' => $fields_instances[$field_machine_name]->getLabel()]),
          ];

          // Label.
          $bfd_label = '';
          if (isset($bfd[$entity_type][$bundle_machine_name][$field_machine_name]['label'])) {
            $bfd_label = $bfd[$entity_type][$bundle_machine_name][$field_machine_name]['label'];
          }
          $form['bundles'][$entity_type][$bundle_machine_name][$field_machine_name]['label'] = [
            '#type' => 'textfield',
            '#title' => 'Label for this field description',
            '#default_value' => Xss::filter($bfd_label),
            '#description' => $this->t('Label for this field description.'),
          ];

          $position = 1;
          if (isset($bfd[$entity_type][$bundle_machine_name][$field_machine_name]['position'])) {
            $position = $bfd[$entity_type][$bundle_machine_name][$field_machine_name]['position'];
          }

          // Position of description.
          $form['bundles'][$entity_type][$bundle_machine_name][$field_machine_name]['position'] = [
            '#type' => 'radios',
            '#title' => 'Position of description.',
            '#options' => $positions,
            '#default_value' => $position,
            '#description' => $this->t('Position the description field above or below the input field. Using the between-option can cause unexpected results: any label set above will replace the label of the field, and for some kinds of fields the title may also be duplicated. Please review the relevant content form after saving these settings.'),
          ];
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bfd = $this->config('better_field_descriptions.settings')->get('better_field_descriptions');

    $template_bundle = $form_state->getValue('bundles');
    $path = drupal_get_path('module', 'better_field_descriptions');
    $template = $template_bundle['template'];
    $template_uri = $path . '/templates/' . $template . '.html.twig';
    $form_state->setValue(['bundles', 'template_uri'], $template_uri);

    // If the template is changed, do a theme registry rebuild.
    if (isset($bfd['template']) && $template != $bfd['template']) {
      drupal_theme_rebuild();
    }

    // Setting variables.
    $config = $this->config('better_field_descriptions.settings')->set('better_field_descriptions', $form_state->getValue('bundles'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
