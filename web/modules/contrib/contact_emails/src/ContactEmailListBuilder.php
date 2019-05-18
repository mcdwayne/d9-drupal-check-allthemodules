<?php

namespace Drupal\contact_emails;

use Drupal\contact\ContactFormInterface;
use Drupal\contact_emails\Entity\ContactEmailInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Defines the list builder for tax services.
 */
class ContactEmailListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['contact_form'] = $this->t('Contact form');
    $header['subject'] = $this->t('Subject');
    $header['recipients'] = $this->t('Recipients');
    $header['status'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\contact_emails\Entity\ContactEmailInterface */

    /** @var ContactFormInterface $contact_form */
    $contact_form = $entity->get('contact_form')->entity;

    $row['id'] = $entity->id();
    $row['contact_form'] = $contact_form ? $contact_form->label() : '';
    $row['subject'] = $entity->label();
    $row['recipients'] = $this->getRecipients($entity);
    $row['status'] = ($entity->get('status')->value) ? $this->t('Enabled') : $this->t('Disabled');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    // If this is a list for a particular contact form, set a useful empty
    // message.
    if ($contact_form = \Drupal::routeMatch()->getParameter('contact_form')) {
      $build['table']['#empty'] = $this->t('The default contact emails are being used. <a href=":url_edit">Modify the default emails here</a> or <a href=":url_create">override them with new contact emails</a>.', [
        ':url_edit' => Url::fromRoute('entity.contact_form.edit_form', ['contact_form' => $contact_form])->toString(),
        ':url_create' => Url::fromRoute('entity.contact_email.add_form', ['contact_form' => $contact_form])->toString(),
      ]);
    }
    return $build;
  }

  /**
   * Gets the recipient text to display.
   *
   * @param \Drupal\contact_emails\Entity\ContactEmailInterface $entity
   *   The contact email entity.
   *
   * @return string
   *   The recipients text.
   */
  protected function getRecipients(ContactEmailInterface $entity) {
    switch ($entity->get('recipient_type')->value) {
      case 'default':
        $value = $this->t('[The site email address]');
        break;

      case 'submitter':
        $value = $this->t('[The submitter of the form]');
        break;

      case 'field':
        $value = $this->recipientFieldValue($entity, 'recipient_field', 'email');
        break;

      case 'reference':
        $value = $this->recipientFieldValue($entity, 'recipient_reference', 'entity_reference');
        break;

      case 'manual':
      default:
        $recipients = [];
        foreach ($entity->get('recipients')->getValue() as $value) {
          $recipients[] = $value['value'];
        }

        $value = implode(', ', $recipients);
        break;
    }

    return $value;
  }

  /**
   * Get the description of recipient field value.
   *
   * @param ContactEmailInterface $entity
   *   The email.
   * @param string $fieldName
   *   The field name.
   * @param string $fieldType
   *   The field type.
   *
   * @return Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description of the field.
   */
  protected function recipientFieldValue(ContactEmailInterface $entity, $fieldName, $fieldType) {
    /** @var \Drupal\contact_emails\ContactEmails $contactEmails */
    $contactEmails = \Drupal::service('contact_emails.helper');

    $contactFormId = $entity->get('contact_form')->target_id;

    $fields = $contactEmails->getContactFormFields($contactFormId, $fieldType);

    $field_label = (
      $entity->hasField($fieldName)
      && !$entity->get($fieldName)->isEmpty()
      && isset($fields[$entity->get($fieldName)->value])
    )
      ? $entity->get($fieldName)->value
      : $this->t('*Unknown or deleted field*');

    return $this->t('[The value of the "@field" field]', [
      '@field' => $field_label,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityIds() {
    $query = $this->getStorage()->getQuery();

    // Maybe filter by the selected contact form.
    if ($contact_form_id = \Drupal::routeMatch()->getParameter('contact_form')) {
      $query->condition('contact_form', $contact_form_id);
    }

    // Order by the id.
    $query->sort($this->entityType->getKey('id'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

}
