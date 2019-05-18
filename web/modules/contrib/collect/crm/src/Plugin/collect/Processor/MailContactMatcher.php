<?php
/**
 * @file
 * Contains
 *   \Drupal\collect_crm\Plugin\collect\Processor\MailContactMatcher.
 */

namespace Drupal\collect_crm\Plugin\collect\Processor;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\inmail\Plugin\DataType\Mailbox;

/**
 * Matches CRM Core contacts, with special support for Mailbox data.
 *
 * @Processor(
 *   id = "contact_matcher_mail",
 *   label = @Translation("Mail contact matcher"),
 *   description = @Translation("Matches/creates CRM Core Contact entities from email data.")
 * )
 */
class MailContactMatcher extends ContactMatcher {

  /**
   * Fields defined for the selected contact type.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface[]
   */
  protected $contactFields;

  /**
   * {@inheritdoc}
   */
  protected function readContactValuesFromData(CollectDataInterface $data, CollectContainerInterface $container) {
    if (!\Drupal::moduleHandler()->moduleExists('inmail')) {
      return array();
    }

    $contacts_values = array();

    // Find Mailbox properties.
    foreach ($data as $property_name => $property) {
      /** @var \Drupal\Core\TypedData\TypedDataInterface $property */

      // Get the names of the fields for which this mailbox property has been
      // selected. Normally it will be one string field and/or one email
      // field, or none.
      $fields = array_keys($this->getConfigurationItem('fields'), $property_name);
      if (empty($fields)) {
        continue;
      }

      // List of mailboxes.
      if ($property instanceof ListInterface && $this->definitionIsMailbox($property->getItemDefinition())) {
        foreach ($property as $item) {
          $contacts_values += $this->getMailboxValues($item, $fields);
        }
      }
      // Single mailbox.
      elseif ($property instanceof Mailbox) {
        $contacts_values += $this->getMailboxValues($property, $fields);
      }
    }

    return $contacts_values;
  }

  /**
   * Recognizes a data object of type Mailbox.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $data_definition
   *   The data definition of the data object.
   *
   * @return bool
   *   Whether the data is of type Mailbox.
   */
  protected function definitionIsMailbox(DataDefinitionInterface $data_definition) {
    $mailbox_class = 'Drupal\inmail\Plugin\DataType\Mailbox';
    $data_class = $data_definition->getClass();
    return $data_class == $mailbox_class || is_subclass_of($data_class, $mailbox_class);
  }

  /**
   * Returns the mailbox values matching the type of each given field.
   *
   * @param \Drupal\inmail\Plugin\DataType\Mailbox $property
   *   A mailbox data object.
   * @param array $fields
   *   A list of field names for which mailbox properties should be returned.
   *
   * @return array
   *   Mailbox values.
   */
  protected function getMailboxValues(Mailbox $property, array $fields) {
    // Load the given contact field.
    if (!isset($this->contactFields)) {
      $this->contactFields = $this->entityManager->getFieldStorageDefinitions('crm_core_individual');
    }

    $values = array();
    foreach ($fields as $field_name) {
      if (!isset($this->contactFields[$field_name])) {
        continue;
      }
      switch ($this->contactFields[$field_name]->getType()) {
        case 'string':
        case 'name':
          $values[$field_name]['given'] = $property->get('name')->getValue();
          break;

        case 'email':
          $values[$field_name] = $property->get('address');
          break;

        default:
          // Misconfigured field mapping.
          $this->logger->warning('Email participant data has no property of type @type (selected for contact field @field).', [
            '@type' => $this->contactFields[$field_name]->getType(),
            '@field' => $field_name,
          ]);
      }
    }
    return $values;
  }

}
