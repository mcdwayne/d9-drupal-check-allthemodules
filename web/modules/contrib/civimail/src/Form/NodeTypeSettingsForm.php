<?php

namespace Drupal\civimail\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\civimail\CiviMailInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Node type settings form.
 */
class NodeTypeSettingsForm extends FormBase {

  /**
   * Drupal\civimail\CiviMailInterface definition.
   *
   * @var \Drupal\civimail\CiviMailInterface
   */
  protected $civiMail;

  /**
   * Constructs a NodeMessageForm object.
   *
   * @param \Drupal\civimail\CiviMailInterface $civi_mail
   *   The CiviMail service.
   */
  public function __construct(CiviMailInterface $civi_mail) {
    $this->civiMail = $civi_mail;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('civimail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civimail_node_type_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node_type = NULL) {
    $storage = [
      'node_type' => $node_type,
    ];
    $form_state->setStorage($storage);
    $groupOptions = $this->civiMail->getGroupSelectOptions();
    // @todo dependency injection
    $entityDisplayRepository = \Drupal::service('entity_display.repository');
    $viewModes = $entityDisplayRepository->getViewModeOptions('node');

    // @todo review the following options
    // - default contact
    // - filter sender contacts
    // - use CiviMail header and footer
    // - use mail template
    // - show translation urls if any
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable CiviMail for this content type'),
      '#default_value' => civimail_get_entity_bundle_settings('enabled', 'node', $node_type),
    ];
    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => t('Mail view mode'),
      '#options' => $viewModes,
      '#description' => $this->t('View mode that will be used by CiviMail for the mail body.'),
      '#default_value' => civimail_get_entity_bundle_settings('view_mode', 'node', $node_type),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    // @todo add 'only' / 'all except those selected'
    $form['from_groups'] = [
      '#type' => 'select',
      '#title' => t('Sender groups'),
      '#options' => $groupOptions,
      '#description' => $this->t('Limit the CiviMail from contacts to the following CiviCRM groups for this content type. All apply if none selected.'),
      '#multiple' => TRUE,
      '#default_value' => civimail_get_entity_bundle_settings('from_groups', 'node', $node_type),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    // @todo add 'only' / 'all except those selected'
    $form['to_groups'] = [
      '#type' => 'select',
      '#title' => t('Recipient groups'),
      '#options' => $groupOptions,
      '#description' => $this->t('Limit the CiviMail send form to the following CiviCRM groups for this content type. All apply if none selected.'),
      '#multiple' => TRUE,
      '#default_value' => civimail_get_entity_bundle_settings('to_groups', 'node', $node_type),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $storage = $form_state->getStorage();
    $node_type = $storage['node_type'];
    // Update cvimail settings.
    $settings = [];
    // Empty configuration if set again to disabled.
    if (!$values['enabled']) {
      $settings = civimail_get_entity_bundle_setting_defaults();
    }
    else {
      $settings = civimail_get_entity_bundle_settings('all', 'node', $node_type);
      foreach (civimail_available_entity_bundle_settings() as $setting) {
        if (isset($values[$setting])) {
          $settings[$setting] = is_array($values[$setting]) ? array_keys(array_filter($values[$setting])) : $values[$setting];
        }
      }
    }
    civimail_set_entity_bundle_settings($settings, 'node', $node_type);
    $messenger = \Drupal::messenger();
    $messenger->addMessage(t('Your changes have been saved.'));
  }

}
