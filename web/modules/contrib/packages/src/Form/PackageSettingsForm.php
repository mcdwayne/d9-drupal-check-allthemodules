<?php

namespace Drupal\packages\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\packages\PackagesInterface;
use Drupal\packages\Plugin\PackageInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;

/**
 * Class PackageSettingsForm.
 *
 * Provides the settings form for configurable packages.
 *
 * @package Drupal\packages\Form
 */
class PackageSettingsForm extends FormBase {

  /**
   * The packages service.
   *
   * @var \Drupal\packages\PackagesInterface
   */
  protected $packages;

  /**
   * Constructor.
   *
   * @param \Drupal\packages\PackagesInterface $packages
   *   The packages service.
   */
  public function __construct(PackagesInterface $packages) {
    $this->packages = $packages;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('packages')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'package_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PackageInterface $package = NULL) {
    // Add the package form.
    $package->settingsForm($form, $form_state);

    // Store the package in the form.
    // TOOO: Right way to pass this along?
    $form['#package_id'] = $package->getPluginId();

    // Add form actions.
    $form['actions'] = [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save settings'),
      ],
      'disable' => [
        '#type' => 'submit',
        '#value' => $this->t('Disable'),
        '#submit' => ['::disable'],
        '#limit_validation_errors' => [],
      ],
      'cancel' => [
        '#type' => 'link',
        '#title' => $this->t('Cancel'),
        '#url' => Url::fromRoute('packages.form'),
      ],
    ];

    // Cache per user.
    $form['#cache'] = [
      'contexts' => ['user'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate the package.
    $this->packages->getPackage($form['#package_id'])->validateSettingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submit the package and receive the updated settings.
    $settings = $this->packages->getPackage($form['#package_id'])->submitSettingsForm($form, $form_state);

    // Set the settings on the package state.
    $this->packages->getState($form['#package_id'])->setSettings($settings);

    // Save the package states.
    $this->packages->saveStates();

    // Alert the user.
    drupal_set_message($this->t('The settings have been saved.'));

    // Redirect back to the package list.
    $form_state->setRedirect('packages.form');
  }

  /**
   * Form submit callback to disable the package being edited.
   */
  public function disable(array &$form, FormStateInterface $form_state) {
    // Disable the package.
    $this->packages->getState($form['#package_id'])->disable();

    // Save the package states.
    $this->packages->saveStates();

    // Alert the user.
    drupal_set_message($this->t('The package has been disabled.'));

    // Redirect back to the package list.
    $form_state->setRedirect('packages.form');
  }

  /**
   * Title callback.
   */
  public function title(PackageInterface $package = NULL) {
    return $this->t('%package settings', ['%package' => $package->getPluginDefinition()['label']]);
  }

  /**
   * Access callback.
   */
  public function access(PackageInterface $package = NULL) {
    // Default to forbidden.
    $result = AccessResult::forbidden();

    // Check that this package is configurable.
    if ($package->getPluginDefinition()['configurable']) {
      // Load the package state.
      $state = $this->packages->getState($package->getPluginId());

      // Make sure this package is active.
      if ($state->isActive()) {
        // Access is granted.
        $result = AccessResult::allowed();
      }
    }

    return $result->cachePerUser();
  }

}
