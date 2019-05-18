<?php

namespace Drupal\country_path;

use Drupal\Core\Entity\EntityInterface;
use Drupal\domain_alias\DomainAliasForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\domain_alias\Entity\DomainAlias;
use Drupal\domain_alias\Entity\DomainAliasInterface;

/**
 * Overrides domain entity form.
 */
class CountryPathDomainAliasForm extends DomainAliasForm {

    /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $domains = $this->domainStorage->loadMultipleSorted();
    $environments = $this->environmentOptions();
    $rows = [];
    foreach ($domains as $domain) {
      // If the user cannot edit the domain, then don't show in the list.
      $access = $this->accessHandler->checkAccess($domain, 'update');
      if ($access->isForbidden()) {
        continue;
      }
      $row = [];
      $row[] = $domain->label();

      foreach ($environments as $environment) {
        $match_output = [];
        if ($environment == 'default') {
          $default = $domain->getCanonical();
          $domain_suffix = $domain->getThirdPartySetting('country_path', 'domain_path');
          if (!empty($domain_suffix)) {
            $default .= "/$domain_suffix";
          }
          $match_output[] = $default;
        }

        $matches = $this->aliasStorage->loadByEnvironmentMatch($domain, $environment);
        foreach ($matches as $match) {
          $match_output[] = $match->getPattern();
        }

        $output = [
          '#items' => $match_output,
          '#theme' => 'item_list',
        ];
        $row[] = \Drupal::service('renderer')->render($output);
      }

      $rows[] = $row;
    }

    $form['environment_help']['table'] = [
      '#type' => 'table',
      '#header' => array_merge([$this->t('Domain')], $environments),
      '#rows' => $rows,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\domain_alias\DomainAliasInterface $entity */
    $this->parseCountryPath($this->entity, $form_state);

    $entity = $this->entity;
    $errors = $this->validator->validate($entity);
    if (!empty($errors)) {
      // Render errors to display as message.
      $form_state->setErrorByName('pattern', $errors);
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
    if ($entity->getEntityTypeId() == 'domain_alias' && $entity instanceof DomainAlias) {
      $form_values = $form_state->getValues();
      list($hostname, $domain_path) = array_pad(explode('/', $form_values['pattern']), 2, NULL);

      if ($form_values['environment'] != 'default') {
        /** @var \Drupal\domain\Entity\Domain $domain */
        $domain = $this->domainStorage->load($entity->getDomainId());
        $country_path = $domain->getThirdPartySetting('country_path', 'domain_path');
        if (!empty($country_path)) {
          $country_path = "/$country_path";

          if (
            strpos($hostname, $country_path) === false
            && substr($hostname, -1) != '*'
          ) {
            $entity->set('pattern', $hostname . $country_path);
            $form_state->setValue('pattern', $hostname . $country_path);
          }
        }
      }
    }
  }
}
