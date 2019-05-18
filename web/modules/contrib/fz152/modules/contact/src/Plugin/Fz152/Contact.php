<?php

namespace Drupal\fz152_contact\Plugin\Fz152;

use Drupal\fz152\Fz152PluginBase;
use Drupal\fz152_contact\Form\Fz152ContactSettings;

/**
 * Provides an annotated Fz152 plugin to configure contact form.
 *
 * @Fz152(
 *   id = "contact",
 * )
 */
class Contact extends Fz152PluginBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function getSettingsPage() {
    return [
      'path' => 'contact',
      'title' => 'Contact',
      'form' => Fz152ContactSettings::class,
      'weight' => 5,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getForms() {
    $forms = [];
    $contact_forms = $this->getContactForms();
    foreach (array_keys($contact_forms) as $form) {
      $config = $this->config("fz152_contact.settings.$form");
      if ($config->get('enabled')) {
        $forms[] = [
          'form_id' => 'contact_message_' . $form . '_form',
          'weight' => $config->get('weight') ?: NULL,
        ];
      }
    }
    return $forms;
  }

  /**
   * Returns a list of contact forms.
   *
   * @return array
   *   A keyed array of contact forms.
   *
   * @see \Drupal\Core\Entity\EntityTypeBundleInfoInterface::getBundleInfo()
   */
  protected function getContactForms() {
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo */
    $bundleInfo = \Drupal::service('entity_type.bundle.info');
    return $bundleInfo->getBundleInfo('contact_message');
  }

  /**
   * Retrieves a configuration object.
   *
   * @param string $name
   *   The config name to retrieve.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The config object.
   */
  protected function config($name) {
    if (!isset($this->configFactory)) {
      $this->configFactory = \Drupal::service('config.factory');
    }
    return $this->configFactory->get($name);
  }

}
