<?php

namespace Drupal\path_alias_xt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Extended path aliases configuration form.
 */
class PathAliasXtSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['path_alias_xt.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'path_alias_xt_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('path_alias_xt.settings');

    $form['user_special'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('For the current user: instead of <em>/user/uid</em> or its alias, apply the alias for <em>/user</em>.'),
      '#default_value' => $config->get('user_special'),
      '#description' => $this->t('If ticked and the system path <em>/user</em> has an <a target="alias" href="@alias">alias</a>, such as <em>/MyAccount</em>, then <em>/MyAccount</em> will also be applied when a user visits their <em>/user/uid/...</em> pages.<br/>For this feature to work you must complete the full installation procedure outlined in the <a href="@README">README</a>.', [
        '@alias' => Url::fromRoute('path.admin_overview')->toString(),
        '@README' => Url::fromUserInput('/' . drupal_get_path('module', 'path_alias_xt') . '/README.txt')->toString(),
      ]),
    ];

    $form['regex_pattern'] = [
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Regular expression to match system paths for nodes, users and taxonomy terms'),
      '#default_value' => $config->get('regex_pattern'),
      '#description' => $this->t("While you can always reset this configuration and recover without permanent damage to your site, a change to this expression may temporarily break all extended aliases. Change only when you know what you're doing."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('path_alias_xt.settings');

    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
