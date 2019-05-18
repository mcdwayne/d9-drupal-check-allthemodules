<?php

namespace Drupal\node_title_validation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class NodeTitleValidationAdminForm.
 *
 * @package Drupal\node_title_validation\Form
 */
class NodeTitleValidationAdminForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new NodeTitleValidationController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entityTypeManager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configFactory.
   */
  public function __construct(EntityTypeManager $entityTypeManager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_title_validation_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get configuration value.
    $node_title_validation_config = $this->config('node_title_validation.node_title_validation_settings')->get('node_title_validation_config');

    // Get available content types.
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

    // Variable to display 1st fieldset collapse open.
    $i = 0;
    // Generate fieldset for each content type along with exclude, min,
    // max and unique form elements.
    foreach ($node_types as $type) {

      // Display First fieldset collapsed open.
      if ($i == 0) {
        $form[$type->get('type')] = [
          '#type' => 'fieldset',
          '#title' => $type->get('name'),
          '#collapsible' => TRUE,
          '#collapsed' => FALSE,
        ];
      }
      else {
        $form[$type->get('type')] = [
          '#type' => 'fieldset',
          '#title' => $type->get('name'),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        ];
      }
      // Increment $i for other fieldsets in collapsed closed.
      $i++;

      $form[$type->get('type')]['exclude-' . $type->get('type')] = [
        '#type' => 'textarea',
        '#default_value' => isset($node_title_validation_config['exclude-' . $type->get('type')]) ? $node_title_validation_config['exclude-' . $type->get('type')] : '',
        '#title' => $this->t('Blacklist Characters/Words'),
        '#description' => '<p>' . $this->t("Comma separated characters or words to avoided while saving node title. Ex: !,@,#,$,%,^,&,*,(,),1,2,3,4,5,6,7,8,9,0,have,has,were,aren't.") . '</p>' . '<p>' . $this->t('If any of the blacklisted characters/words found in node title,would return validation error on node save.') . '</p>',
      ];

      $form[$type->get('type')]['comma-' . $type->get('type')] = [
        '#type' => 'checkbox',
        '#default_value' => isset($node_title_validation_config['comma-' . $type->get('type')]) ? $node_title_validation_config['comma-' . $type->get('type')] : '',
        '#title' => $this->t('Add comma to blacklist.'),
      ];

      $form[$type->get('type')]['min-' . $type->get('type')] = [
        '#type' => 'number',
        '#title' => $this->t("Minimum characters"),
        '#required' => TRUE,
        '#description' => $this->t("Minimum number of characters node title should contain"),
        '#max' => 255,
        '#min' => 1,
        '#default_value' => isset($node_title_validation_config['min-' . $type->get('type')]) ? $node_title_validation_config['min-' . $type->get('type')] : 1,
      ];

      $form[$type->get('type')]['max-' . $type->get('type')] = [
        '#type' => 'number',
        '#title' => $this->t("Maximum characters"),
        '#required' => TRUE,
        '#description' => $this->t("Maximum number of characters node title should contain"),
        '#max' => 255,
        '#min' => 1,
        '#default_value' => isset($node_title_validation_config['max-' . $type->get('type')]) ? $node_title_validation_config['max-' . $type->get('type')] : 255,
      ];

      $form[$type->get('type')]['min-wc-' . $type->get('type')] = [
        '#type' => 'number',
        '#title' => $this->t("Minimum Word Count"),
        '#required' => TRUE,
        '#description' => $this->t("Minimum number of words node title should contain"),
        '#max' => 20,
        '#min' => 1,
        '#default_value' => isset($node_title_validation_config['min-wc-' . $type->get('type')]) ? $node_title_validation_config['min-wc-' . $type->get('type')] : 1,
      ];

      $form[$type->get('type')]['max-wc-' . $type->get('type')] = [
        '#type' => 'number',
        '#title' => $this->t("Maximum Word Count"),
        '#description' => $this->t("Maximum number of words node title should contain"),
        '#max' => 20,
        '#min' => 1,
        '#default_value' => isset($node_title_validation_config['max-wc-' . $type->get('type')]) ? $node_title_validation_config['max-wc-' . $type->get('type')] : 25,
      ];

      $form[$type->get('type')]['unique-' . $type->get('type')] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Unique node title for @type content type", [
          '@type' => $type->get('type'),
        ]),
        '#default_value' => isset($node_title_validation_config['unique-' . $type->get('type')]) ? $node_title_validation_config['unique-' . $type->get('type')] : 0,
      ];
    }

    $form['unique'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unique node title for all content types'),
      '#default_value' => isset($node_title_validation_config['unique']) ? $node_title_validation_config['unique'] : 0,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Get available content types.
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

    // Loop for each content type & validate min, max values.
    foreach ($node_types as $type) {
      // Get max characters count.
      $max = $form_state->getValue(['max-' . $type->get('type')]);
      // Get min characters count.
      $min = $form_state->getValue(['min-' . $type->get('type')]);

      // Validate min is less than max value.
      if ($min > $max) {
        $form_state->setErrorByName('min-' . $type->get('type'), $this->t("Minimum length should not be more than Max length"));
      }

      // Get min word count.
      $min_wc = $form_state->getValue('min-wc-' . $type->get('type'));
      // Get max word count.
      $max_wc = $form_state->getValue(['max-wc-' . $type->get('type')]);

      // Validate min is less than max value.
      if (!empty($min_wc) && !empty($max_wc) && $min_wc > $max_wc) {
        $form_state->setErrorByName('max-wc-' . $type->get('type'), $this->t("Minimum word count of title should not be more than Maximum word count"));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = [];
    // Get available content types.
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    // Store Form values in node_title_validation_config variable.
    foreach ($node_types as $type) {
      $values['comma-' . $type->get('type')] = $form_state->getValue(['comma-' . $type->get('type')]);
      $values['exclude-' . $type->get('type')] = $form_state->getValue(['exclude-' . $type->get('type')]);
      $values['min-' . $type->get('type')] = $form_state->getValue(['min-' . $type->get('type')]);
      $values['max-' . $type->get('type')] = $form_state->getValue(['max-' . $type->get('type')]);
      $values['min-wc-' . $type->get('type')] = $form_state->getValue(['min-wc-' . $type->get('type')]);
      $values['max-wc-' . $type->get('type')] = $form_state->getValue(['max-wc-' . $type->get('type')]);
      $values['unique-' . $type->get('type')] = $form_state->getValue(['unique-' . $type->get('type')]);
    }
    $values['unique'] = $form_state->getValue(['unique']);

    // Set node_title_validation_config variable.
    $this->configFactory->getEditable('node_title_validation.node_title_validation_settings')
      ->set('node_title_validation_config', $values)
      ->save();

    drupal_set_message($this->t('Node Title Validation Configurations saved successfully!'));
  }

}
