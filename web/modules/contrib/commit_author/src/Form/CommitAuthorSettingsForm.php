<?php
/**
 * @file
 * Contains \Drupal\commit_author\Form\SettingsForm
 */

namespace Drupal\commit_author\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class CommitAuthorSettingsForm extends ConfigFormBase {
  public function getFormId() {
    return 'commit_author_settings_form';
  }

  protected function getEditableConfigNames() {
    return ['commit_author.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commit_author.settings');

    $form['commit_author_show_core'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show author if notice is from drupal core (including themes from the core).'),
      '#default_value' => $config->get('commit_author_show_core'),
    );

    $form['commit_author_show_contrib'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show author if notice is from contrib modules.'),
      '#default_value' => $config->get('commit_author_show_contrib'),
    );

    $form['commit_author_show_custom'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show author if notice is from custom modules.'),
      '#default_value' => $config->get('commit_author_show_custom'),
    );

    $form['commit_author_show_theme'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show author if notice is from themes.'),
      '#default_value' => $config->get('commit_author_show_theme'),
    );

    $form['commit_author_not_author'] = array(
      '#type' => 'checkbox',
      '#title' => t("Do not show author if message is 'Not Committed Yet'"),
      '#default_value' => $config->get('commit_author_not_author'),
    );

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('commit_author.settings');

    $exist_values = $config->getRawData();
    $new_values = $form_state->getValues();

    foreach($exist_values as $key => $value) {
      if (isset($new_values[$key])) {
        $config->set($key, $new_values[$key]);
      }
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }
}
