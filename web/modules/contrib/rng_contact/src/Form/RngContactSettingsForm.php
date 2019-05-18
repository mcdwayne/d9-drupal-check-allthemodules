<?php

namespace Drupal\rng_contact\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\rng_contact\Entity\RngContactType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Configure RNG Contact settings.
 */
class RngContactSettingsForm extends ConfigFormBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a RngContactSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($config_factory);
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rng_contact_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'rng_contact.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['table_help'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Select email fields Courier should use when communicating with contacts.'),
    ];

    $form['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Contact type'),
        $this->t('Email field'),
      ],
      '#empty' => $this->t('There are no people types available.'),
    ];

    foreach (RngContactType::loadMultiple() as $contact_type) {
      $row = [];
      $row['label'] = [
        '#markup' => $contact_type->label()
      ];

      /** @var \Drupal\rng_contact\Entity\RngContactTypeInterface $contact_type */
      $field_definitions = $this->entityFieldManager
        ->getFieldDefinitions('rng_contact', $contact_type->id());

      $field_options = [];
      foreach ($field_definitions as $field_definition) {
        $field_type = $field_definition->getType();
        if ($field_type == 'email') {
          $field_options[$field_definition->getName()] = $field_definition->getLabel();
        }
      }

      $row['email_field'] = [
        '#type' => 'select',
        '#title_display' => 'invisible',
        '#empty_option' => $this->t('- Disable -'),
        '#empty_value' => NULL,
        '#options' => $field_options,
        '#default_value' => $contact_type->getCourierEmailField(),
      ];

      $form['table'][$contact_type->id()] = $row;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('table') as $contact_type_id => $row) {
      /** @var \Drupal\rng_contact\Entity\RngContactTypeInterface $contact_type */
      $contact_type = RngContactType::load($contact_type_id);
      $email_field = !empty($row['email_field']) ? $row['email_field'] : NULL;
      $contact_type->setCourierEmailField($email_field);
      $contact_type->save();
    }
    drupal_set_message($this->t('Settings updated.'));
  }

}
