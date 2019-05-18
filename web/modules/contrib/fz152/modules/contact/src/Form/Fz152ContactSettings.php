<?php

namespace Drupal\fz152_contact\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure example settings for this site.
 */
class Fz152ContactSettings extends ConfigFormBase {

  /**
   * The entity bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fz152_contact_settings';
  }

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $bundleInfo
   *   The entity bundle info.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeBundleInfo $bundleInfo) {
    parent::__construct($config_factory);
    $this->bundleInfo = $bundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    $forms = $this->getContactBundles();
    $names = [];
    foreach (array_keys($forms) as $form) {
      $names[] = "fz152_contact.settings.$form";
    }
    return $names;
  }

  /**
   * Returns a list of contact forms.
   *
   * @return array
   *   A keyed array of contact forms.
   *
   * @see \Drupal\Core\Entity\EntityTypeBundleInfoInterface::getBundleInfo()
   */
  protected function getContactBundles() {
    return $this->bundleInfo->getBundleInfo('contact_message');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $contact_bundles = $this->getContactBundles();

    foreach ($contact_bundles as $bundle_name => $bundle_info) {
      $config = $this->config("fz152_contact.settings.$bundle_name");
      $form['contact_' . $bundle_name . '_enable'] = [
        '#type' => 'checkbox',
        '#title' => $bundle_info['label'] . " ($bundle_name)",
        '#default_value' => $config->get('enabled'),
      ];

      $form['contact_' . $bundle_name . '_weight'] = [
        '#type' => 'number',
        '#title' => 'Weight of element',
        '#default_value' => $config->get('weight'),
        '#states' => [
          'visible' => [
            'input[name="contact_' . $bundle_name . '_enable"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $forms = $this->getContactBundles();

    foreach (array_keys($forms) as $form_name) {
      $this->config("fz152_contact.settings.$form_name")
        ->set('enabled', $form_state->getValue('contact_' . $form_name . '_enable'))
        ->set('weight', $form_state->getValue('contact_' . $form_name . '_weight'))
        // Because all fz15 modules configs comes with Russian configs as
        // default. It's necessary to work config_translation module.
        ->set('langcode', 'ru')
        ->save();
    }
    parent::submitForm($form, $form_state);
  }

}
