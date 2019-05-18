<?php

namespace Drupal\global_gateway_country\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\global_gateway\RegionNegotiatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RegionNegotiationByUserFieldConfigForm.
 *
 * @package Drupal\global_gateway_country\Form
 */
class RegionNegotiationByUserFieldConfigForm extends ConfigFormBase {
  /**
   * Negotiation object.
   *
   * @var \Drupal\global_gateway\RegionNegotiator
   */
  protected $negotiator;
  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * RegionNegotiationByUserFieldConfigForm constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityFieldManagerInterface $field_manager,
    RegionNegotiatorInterface $negotiator
  ) {
    parent::__construct($config_factory);
    $this->fieldManager = $field_manager;
    $this->negotiator = $negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('global_gateway_region_negotiator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['global_gateway.region.negotiation'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'global_gateway_region_negotiation_by_user_field_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Prepare the fields list.
    $fields = $this->getFieldsList();
    // Show the message if User entity hasn't appropriate fields.
    if (empty($fields)) {
      $this->messenger()->addWarning($this->t('Sorry there are no country fields found in entity User'));
      return [];
    }
    // Get default value(saved field name).
    $field_name = $this->negotiator
      ->getNegotiator('user_field')
      ->get('user_field_name');
    $form['user_field_name'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Country field'),
      '#default_value' => $field_name,
      '#options'       => ['' => '- None -'] + $fields,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the field name to the appropriate negotiator config object.
    $negotiator = $this->negotiator->getNegotiator('user_field');
    $negotiator->set('user_field_name', $form_state->getValue('user_field_name'));
    $type[$negotiator->id()] = $negotiator->getConfiguration();
    $this->negotiator->saveConfiguration($type);
    parent::submitForm($form, $form_state);
  }

  /**
   * Get country fields from user's entity.
   *
   * @return array
   *   Country fields attached to user entity.
   */
  protected function getFieldsList() {
    $fields = [];
    foreach ($this->fieldManager->getFieldDefinitions('user', 'user') as $field) {
      if (in_array($field->getType(), ['country', 'address_country'])) {
        $name = $field->getLabel() . '(' . $field->getName() . ')';
        $fields[$field->getName()] = $name;
      }
    }
    return $fields;
  }

}
