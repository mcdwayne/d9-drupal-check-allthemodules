<?php

/**
 * Contains \Drupal\file_compressor_field\Form\FileCompressorRebuildForm.
 */
namespace Drupal\file_compressor_field\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manually Rebuild File Compressor fields..
 */
class FileCompressorRebuildForm extends FormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new FileCompressorRebuildForm.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'file_compressor_rebuild';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $definitions = $this->entityManager->getFieldMapByFieldType('file_compressor');

    $options = array();
    foreach ($definitions as $entity_type_id => $field_definitions) {
      $entity_type = $this->entityManager->getDefinition($entity_type_id);
      $bundle_info = $this->entityManager->getBundleInfo($entity_type_id);
      foreach ($field_definitions as $field_name => $field_map) {
        foreach ($field_map['bundles'] as $bundle) {
          $bundle_definitions = $this->entityManager->getFieldDefinitions($entity_type_id, $bundle);
          $option_key = implode('|', array($entity_type_id, $bundle));
          if (!isset($options[$option_key])) {
            $options[$option_key] = array($bundle_definitions[$field_name]->getLabel(), $entity_type->getLabel(), $bundle_info[$bundle]['label']);
          }
          else {
            $options[$option_key][0] = $options[$option_key][0] . ', ' . $bundle_definitions[$field_name]->getLabel();
          }
        }
      }
    }

    $header = array(t('Field Name'), t('Entity type'), t('Bundle'));

    $form['rebuild'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No content available.'),
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Rebuild Compressed files'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $selected_fields = array_filter($form_state->getValue('rebuild'));
    if (empty($selected_fields)) {
      $form_state->setErrorByName('rebuild', t('Please select at least one field to update.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fields = array_filter($form_state->getValue('rebuild'));
    foreach ($fields as $field) {
      list($entity_type_name, $bundle_name) = explode('|', $field);
      $entity_type = $this->entityManager->getDefinition($entity_type_name);

      $query = \Drupal::EntityQuery($entity_type_name);
      if ($type = $entity_type->getKey('bundle')) {
        $query->condition($type, $bundle_name);
      }
      $result = $query->execute();
      if (!empty($result)) {
        $entities = entity_load_multiple($entity_type_name, $result);
        foreach ($entities as $entity) {
          $entity->save();
        }
      }
    }
  }

}