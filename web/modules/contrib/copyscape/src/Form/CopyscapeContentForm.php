<?php

namespace Drupal\copyscape\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Administrative form for Copyscape content settings route.
 */
class CopyscapeContentForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Constructs a new CopyscapeContentForm object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   */
  public function __construct(
    EntityFieldManager $entityFieldManager
  ) {
    $this->entityFieldManager = $entityFieldManager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'copyscape_content_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('copyscape.content');

    $form['reject_content'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reject content if copy percentage is bigger than (in %):'),
      '#required' => FALSE,
      '#default_value' => $config->get('reject_content'),
    ];

    $form['reject_value'] = [
      '#title' => $this->t(''),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $config->get('reject_value'),
    ];

    foreach (node_type_get_names() as $machineName => $contentType) {
      $configName = "copyscape_ct.{$machineName}";
      $form[$configName] = [
        '#type' => 'fieldset',
        '#title' => $this->t($contentType),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      ];

      $fields = $this->entityFieldManager->getFieldDefinitions('node', $machineName);

      $allowedFields = [];
      foreach ($fields as $fieldMachineName => $field) {
        if (!$field instanceof FieldConfig) {
          continue;
        }

        if (!in_array($field->getType(), [
          'text_long',
          'string_long',
          'text_with_summary',
        ])) {
          continue;
        }

        $allowedFields[$fieldMachineName] = $field->getLabel();
      }

      if (empty($allowedFields)) {
        continue;
      }

      $form[$configName][$machineName] = [
        '#type' => 'checkboxes',
        '#options' => $allowedFields,
        '#default_value' => $config->get($configName) ? $config->get($configName) : [],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $options = [];
    $this->config('copyscape.content')
      ->set('reject_content', $values['reject_content'])
      ->set('reject_value', $values['reject_value']);

    foreach (node_type_get_names() as $machineName => $contentType) {
      if (empty($values[$machineName])) {
        continue;
      }
      $options[$machineName] = $values[$machineName];
    }
    $this->config('copyscape.content')
      ->set('copyscape_ct', $options)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['copyscape.content'];
  }
}
