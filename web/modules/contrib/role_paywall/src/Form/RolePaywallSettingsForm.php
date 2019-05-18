<?php

namespace Drupal\role_paywall\Form;

use Drupal\block\Entity\Block;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements role paywall configuration form.
 */
class RolePaywallSettingsForm extends ConfigFormBase {

  /**
   * Internal fields not need to be set with the paywall.
   */
  const NOT_APPLICABLE_FIELDS = [
    'nid',
    'uuid',
    'vid',
    'langcode',
    'type',
    'revision_timestamp',
    'revision_uid',
    'revision_log',
    'status',
    'uid',
    'created',
    'changed',
    'promote',
    'sticky',
    'default_language',
    'revision_translation_affected',
    'publish_on',
    'unpublish_on',
    'default_langcode',
    'path',
    'menu_link',
  ];

  /**
   * Stores locally the injected manager.
   *
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * Stores locally the injected manager.
   *
   * @var EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entityManager, EntityFieldManagerInterface $entityFieldManager) {
    $this->entityManager = $entityManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'role_paywall_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'role_paywall.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->config('role_paywall.settings');

    $roles = [];
    foreach (Role::loadMultiple() as $role_id => $role_obj) {
      $roles[$role_id] = $role_obj->get('label');
    }
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles with premium access'),
      '#options' => $roles,
      '#default_value' => $configuration->get('roles') ?: [],
    ];

    $bundles = $this->entityManager->getBundleInfo('node');
    // @todo Support multiple bundles.
    $active_bundles = $configuration->get('bundles') ?: [];
    $options = [];
    foreach ($bundles as $bundle_id => $bundle) {
      $options[$bundle_id] = $bundle['label'];
    }
    $form['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types'),
      '#options' => $options,
      '#default_value' => $active_bundles,
    ];

    foreach ($active_bundles as $bundle_id => $label) {
      if ($label === '0') {
        continue;
      }
      $fields = $this->entityFieldManager->getFieldDefinitions('node', $bundle_id);
      $hide_fields = [];
      $activate_paywall_field = [];
      foreach ($fields as $field_id => $field) {
        if (!in_array($field_id, self::NOT_APPLICABLE_FIELDS, TRUE)) {
          $hide_fields[$field_id] = (string) $field->getLabel();
          if ($field->getType() === 'boolean') {
            $activate_paywall_field[$field_id] = (string) $field->getLabel();
          }
        }
      }

      $context = 'display';
      $extra_fields = \Drupal::entityManager()->getExtraFields('node', $bundle_id);
      $extra_fields = isset($extra_fields[$context]) ? $extra_fields[$context] : [];
      foreach ($extra_fields as $field_id => $field) {
        if (in_array($field_id, self::NOT_APPLICABLE_FIELDS, TRUE)) {
          continue;
        }
        $hide_fields[$field_id] = is_object($field['label']) ? $field['label']->render() : $field['label'];
      }

      if (!empty($activate_paywall_field)) {
        if (!isset($form['activate_paywall_field'])) {
          $form['activate_paywall_field'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Premium content field'),
          ];
          $form['hidden_fields'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Content to hide'),
          ];
        }
        $form['activate_paywall_field']['activate_paywall_field_' . $bundle_id] = [
          '#type' => 'radios',
          '#title' => $this->t('Field to mark @bundle behind the paywall', ['@bundle' => $label]),
          '#options' => $activate_paywall_field,
          '#default_value' => $configuration->get('activate_paywall_field')[$bundle_id] ?: [],
        ];
        $form['hidden_fields']['hidden_fields_' . $bundle_id] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Fields to hide in @bundle', ['@bundle' => $label]),
          '#options' => $hide_fields,
          '#default_value' => $configuration->get('hidden_fields.' . $bundle_id),
        ];
      }
      else {
        $form['no_flag'] = [
          '#type' => 'markup',
          '#markup' => $this->t('A boolean field in @bundle is required. The field will mark if each node is premium and the fields have to be hidden or not', ['@bundle' => $label]),
        ];
      }
    }

    // Loads blocks to select one to be used as a barrier.
    $block_ids = \Drupal::entityQuery('block')->execute();
    $block_options = [];
    foreach ($block_ids as $block_id) {
      $block = Block::load($block_id);
      $admin_label = (string) $block->getPlugin()->getPluginDefinition()['admin_label'];
      $block_options[$block_id] = $admin_label;
    }

    $form['barrier_block'] = [
      '#type' => 'select',
      '#title' => $this->t('Block to use as barrier'),
      '#options' => $block_options,
      '#default_value' => $configuration->get('barrier_block'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundles = array_filter($form_state->getValue('bundles'));
    $activate_paywall_field = [];
    $hidden_fields = [];
    foreach ($bundles as $bundle_id => $label) {
      $activate_paywall_field[$bundle_id] = $form_state->getValue('activate_paywall_field_' . $bundle_id) ?: '';
      $hidden_fields[$bundle_id] = array_filter($form_state->getValue('hidden_fields_' . $bundle_id) ?: []);
    }
    $this->config('role_paywall.settings')
      ->set('bundles', $bundles)
      ->set('roles', array_filter($form_state->getValue('roles')))
      ->set('activate_paywall_field', $activate_paywall_field)
      ->set('hidden_fields', $hidden_fields)
      ->set('barrier_block', $form_state->getValue('barrier_block'))
      ->save();
    parent::submitForm($form, $form_state);

    drupal_flush_all_caches();
  }

}
