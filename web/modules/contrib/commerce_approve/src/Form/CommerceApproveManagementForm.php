<?php

namespace Drupal\commerce_approve\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Basic settings form for Commerce Approve.
 *
 * Provides configuration form to toggle fields on product variation types.
 *
 * Class CommerceApproveManagementForm
 *
 * @package Drupal\commerce_approve\Form
 */
class CommerceApproveManagementForm extends ConfigFormBase {

  protected $entityTypeManager;

  /**
   * CommerceApproveManagementForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory to pass back to parent.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'approve_management_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_approve.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('commerce_approve.settings');

    $form['about'] = [
      '#type' => 'fieldset',
      '#title' => 'About',
    ];
    $form['about']['about_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#default_value' => $config->get('about_enabled'),
    ];
    $form['about']['about_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('About Text'),
      '#description' => $this->t('Explain why approval is required during checkout.'),
      '#default_value' => $config->get('about_text'),
    ];

    $form['select'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Taxonomy Vocabulary'),
      '#description' => $this->t('Add fields to taxonomy terms under this vocabulary.'),
    ];

    // Load & format product variation types for checkboxes.
    $vocab = Vocabulary::loadMultiple();
    /** @var \Drupal\taxonomy\Entity\Vocabulary $item */
    foreach ($vocab as $item) {
      $form['select']['enable_' . $item->id()] = [
        '#title' => $item->label(),
        '#type' => 'checkbox',
        '#default_value' => $config->get('vocab')[$item->id()],
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $neededFields = [
      'field_require_approval' => $this->t('Require approval'),
      'field_require_approval_text' => $this->t('Require approval text'),
    ];

    $enabled = [];
    foreach ($values as $key => $value) {
      // Filter out values we can't / don't want to handle.
      if (strpos($key, 'enable_') === FALSE) {
        continue;
      }

      // If we get past the filters assume it's a verb and throw on the field.
      $key = str_replace('enable_', '', $key);
      $enabled[$key] = $value;
      foreach ($neededFields as $id => $label) {
        $field_storage = FieldStorageConfig::loadByName('taxonomy_term', $id);
        if (!$field_storage) {
          FieldStorageConfig::create([
            'entity_type' => 'taxonomy_term',
            'field_name' => $id,
            'type' => $id === 'field_require_approval' ? 'boolean' : 'string',
            'cardinality' => 1,
          ])->save();
        }
        $field = FieldConfig::loadByName('taxonomy_term', $key, $id);
        // Bit funky but allows us to safely delete the field if it exists,
        // or skip if it doesn't.
        if (!$value) {
          if ($field) {
            $field->delete();
          }
          continue;
        }
        if (!$field) {
          $field = FieldConfig::create([
            'entity_type' => 'taxonomy_term',
            'field_name' => $id,
            'bundle' => $key,
            'label' => $label,
          ])->setDefaultValue(TRUE);
          $field->save();

          // Set visibility and component type so it automatically shows up.
          $display = $this->entityTypeManager->getStorage('entity_form_display')
            ->load('taxonomy_term.' . $key . '.default');
          $display->setComponent($id, [
            'label' => 'hidden',
            'type' => $id === 'field_require_approval' ? 'boolean_checkbox' : 'string_textfield',
            'weight' => 90,
          ])->save();
        }
      }
    }
    // Save config for future reference.
    $config = $this->configFactory()->getEditable('commerce_approve.settings');

    $config->set('vocab', $enabled)->save();
    $config->set('about_text', $values['about_text'])->save();
    $config->set('about_enabled', $values['about_enable'])->save();
  }

}
