<?php

namespace Drupal\pcp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManager;

/**
 * Provides a PCP configuration form.
 */
class PCPForm extends ConfigFormBase {

  private $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityFieldManager $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    // Load the service required to construct this class.
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pcp_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pcp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('pcp.settings');

    $form['general_setting'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('General Setting'),
    ];

    $form['general_setting']['hide_pcp_block_on_complete'] = [
      '#type' => 'checkbox',
      '#option' => ['1'],
      '#default_value' => $config->get('hide_block_on_complete'),
      '#title' => $this->t('Hide Block When Complete.'),
      '#description' => $this->t('When a user reaches 100% complete of their profile, do you want the profile complete percent block to go away? If so, check this box on.'),
    ];

    $form['general_setting']['field_order'] = [
      '#type' => 'radios',
      '#title' => $this->t('Profile Fields Order'),
      '#options' => ['0' => $this->t('Random'), '1' => $this->t('Fixed')],
      '#default_value' => $config->get('field_order') ?: 0,
      '#description' => $this->t('Select to show which field come first.'),
    ];

    $form['general_setting']['open_field_link'] = [
      '#type' => 'radios',
      '#title' => $this->t('Profile Fields Open Link'),
      '#options' => ['0' => $this->t('Same Window'), '1' => $this->t('New Window')],
      '#default_value' => $config->get('open_link') ?: 0,
      '#description' => $this->t('Select to open field link in browser.'),
    ];

    $form['core_field_setting'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Core Profile Field Settings'),
    ];

    $fields = array_filter($this->entityFieldManager->getFieldDefinitions('user', 'user'), function ($field_definition) {
      return $field_definition instanceof FieldConfigInterface;
    });

    $user_field = [];
    foreach ($fields as $key => $value) {
      $user_field[$key] = t($fields[$key]->label());
    }

    $form['core_field_setting']['profile_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Profile Fields'),
      '#options' => $user_field,
      '#default_value' => $config->get('profile_fields') ?: [],
      '#description' => $this->t('Checking a profile field below will add that field to the logic of the complete percentage.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('pcp.settings');

    $config->set('field_order', $form_state->getValue('field_order'))
           ->set('open_link', $form_state->getValue('open_field_link'))
           ->set('hide_block_on_complete', $form_state->getValue('hide_pcp_block_on_complete'))
           ->set('profile_fields', $form_state->getValue('profile_fields'))
           ->save();

    parent::submitForm($form, $form_state);
  }

}
