<?php

namespace Drupal\visualn\Core;

use Drupal\visualn\Core\VisualNPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for VisualN Drawer Skin plugins.
 *
 * @see \Drupal\visualn\Core\DrawerSkinInterface
 *
 * @ingroup drawer_skin_plugins
 */
abstract class DrawerSkinBase extends VisualNPluginBase implements DrawerSkinInterface {

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Clean submitted values
    $drawer_config = $this->extractFormValues($form, $form_state);
    $form_state->setValues($drawer_config);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    //The skin_config from form_state should not change the plugin configuration,
    //it is only used to build the form according to that config.
    $skin_config = $this->extractFormValues($form, $form_state);

    $form['markup'] = [
      '#markup' => '<div>' . t('No configuration provided for this skin') . '</div>',
    ];

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function extractFormValues($form, FormStateInterface $form_state) {
    // Since it is supposed to be subform_state, get all the values without limiting the scope.
    return $form_state->getValues();
  }

}
