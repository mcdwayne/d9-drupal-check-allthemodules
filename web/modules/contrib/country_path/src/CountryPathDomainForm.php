<?php

namespace Drupal\country_path;

use Drupal\Core\Entity\EntityInterface;
use Drupal\domain\DomainForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\domain\Entity\Domain;

/**
 * Overrides domain entity form.
 */
class CountryPathDomainForm extends DomainForm {

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\domain\DomainInterface $entity */
    $this->parseCountryPath($this->entity, $form_state);
    $entity = $this->entity;
    $hostname = $entity->getHostname();
    $domainId = $entity->getDomainId();
    $errors = $this->validator->validate($hostname);
    if (!empty($errors)) {
      // Render errors to display as message.
      $message = [
        '#theme' => 'item_list',
        '#items' => $errors,
      ];
      $message = $this->renderer->renderPlain($message);
      $form_state->setErrorByName('hostname', $message);
    }

    $existing = $this->domainStorage->loadByProperties(
      [
        'hostname'  => $hostname,
        'domain_id' => $domainId,
      ]
    );
    $existing = reset($existing);
    // If we have already registered a hostname,
    // make sure we don't create a duplicate.
    // We cannot check id() here, as the machine name is editable.
    if ($existing && $domainId != $entity->getDomainId()) {
      $form_state->setErrorByName('hostname', $this->t('The hostname is already registered.'));
    }
  }

  /**
   * Parse country path from hostname.
   *
   * Explode input hostname using / as delimiter. Returns only 1 value after /.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Domain entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Domain form state.
   */
  public function parseCountryPath(EntityInterface &$entity, FormStateInterface &$form_state) {
    if ($entity->getEntityTypeId() == 'domain' && $entity instanceof Domain) {
      $form_values = $form_state->getValues();
      list($hostname, $domain_path) = array_pad(explode('/', $form_values['hostname']), 2, NULL);
      if (empty($hostname) || empty($domain_path)) {
        return;
      }
      $entity->setHostname($hostname);
      $entity->set('domain_path', $domain_path);
      $form_state->setValue('hostname', $hostname);
      $form_state->setValue('domain_path', $domain_path);
    }
  }

}
