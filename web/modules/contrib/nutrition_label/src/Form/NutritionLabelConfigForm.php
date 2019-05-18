<?php

namespace Drupal\nutrition_label\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Form;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Implements a NutritionLabelConfig form.
 */
class NutritionLabelConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nutrition_label_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['nutrition_label.settings'];
  }

  /**
   * Nutrition Label configuration form.
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $nutrition_label_path = _nutrition_label_lib_get_nutrition_label_path();
    if (!$nutrition_label_path) {
      $url = Url::fromUri(NUTRITION_LABEL_WEBSITE_URL);
      $link = Link::fromTextAndUrl($this->t('Nutrition Label jQuery Plugin'), $url)->toString();

      drupal_set_message($this->t('The library could not be detected. You need to download the @nutrition_label and extract the entire contents of the archive into the %path directory on your server.',
        ['@nutrition_label' => $link, '%path' => 'libraries']
      ), 'error');
      return $form;
    }

    // Nutrition Label settings:
    $nutrition_label_conf = $this->configFactory->get('nutrition_label.settings');

    // @todo: Add default settings.
    $form['placeholder'] = [
      '#markup' => 'Default settings will go here.',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Nutrition Label configuration form submit handler.
   *
   * Validates submission by checking for duplicate entries, invalid
   * characters, and that there is an abbreviation and phrase pair.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('nutrition_label.settings');

    //$config
    //  ->set('example', $form_state->getValue('example'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Helper function to get options for enabled themes.
   */
  private function nutrition_label_enabled_themes_options() {
    // @todo: do we need per-theme settings?
    $options = [];

    // Get a list of available themes.
    $theme_handler = \Drupal::service('theme_handler');

    $themes = $theme_handler->listInfo();

    foreach ($themes as $theme_name => $theme) {
      // Only create options for enabled themes.
      if ($theme->status) {
        if (!(isset($theme->info['hidden']) && $theme->info['hidden'])) {
          $options[$theme_name] = $theme->info['name'];
        }
      }
    }

    return $options;
  }

}
