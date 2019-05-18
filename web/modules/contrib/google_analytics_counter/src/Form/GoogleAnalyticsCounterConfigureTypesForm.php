<?php

namespace Drupal\google_analytics_counter\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\google_analytics_counter\GoogleAnalyticsCounterCustomFieldGeneratorInterface;
use Drupal\google_analytics_counter\GoogleAnalyticsCounterAppManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * The form for editing content types with the custom google analytics counter field.
 *
 * @internal
 */
class GoogleAnalyticsCounterConfigureTypesForm extends ConfigFormBase {

  /**
   * Config Factory Service Object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Drupal\google_analytics_counter\GoogleAnalyticsCounterAppManagerInterface.
   *
   * @var \Drupal\google_analytics_counter\GoogleAnalyticsCounterAppManagerInterface
   */
  protected $appManager;

  /**
   * Drupal\google_analytics_counter\GoogleAnalyticsCounterCustomFieldGeneratorInterface.
   *
   * @var \Drupal\google_analytics_counter\GoogleAnalyticsCounterCustomFieldGeneratorInterface
   */
  protected $customField;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger, GoogleAnalyticsCounterAppManagerInterface $app_manager, GoogleAnalyticsCounterCustomFieldGeneratorInterface $custom_field) {
    parent::__construct($config_factory);
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->appManager = $app_manager;
    $this->customField = $custom_field;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('google_analytics_counter.app_manager'),
      $container->get('google_analytics_counter.custom_field_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_analytics_counter_configure_types_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_analytics_counter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
    $config = $this->config('google_analytics_counter.settings');

    // Add a checkbox to determine whether the storage for the custom field should be removed.
    $form['gac_custom_field_storage_status'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom field storage information'),
      '#open' => TRUE,
    ];
    $form['gac_custom_field_storage_status']['gac_type_remove_storage'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove the custom field'),
      '#description' => $this->t('Removes the custom Google Analytics Counter field from the system completely.'),
      '#default_value' => $config->get("general_settings.gac_type_remove_storage"),
    ];

    // Add a checkbox field for each content type.
    $form['gac_content_types'] = [
      '#type' => 'details',
      '#title' => $this->t('Content types'),
      '#description' => $this->t('Check the content types to add the custom Google Analytics Counter field to.'),
      '#open' => TRUE,
    ];
    $content_types = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    foreach ($content_types as $machine_name => $content_type) {
      $form['gac_content_types']["gac_type_$machine_name"] = [
        '#type' => 'checkbox',
        '#title' => $content_type->label(),
        '#default_value' => $config->get("general_settings.gac_type_$machine_name"),
        '#states' => [
          'disabled' => [
            ':input[name="gac_type_remove_storage"]' => ['checked' => TRUE],
          ],
        ],

      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('google_analytics_counter.settings');
    $config_factory = $this->configFactory();
    $values = $form_state->cleanValues()->getValues();

    // Save the remove_storage configuration.
    $config
      ->set('general_settings.gac_type_remove_storage', $values['gac_type_remove_storage'])
      ->save();

    // Loop through each content type. Add/subtract the custom field or do nothing.
    foreach ($values as $key => $value) {
      if ($key == 'gac_type_remove_storage') {
        continue;
      }

      // Get the NodeTypeInterface $type from gac_type_{content_type}.
      $type = \Drupal::service('entity.manager')
        ->getStorage('node_type')
        ->load(substr($key, 9));

      // Add the field if the field has been checked.
      if ($values['gac_type_remove_storage'] == FALSE && $value == 1) {
        $this->customField->gacPreAddField($type, $key, $value);
      }
      else if ($values['gac_type_remove_storage'] == FALSE && $value == 0) {
        $this->customField->gacPreDeleteField($type, $key);

        // Update the gac_type_{content_type} configuration.
        $config_factory->getEditable('google_analytics_counter.settings')
          ->set("general_settings.$key", NULL)
          ->save();
      }
      else {
        // Delete the field.
        if ($values['gac_type_remove_storage'] == TRUE) {
          // Delete the field.
          $this->customField->gacPreDeleteField($type, $key);
          // Delete the field storage.
          $this->customField->gacDeleteFieldStorage();
          // Set all the gac_type_{content_type} to NULL.
          $this->customField->gacChangeConfigToNull();
        }
      }
    }

    parent::submitForm($form, $form_state);
  }

}