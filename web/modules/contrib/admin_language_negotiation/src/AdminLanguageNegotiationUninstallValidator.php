<?php

namespace Drupal\admin_language_negotiation;

use Drupal\admin_language_negotiation\Plugin\LanguageNegotiation\AdminLanguageNegotiationUserPermission;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\language\LanguageNegotiator;

/**
 * Verifies that the plugins provided by this module
 * are in use by the main configuration. If so the uninstall
 * process will be stopped.
 *
 */
class AdminLanguageNegotiationUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The language negotiation methods IDs
   */
  const NEGOTIATOR_METHODS = [AdminLanguageNegotiationUserPermission::METHOD_ID];

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\language\LanguageNegotiationMethodInterface
   */
  protected $languageNegotiator;

  /**
   * AdminLanguageNegotiationUninstallValidator constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\language\LanguageNegotiator $languageNegotiator
   */
  public function __construct(ConfigFactoryInterface $configFactory, LanguageNegotiator $languageNegotiator) {
    $this->configFactory = $configFactory;
    $this->languageNegotiator = $languageNegotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module_name) {
    $reasons = [];

    if ($module_name === 'admin_language_negotiation') {
      foreach (self::NEGOTIATOR_METHODS as $negotiation_method) {
        if ($this->languageNegotiator->isNegotiationMethodEnabled($negotiation_method)) {
          $reasons[] = $this->t("The negotiation method id '@negotiation_id' is in use, disable it first.",
                                ['@negotiation_id' => $negotiation_method]);
        }
      }
    }

    return $reasons;
  }

}
