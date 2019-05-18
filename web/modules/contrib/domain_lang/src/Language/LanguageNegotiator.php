<?php

namespace Drupal\domain_lang\Language;

use Drupal\language\LanguageNegotiator as BaseLanguageNegotiator;

/**
 * Class responsible for performing language negotiation.
 */
class LanguageNegotiator extends BaseLanguageNegotiator {

  /**
   * {@inheritdoc}
   */
  protected function getEnabledNegotiators($type) {
    if ($domain = \Drupal::service('domain.negotiator')->getActiveDomain()) {
      return $this->configFactory->get('domain.config.' . $domain->id() . '.language.types')->get('negotiation.' . $type . '.enabled') ?: [];
    }

    return parent::getEnabledNegotiators($type);
  }

}
