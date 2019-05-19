<?php

namespace Drupal\user_points_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use \Symfony\Component\HttpFoundation\Request;

/**
 * Class UserPointsLoginSettingsForm
 *
 * @package Drupal\login_security\Form
 */
class UserPointsLoginSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_points_login_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'user_points_login.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('user_points_login.settings');
    $form['login_points'] = array(
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Points Awarded'),
      '#default_value' => $config->get('login_points'),
      '#description' => t('No. of points rewarded after a user logs in'),
    );
    $form['frequency'] = array(
      '#type' => 'number',
      '#min' => 1,
      '#title' => $this->t('Frequency'),
      '#default_value' => $config->get('frequency'),
      '#description' => t('No. of hours after which points are rewarded'),
    );
    return parent::buildForm($form,$form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('user_points_login.settings')
      ->set('login_points', $form_state->getValue('login_points'))
      ->set('frequency', $form_state->getValue('frequency'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
