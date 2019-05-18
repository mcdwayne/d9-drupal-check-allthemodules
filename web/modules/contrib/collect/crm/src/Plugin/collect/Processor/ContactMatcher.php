<?php
/**
 * @file
 * Contains \Drupal\collect\Plugin\collect\Processor\ContactMatcher.
 */

namespace Drupal\collect_crm\Plugin\collect\Processor;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Plugin\collect\Model\CollectJson;
use Drupal\collect\Processor\ProcessorBase;
use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\collect\TypedData\TypedDataProvider;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\crm_core_contact\ContactInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Matches and/or creates a CRM Core Contact entity.
 *
 * @Processor(
 *   id = "contact_matcher",
 *   label = @Translation("Contact matcher"),
 *   description = @Translation("Matches or creates a CRM Core Contact entity.")
 * )
 */
class ContactMatcher extends ProcessorBase {

  /**
   * The injected entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a ContactMatcher processor plugin.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, TypedDataProvider $typed_data_provider, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $typed_data_provider);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('collect'),
      $container->get('collect.typed_data_provider'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(CollectDataInterface $data, array &$context) {
    $relation = $this->getConfigurationItem('relation') ?: 'default';

    // Read contact values from the data.
    $contact_values = $this->readContactValuesFromData($data, $data->getContainer());
    if (empty($contact_values)) {
      return;
    }

    // Match existing contacts.

    // Create a template contact entity from the identified values.
    // @todo: Let matchers provide a matching template.
    /** @var \Drupal\crm_core_contact\ContactInterface $template_contact */
    $template_contact = $this->entityManager->getStorage('crm_core_individual')->create([
      'type' => $this->getConfigurationItem('contact_type'),
    ] + $contact_values);

    // Look for an equivalent extisting contact.
    $existing_contact = $this->matchContact($template_contact);

    // Update the existing contact or save a new one.
    if (!empty($existing_contact)) {
      $this->updateContact($existing_contact, $contact_values);
    }
    else {
      $existing_contact = $this->saveNewContact($template_contact);
    }

    // Add contact to context.
    $context['contacts'][$relation][$existing_contact->id()] = $existing_contact;
  }

  /**
   * Reads contact values from the processed data.
   *
   * Some identified values can have multiple cardinality, which is interpreted
   * as representing different contacts. All single values are however assumed
   * to represent the same contact, which will be the first element in the
   * returned list.
   *
   * @param \Drupal\collect\TypedData\CollectDataInterface $data
   *   The data being processed.
   * @param \Drupal\collect\CollectContainerInterface $container
   *   The container of the data.
   *
   * @return array
   *   A list of associative arrays per identified contact, each containing
   *   values matching the CRM Core contact fields.
   */
  protected function readContactValuesFromData(CollectDataInterface $data, CollectContainerInterface $container) {
    // Initialize first element so that it is not created by the [] assignment.
    $contact_values = array();

    // Read selected properties.
    foreach ($this->getConfigurationItem('fields') as $contact_field_name => $property_name) {
      // In order to properly create a name field on Contact, hard-code it to
      // "given" property.
      // @todo: Find a way to properly fill complex name field properties.
      if ($contact_field_name == 'name') {
        $contact_values['name']['given'] = $data->get($property_name)->value;
        continue;
      }
      $contact_values[$contact_field_name] = $data->get($property_name)->getValue();
    }

    // Determine if there is a URI reference to a user (or user-like entity) to
    // use for the user_uri contact field.
    // If the data has a URI field named 'uid', use it.
    if ($data->getDataDefinition()->getPropertyDefinition('_link_uid')) {
      $uid_data = $data->get('_link_uid');
      if ($uid_data instanceof ListInterface && is_subclass_of($uid_data->getItemDefinition()->getClass(), 'Drupal\Core\TypedData\Type\UriInterface')) {
        $contact_values['user_uri'] = $uid_data->getValue();
      }
    }
    // Use container's schema URI to check if the data is a captured user
    // entity. If it is the case, use the origin URI as a contact's user URI.
    $matches = CollectJson::matchSchemaUri($container->getSchemaUri());
    if ($matches && ($matches['entity_type'] == 'user')) {
      // Same format as would have been returned by getValue() on a uri field.
      $contact_values['user_uri'] = [['value' => $container->getOriginUri()]];
    }

    return $contact_values;
  }

  /**
   * Matches a new contact entity against existing ones.
   *
   * @param \Drupal\crm_core_contact\ContactInterface $template_contact
   *   The new contact entity, with values to use.
   *
   * @return \Drupal\crm_core_contact\ContactInterface|null
   *   An existing contact if successfully matched, otherwise the given contact.
   */
  protected function matchContact(ContactInterface $template_contact) {
    // Match by URI.
    if ($template_contact->hasField('user_uri') && $user_uri = $template_contact->get('user_uri')->value) {
      $existing_contacts = $this->entityManager->getStorage('crm_core_individual')
        ->loadByProperties(['user_uri' => $user_uri]);
      if ($existing_contacts) {
        if (count($existing_contacts) > 1) {
          $this->logger->warning('User URI @uri matched more than one contact.', ['@uri' => $user_uri]);
        }
        return reset($existing_contacts);
      }
    }

    // Use the CRM Core contact matcher.
    /** @var \Drupal\crm_core_match\Matcher\MatcherConfigInterface $matcher */
    $matcher = $this->entityManager->getStorage('crm_core_match')->load($this->getConfigurationItem('matcher'));
    if ($matches = $matcher->match($template_contact)) {
      return $this->entityManager->getStorage('crm_core_individual')
        ->load(reset($matches));
    }

    return NULL;
  }

  /**
   * Updates an existing contact with values from a new contact.
   *
   * @param \Drupal\crm_core_contact\ContactInterface $existing_contact
   *   The existing contact.
   * @param array $new_values
   *   Associative array of new values for some fields, keyed by field names.
   */
  protected function updateContact(ContactInterface $existing_contact, array $new_values) {
    $has_changed = FALSE;
    foreach ($new_values as $field_name => $value) {
      $current_value = $existing_contact->get($field_name)->getValue();
      // Append user URI if unique.
      if ($field_name == 'user_uri') {
        $value = array_unique(array_merge($current_value, $value), SORT_REGULAR);
      }
      // Only change field value and trigger new revision if value has changed.
      if ($value != $current_value) {
        $existing_contact->set($field_name, $value);
        $has_changed = TRUE;
      }
    }
    if ($has_changed) {
      $existing_contact->setNewRevision();
      $existing_contact->save();
    }
  }

  /**
   * Saves a new contact.
   *
   * @param \Drupal\crm_core_contact\ContactInterface $template_contact
   *   The contact to save.
   *
   * @return \Drupal\crm_core_contact\ContactInterface
   *   The given contact, saved.
   */
  protected function saveNewContact(ContactInterface $template_contact) {
    $template_contact->save();
    $this->logger->info('Created new contact %label with id @id.', ['%label' => $template_contact->label(), '@id' => $template_contact->id()]);
    return $template_contact;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['relation'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Relation'),
      '#description' => $this->t('Enter a machine name to identify the relation from the identified contact to the data.'),
      '#default_value' => $this->getConfigurationItem('relation'),
      '#size' => 20,
    );

    // Contact type & matcher selector.
    // @todo: Let matchers list types and provide a matching template.
    $contact_type_options = array();
    foreach ($this->entityManager->getStorage('crm_core_individual_type')->loadMultiple() as $contact_type_id => $contact_type) {
      $contact_type_options[$contact_type_id] = $contact_type->label();
    }
    $contact_type_id = $this->getConfigurationItem('contact_type');

    // @todo Ajaxify content type selector.
    $form['contact_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Contact type'),
      '#description' => $this->t('Select the contact type to be matched or created.'),
      '#options' => $contact_type_options,
      '#default_value' => $contact_type_id,
      '#required' => TRUE,
      '#access' => $contact_type_options,
    );

    $matchers = array();
    // Get the list of available matchers.
    foreach ($this->entityManager->getStorage('crm_core_match')->loadMultiple() as $matcher_id => $matcher) {
      $matchers[$matcher_id] = $matcher->label();
    }
    $matcher_id = $this->getConfigurationItem('matcher');

    // @todo Ajaxify matcher selector.
    $form['matcher'] = array(
      '#type' => 'select',
      '#title' => $this->t('Matcher'),
      '#description' => $this->t('Select the matcher which will be used.'),
      '#options' => $matchers,
      '#default_value' => $matcher_id,
      '#required' => TRUE,
      '#access' => (bool) $matchers,
    );

    if ($matcher_id && $contact_type_id) {
      /** @var \Drupal\crm_core_match\Matcher\MatcherConfigInterface $matcher */
      $matcher = $this->entityManager->getStorage('crm_core_match')->load($matcher_id);

      $form['fields'] = array(
        '#type' => 'table',
        '#caption' => $this->t('For each enabled field in the matcher, you can select a model-provided property to match by.'),
        '#header' => array(
          'contact_field' => $this->t('Contact field'),
          'model_property' => $this->t('Model property'),
        ),
        '#empty' => $this->t('There are no fields enabled yet.'),
      );

      // Add link to matcher configuration.
      $form['fields']['#caption'] = $this->t('For each enabled field in the <a href="@matcher">matcher</a>, you can select a model-provided property to match by.', ['@matcher' => $matcher->url()]);

      // Add a table row for each field in the matcher.
      foreach ($matcher->getPlugin()->getRules() as $field_name => $field) {
        /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
        $field_definition = $field['definition'];

        $form['fields'][$field_name]['contact_field'] = array(
          '#type' => 'item',
          '#value' => $field_name,
          '#markup' => SafeMarkup::checkPlain($field['label']),
        );

        // Display a selector if there are applicable properties.
        $model_property_options = $this->getPropertyDefinitionOptions();
        if (!empty($model_property_options)) {
          $form['fields'][$field_name]['model_property'] = array(
            '#type' => 'select',
            '#title' => $this->t('Model property'),
            '#title_display' => 'hidden',
            '#options' => $model_property_options,
            '#default_value' => $this->getConfigurationItem(['fields', $field_name]),
            '#empty_option' => $this->t('- None -'),
          );
        }
        else {
          $form['fields'][$field_name]['model_property'] = array(
            '#type' => 'item',
            '#markup' => $this->t('No %type property available', ['%type' => $field_definition->getType()]),
            '#value' => NULL,
          );
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Extract the selected model property from each table row in the form
    // values.
    $matching_fields = $form_state->getValue('fields') ?: array();
    $fields = array_filter(array_map(function (array $values) {
      return $values['model_property'];
    }, $matching_fields));

    $this->setConfiguration([
      'relation' => $form_state->getValue('relation'),
      'contact_type' => $form_state->getValue('contact_type'),
      'matcher' => $form_state->getValue('matcher'),
      'fields' => $fields,
    ]);
  }

}
