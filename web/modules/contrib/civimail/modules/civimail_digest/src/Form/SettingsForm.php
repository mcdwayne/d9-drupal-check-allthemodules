<?php

namespace Drupal\civimail_digest\Form;

use Drupal\civicrm_tools\CiviCrmGroupInterface;
use Drupal\civicrm_tools\CiviCrmContactInterface;
use Drupal\civimail_digest\CiviMailDigestSchedulerInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Drupal\civicrm_tools\CiviCrmGroupInterface definition.
   *
   * @var \Drupal\civicrm_tools\CiviCrmGroupInterface
   */
  protected $civicrmToolsGroup;

  /**
   * Drupal\civicrm_tools\CiviCrmContactInterface definition.
   *
   * @var \Drupal\civicrm_tools\CiviCrmContactInterface
   */
  protected $civicrmToolsContact;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SettingsForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    CiviCrmGroupInterface $civicrm_tools_group,
    CiviCrmContactInterface $civicrm_tools_contact,
    EntityTypeManagerInterface $entity_type_manager
    ) {
    parent::__construct($config_factory);
    $this->civicrmToolsGroup = $civicrm_tools_group;
    $this->civicrmToolsContact = $civicrm_tools_contact;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('civicrm_tools.group'),
      $container->get('civicrm_tools.contact'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'civimail_digest.settings',
    ];
  }

  /**
   * Returns a list of days.
   *
   * @return array
   *   List of week days.
   */
  private function getWeekDays() {
    // @todo review existing API
    return [
      0 => t('Sunday'),
      1 => t('Monday'),
      2 => t('Tuesday'),
      3 => t('Wednesday'),
      4 => t('Thursday'),
      5 => t('Friday'),
      6 => t('Saturday'),
    ];
  }

  /**
   * Returns a list of hours.
   *
   * @return array
   *   List of hours.
   */
  private function getHours() {
    // @todo review existing API
    $result = [];
    for ($h = 0; $h < 24; $h++) {
      $result[$h] = $h . ':00';
    }
    return $result;
  }

  /**
   * Returns a list of bundles currently limited to node type.
   *
   * @return array
   *   List of bundles.
   */
  private function getBundles() {
    $result = [];
    try {
      // @todo extend to other entity types
      $nodeBundles = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
      foreach ($nodeBundles as $key => $bundle) {
        $result[$key] = $bundle->label();
      }
    }
    catch (InvalidPluginDefinitionException $exception) {
      $exception->getMessage();
    }
    return $result;
  }

  /**
   * Returns all the groups, to be used as select options.
   *
   * @return array
   *   List of CiviCRM groups.
   */
  private function getGroups() {
    $result = [];
    $groups = $this->civicrmToolsGroup->getAllGroups();
    foreach ($groups as $key => $group) {
      $result[$key] = $group['title'];
    }
    return $result;
  }

  /**
   * Returns a list of contacts for a group, to be used as select options.
   *
   * @param array $groups
   *   CiviCRM array of group ids.
   *
   * @return array
   *   List of CiviCRM contacts.
   */
  private function getContacts(array $groups) {
    $result = [];
    // $contacts = $this->civicrmToolsContact->getFromGroups($groups);
    // foreach ($contacts as $key => $contact) {
    // $result[$key] = $contact['display_name'];
    // }.
    // CiviCRM Groups API does not seem to retrieve all the contacts.
    // Here is a workaround.
    /** @var \Drupal\civicrm_tools\CiviCrmDatabaseInterface $civiCrmDatabase */
    $civiCrmDatabase = \Drupal::service('civicrm_tools.database');
    /** @var \Drupal\civicrm_tools\CiviCrmApiInterface $civiCrmApi */
    $civiCrmApi = \Drupal::service('civicrm_tools.api');
    $groupsList = implode(',', $groups);
    $fromContactsQuery = "SELECT contact_id FROM civicrm_group_contact WHERE status='Added' AND group_id IN (" . $groupsList . ")";
    $fromResult = $civiCrmDatabase->execute($fromContactsQuery);
    foreach ($fromResult as $row) {
      $contact = $civiCrmApi->get('Contact', ['contact_id' => $row->contact_id]);
      $result[$row->contact_id] = $contact[$row->contact_id]['sort_name'] . ' - ' . $contact[$row->contact_id]['email'];
    }
    return $result;
  }

  /**
   * Ajax callback for the 'from contact group' selection.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The portion of the render structure that will replace the form element.
   */
  public function fromContactCallback(array $form, FormStateInterface $form_state) {
    return $form['contact']['from_contact_container'];
  }

  /**
   * Ajax callback for the 'validation contacts groups' selection.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The portion of the render structure that will replace the form element.
   */
  public function validationContactsCallback(array $form, FormStateInterface $form_state) {
    return $form['contact']['validation_contacts_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civimail_digest.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('civimail_digest.settings');

    if ($config->get('is_active')) {
      $digestListUrl = Url::fromRoute('civimail_digest.digest_list');
      $digestListLink = Link::fromTextAndUrl($this->t('send an view digests'), $digestListUrl);
      $digestListLink = $digestListLink->toRenderable();
      \Drupal::messenger()->addStatus($this->t(
        'CiviMail Digest is active, you can now @digest_list_link.', [
          '@digest_list_link' => \Drupal::service('renderer')->renderRoot($digestListLink),
        ]
      ));
    }

    $availableGroups = $this->getGroups();

    // Do not get from contacts when the group filter is empty
    // as this could fetch all the contacts.
    $fromGroup = [];
    $fromContacts = [];
    if (!empty($form_state->getValue('from_group'))) {
      $fromGroup = $form_state->getValue('from_group');
      $fromContacts = $this->getContacts([$fromGroup]);
    }
    elseif (!empty($config->get('from_group'))) {
      $fromGroup = $config->get('from_group');
      $fromContacts = $this->getContacts([$fromGroup]);
    }

    // Do not get validation contacts when the group filter is empty
    // as this could fetch all the contacts.
    $validationGroups = [];
    $validationContacts = [];
    if (!empty($form_state->getValue('validation_groups'))) {
      $validationGroups = $form_state->getValue('validation_groups');
      // @todo multiple validation groups
      $validationContacts = $this->getContacts([$validationGroups]);
    }
    elseif (!empty($config->get('validation_groups'))) {
      $validationGroups = $config->get('validation_groups');
      // @todo multiple validation groups
      $validationContacts = $this->getContacts([$validationGroups]);
    }

    // @todo dependency injection
    $entityDisplayRepository = \Drupal::service('entity_display.repository');
    // @todo extend to other content entities
    $viewModes = $entityDisplayRepository->getViewModeOptions('node');

    // @todo dependency injection
    $languageManager = \Drupal::languageManager();
    $languages = $languageManager->getLanguages();
    $availableLanguages = [];
    foreach ($languages as $key => $language) {
      $availableLanguages[$key] = $language->getName();
    }

    $form['digest_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Digest title'),
      // @todo use token for digest number.
      '#description' => $this->t('Title that appears in mail subject, and title in browser view. The digest number will be appended.'),
      '#maxlength' => 254,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('digest_title'),
    ];
    // Is active is currently kept as a simple way to check
    // that the digest has been configured properly
    // without having to check anything else.
    // It is used by CiviMailDigestInterface::isActive() and is
    // a convenient way to suspend the digest without having to
    // uninstall the module.
    $form['is_active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Is digest active'),
      '#description' => $this->t('When checked, digests of the contents that were previously sent via CiviMail can be prepared with optional automation.'),
      '#default_value' => $config->get('is_active'),
    ];

    $form['scheduler'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Scheduler'),
      '#states' => [
        'visible' => [
          ':input[name="is_active"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['scheduler']['is_scheduler_active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Is scheduler active'),
      '#description' => $this->t('When checked, digests can be scheduled each week.'),
      '#default_value' => $config->get('is_scheduler_active'),
    ];
    $form['scheduler']['scheduler_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Scheduler type'),
      '#description' => $this->t('Prepare and notify validators or prepare and send automatically the digest to the defined groups and time.'),
      '#options' => [
        CiviMailDigestSchedulerInterface::SCHEDULER_NOTIFY => $this->t('Prepare and notify validators'),
        CiviMailDigestSchedulerInterface::SCHEDULER_SEND => $this->t('Prepare and send'),
      ],
      '#default_value' => $config->get('scheduler_type'),
      '#states' => [
        'visible' => [
          ':input[name="is_scheduler_active"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="is_scheduler_active"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['scheduler']['scheduler_week_day'] = [
      '#type' => 'select',
      '#title' => $this->t('Week day'),
      '#description' => $this->t('Day to notify validators or send the weekly digest.'),
      '#options' => $this->getWeekDays(),
      '#default_value' => $config->get('scheduler_week_day'),
      '#states' => [
        'visible' => [
          ':input[name="is_scheduler_active"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="is_scheduler_active"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['scheduler']['scheduler_hour'] = [
      '#type' => 'select',
      '#title' => $this->t('Hour'),
      '#description' => $this->t('Hour to notify validators or send the weekly digest.'),
      '#options' => $this->getHours(),
      '#default_value' => $config->get('scheduler_hour'),
      '#states' => [
        'visible' => [
          ':input[name="is_scheduler_active"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="is_scheduler_active"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['display'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Display'),
      '#states' => [
        'visible' => [
          ':input[name="is_active"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['display']['view_mode'] = [
      '#type' => 'select',
      '#title' => t('Content view mode'),
      '#options' => $viewModes,
      '#description' => $this->t('View mode that will be used by the digest for each content excerpt in the mail template.'),
      '#default_value' => $config->get('view_mode'),
    ];

    $form['limit'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Limit'),
      '#states' => [
        'visible' => [
          ':input[name="is_active"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['limit']['quantity_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity limit'),
      '#description' => $this->t('Limits the amount of entities that will be included in a single digest.'),
      '#required' => TRUE,
      '#default_value' => $config->get('quantity_limit'),
    ];
    // @todo filter bundles that have been activated for CiviMail
    $form['limit']['bundles'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundles'),
      '#description' => $this->t('Optionally limit bundles that can be part of the digest. All apply if none selected.'),
      '#options' => $this->getBundles(),
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#default_value' => $config->get('bundles'),
    ];
    $form['limit']['include_update'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include CiviMail updates'),
      '#description' => $this->t('If checked, when several mailings have been sent for the same entity, it will also include the last per content mailing. So, it will bypass the check of a content that was already sent in a previous digest.'),
      '#default_value' => $config->get('include_update'),
    ];
    $form['limit']['age_in_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Days'),
      '#description' => $this->t('Do not include content older than the defined days.'),
      '#required' => TRUE,
      '#default_value' => $config->get('age_in_days'),
    ];
    // @todo open to multilingual digest
    $form['limit']['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#description' => $this->t('Include CiviMail mailings in this language.'),
      '#options' => $availableLanguages,
      '#multiple' => FALSE,
      '#required' => TRUE,
      '#default_value' => $config->get('language'),
    ];

    $form['contact'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Contacts'),
      '#states' => [
        'visible' => [
          ':input[name="is_active"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // From group and contact dependent select elements.
    $form['contact']['from_group'] = [
      '#type' => 'select',
      '#title' => $this->t('From contact groups'),
      '#description' => $this->t('Set a group that will be used to filter the from contact.'),
      '#options' => $availableGroups,
      '#default_value' => $fromGroup,
      '#ajax' => [
        'callback' => '::fromContactCallback',
        'wrapper' => 'from-contact-container',
        'event' => 'change',
      ],
      '#required' => TRUE,
    ];
    // JS fallback to trigger a form rebuild.
    $form['contact']['choose_from_group'] = [
      '#type' => 'submit',
      '#value' => $this->t('Choose from contact group'),
      '#states' => [
        'visible' => ['body' => ['value' => TRUE]],
      ],
    ];
    $form['contact']['from_contact_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'from-contact-container'],
    ];
    $form['contact']['from_contact_container']['from_contact_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Choose a contact'),
    ];
    $form['contact']['from_contact_container']['from_contact_fieldset']['from_contact'] = [
      '#type' => 'select',
      '#title' => $this->t('From contact'),
      '#description' => $this->t('Contact that will be used as the sender.'),
      '#options' => $fromContacts,
      '#default_value' => $config->get('from_contact'),
      '#required' => TRUE,
    ];

    $form['contact']['to_groups'] = [
      '#type' => 'select',
      '#title' => $this->t('To groups'),
      '#description' => $this->t('CiviCRM groups that will receive the digest.'),
      '#options' => $this->getGroups(),
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#default_value' => $config->get('to_groups'),
    ];
    $form['contact']['test_groups'] = [
      '#type' => 'select',
      '#title' => $this->t('Test groups'),
      '#description' => $this->t('CiviCRM groups that will receive tests. Currently inactive - on this mvp, test digests are not implemented yet.'),
      '#options' => $this->getGroups(),
      '#multiple' => TRUE,
      '#default_value' => $config->get('test_groups'),
      '#disabled' => TRUE,
    ];

    // Validation groups and contacts dependent select elements.
    // @todo if the is_scheduler_active is set to true then back to false
    // and the scheduler_type remaining configuration is still
    // CiviMailDigestSchedulerInterface::SCHEDULER_NOTIFY
    // the validation groups must be hidden.
    $form['contact']['validation_groups'] = [
      '#type' => 'select',
      '#title' => $this->t('Validation contact group'),
      '#description' => $this->t('Set the group that will be used to filter the validation contacts.'),
      '#options' => $availableGroups,
      '#default_value' => $validationGroups,
      '#ajax' => [
        'callback' => '::validationContactsCallback',
        'wrapper' => 'validation-contacts-container',
        'event' => 'change',
      ],
      // @todo open to multiple groups
      '#multiple' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="scheduler_type"]' => ['value' => CiviMailDigestSchedulerInterface::SCHEDULER_NOTIFY],
        ],
        'required' => [
          ':input[name="scheduler_type"]' => ['value' => CiviMailDigestSchedulerInterface::SCHEDULER_NOTIFY],
        ],
      ],
    ];
    // JS fallback to trigger a form rebuild.
    $form['contact']['choose_validation_group'] = [
      '#type' => 'submit',
      '#value' => $this->t('Choose validation contact group'),
      '#states' => [
        'visible' => ['body' => ['value' => TRUE]],
      ],
    ];
    $form['contact']['validation_contacts_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'validation-contacts-container'],
      '#states' => [
        'visible' => [
          ':input[name="scheduler_type"]' => ['value' => CiviMailDigestSchedulerInterface::SCHEDULER_NOTIFY],
        ],
      ],
    ];
    $form['contact']['validation_contacts_container']['validation_contacts_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Choose at least one contact'),
      '#states' => [
        'visible' => [
          ':input[name="scheduler_type"]' => ['value' => CiviMailDigestSchedulerInterface::SCHEDULER_NOTIFY],
        ],
      ],
    ];
    $form['contact']['validation_contacts_container']['validation_contacts_fieldset']['validation_contacts'] = [
      '#type' => 'select',
      '#title' => $this->t('Validation contacts'),
      '#description' => $this->t('CiviCRM contacts that will confirm that the digest can be sent.'),
      '#options' => $validationContacts,
      '#default_value' => $config->get('validation_contacts'),
      '#multiple' => TRUE,
      '#states' => [
        'required' => [
          ':input[name="scheduler_type"]' => ['value' => CiviMailDigestSchedulerInterface::SCHEDULER_NOTIFY],
        ],
      ],
    ];

    // If no group is selected for a contact give a hint to the user
    // that it must be selected first.
    if (empty($config->get('from_group')) && empty($form_state->getValue('from_group'))) {
      $form['contact']['from_contact_container']['from_contact_fieldset']['from_contact']['#title'] = $this->t('You must choose the from group first.');
      $form['contact']['from_contact_container']['from_contact_fieldset']['from_contact']['#disabled'] = TRUE;
    }
    if (empty($config->get('validation_groups')) && empty($form_state->getValue('validation_groups'))) {
      $form['contact']['validation_contacts_container']['validation_contacts_fieldset']['validation_contacts']['#title'] = $this->t('You must choose the validation group first.');
      $form['contact']['validation_contacts_container']['validation_contacts_fieldset']['validation_contacts']['#disabled'] = TRUE;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Make the distinction between plain form submit and ajax trigger.
    $trigger = (string) $form_state->getTriggeringElement()['#value'];
    if ($trigger == 'Save configuration') {
      parent::submitForm($form, $form_state);
      $this->config('civimail_digest.settings')
        ->set('digest_title', $form_state->getValue('digest_title'))
        ->set('is_active', $form_state->getValue('is_active'))
        ->set('is_scheduler_active', $form_state->getValue('is_scheduler_active'))
        ->set('scheduler_type', $form_state->getValue('scheduler_type'))
        ->set('scheduler_week_day', $form_state->getValue('scheduler_week_day'))
        ->set('scheduler_hour', $form_state->getValue('scheduler_hour'))
        ->set('view_mode', $form_state->getValue('view_mode'))
        ->set('quantity_limit', $form_state->getValue('quantity_limit'))
        ->set('bundles', $form_state->getValue('bundles'))
        ->set('include_update', $form_state->getValue('include_update'))
        ->set('age_in_days', $form_state->getValue('age_in_days'))
        ->set('language', $form_state->getValue('language'))
        ->set('from_group', $form_state->getValue('from_group'))
        ->set('from_contact', $form_state->getValue('from_contact'))
        ->set('to_groups', $form_state->getValue('to_groups'))
        ->set('test_groups', $form_state->getValue('test_groups'))
        ->set('validation_groups', $form_state->getValue('validation_groups'))
        ->set('validation_contacts', $form_state->getValue('validation_contacts'))
        ->save();
    }
    else {
      $form_state->setRebuild();
    }
  }

}
