<?php

/**
 * @file
 * Contains \Drupal\packaging\Form\PackagingSettingsForm.
 */

namespace Drupal\packaging\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\packaging\Context;
use Drupal\packaging\Package;
use Drupal\packaging\Product;
use Drupal\packaging\Strategy;


/**
 * Functions needed by shipping quotes modules to administer packaging strategy.
 *
 * @author Tim Rohaly.    <http://drupal.org/user/202830>
 */

// This is similar to how the Rules UI forms work, and may include a rules
// configuration form.

// Each shipping method builds its own PackageContext to store method-specific
// settings:
//   Set max weight per-method
//   Set strategy per-method
//   Set multiple origing/destination per-method

// Detect and use pluging
// Add packaging fields to product types - pkg_qty, dimensions, weight, box type
// Admin for box sizes - need some way for strategies to add admin
//   settings to the packaging admin menu.
// Markups?

// Contributed packaging method at http://drupal.org/node/444442

// Integration with Rules?
// Integration with Views?

// We need a way to save packaging results, so that shipping/label
// generation can use the same set of packages determined here.

/**
 * Configure packaging settings for this site.
 */
class PackagingSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'packaging_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'packaging.settings',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Builds Form API array containing all elements needed to configure and
   * select packaging strategy. This form may be included on the individual
   * shipping quotes configuration pages or may be linked to from a list of
   * available shipping quote methods.
   *
   * @return
   *   Forms for store administrator to set configuration options.
   *
   * @see packaging_admin_settings_validate()
   * @ingroup forms
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $packaging_config = $this->config('packaging.settings');

    // Use CTools to look for any modules which define packaging strategies.
    $operations = packaging_get_strategies();
    $options = array();
    foreach ($operations as $id => $operation) {
      $options[$id] = $operation['admin_label'];
    }
    $default_value = $packaging_config->get('strategy');
    $default_value = isset($default_value) ?  $default_value : reset($options);

    // Form to select packaging strategy.
    $form['packaging_strategy'] = array(
      '#type'          => 'select',
      '#title'         => t('Packaging strategy'),
      '#description'   => t('Select the packaging strategy that is most appropriate to the types of products you sell.'),
      '#element_validate' => array(array($this, 'validateStrategy')),
      '#options'       => $options,
      '#default_value' => $default_value,
      '#ajax' => array(
        'wrapper'  => 'packaging-strategy-description-fieldset-wrapper',
        'callback' => array($this, 'strategyDescriptionCallback'),
      ),
    );

    // This fieldset serves as a container for the a description of the selected
    // packaging strategy.
    $form['packaging_strategy_description'] = array(
      '#type'   => 'fieldset',
      '#title'  => 'Strategy description',
      // These provide the wrapper referred to in #ajax['wrapper'] above.
      '#prefix' => '<div id="packaging-strategy-description-fieldset-wrapper">',
      '#suffix' => '</div>',
    );

    $strategy = $form_state->getValue('packaging_strategy');
    if (empty($strategy)) {
      $strategy = $packaging_config->get('strategy', reset($options));
    }

    if ($instance = packaging_get_instance($strategy)) {
      $description = $instance->getDescription();
    }
    else {
      $description = t('No description available');
    }

    $form['packaging_strategy_description']['description'] = array(
      '#markup' => check_markup($description),
    );

    // Sets maximum allowed package weight.
    $form['packaging_max_weight'] = array(
      '#type'        => 'textfield',
      '#title'       => t('Maximum package weight'),
      '#description' => t('Enter the maximum allowed package weight.'),
    );

    // Defines units for maximum weight, also used as the default weight units
    // for Package objects.
    $form['packaging_weight_units'] = array(
      '#type'        => 'select',
      '#title'       => t('Package weight units'),
      '#description' => t('Select the default weight units to use for packages.'),
      '#options'     => array(
        'lb' => t('Pounds'),
        'oz' => t('Ounces'),
        'kg' => t('Kilograms'),
        'g'  => t('Grams'),
      ),
    );

    // Sets maximum allowed package volume.
    $form['packaging_max_volume'] = array(
      '#type'        => 'textfield',
      '#title'       => t('Maximum package volume'),
      '#description' => t('Enter the maximum allowed package volume.'),
    );

    // Defines units for length, cubed for maximum volume. Also used as the
    // default length units for Package objects.
    $form['packaging_length_units'] = array(
      '#type'        => 'select',
      '#title'       => t('Package length units'),
      '#description' => t('Select the default length units to use for packages.'),
      '#options'     => array(
        'in' => t('Inches'),
        'ft' => t('Feet'),
        'cm' => t('Centimeters'),
        'mm' => t('Millimeters'),
      ),
    );

//    // Register additional submit handler.
//    $form['#submit'][] = 'packaging_admin_settings_submit';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    return parent::validateForm($form, $form_state);
  }

  /**
   * Callback for the select element.
   *
   * This callback selects and returns the packaging_strategy_description
   * fieldset.
   */
  public function strategyDescriptionCallback($form, FormStateInterface $form_state) {
    return $form['packaging_strategy_description'];
  }

  /**
   * Element validation handler for packaging_strategy select element.
   */
  public function validateStrategy($element, FormStateInterface $form_state) {
    $operation = $element['#value'];
    $instance = packaging_get_instance($operation);
    if (!isset($instance)) {
      $form_state->setError($element, t('The %operation strategy could not be located. Please contact the site administrator.', array('%operation' => $operation)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $packaging_config = $this->config('packaging.settings');

    $packaging_config
      ->setData(array(
        'max_weight' => $values['packaging_max_weight'],
        'weight_units' => $values['packaging_weight_units'],
        'max_volume' => $values['packaging_max_volume'],
        'length_units' => $values['packaging_length_units'],
        'strategy' => $values['packaging_strategy'],
      ))
      ->save();

    // Print message for testing purposes - won't do this in release version.
    $operation = $values['packaging_strategy'];
    if ($instance = packaging_get_instance($operation)) {
      $context = new Context();
      $context->setStrategy($instance);
      drupal_set_message("Invoked packageProducts()<pre>" . var_export($context->packageProducts(array(new Product(), new Product())), TRUE) . "</pre>");
    }

    parent::submitForm($form, $form_state);
  }

}
