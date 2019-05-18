<?php

namespace Drupal\github_deploy\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\github_deploy\GithubDeployServices;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\github_deploy\Form
 */
class SettingsForm extends ConfigFormBase {
  // @Todo we might not need any service here.
  protected $services;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, GithubDeployServices $services) {
    parent::__construct($config_factory);
    $this->services = $services;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('github_deploy.services')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'github_deploy_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'github_deploy.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('github_deploy.settings');

    $form['githubDeploy'] = [
      '#type'           => 'fieldset',
      '#title'          => $this->t('Github deploy settings'),
      '#collapsible'    => FALSE,
    ];

    $form['githubDeploy']['secret'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Secret'),
      '#description'    => $this->t('Secret you want to use.'),
      '#default_value'  => $config->get('secret'),
      '#required'       => TRUE,
    ];

    $form['githubDeploy']['gitPath'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Git bin'),
      '#description'    => $this->t('System path to git bin default <code>@path</code>,
      for windows mode set value as <code>@windows</code>.', [
          '@path'       => '/usr/bin/git',
          '@windows'    => 'git',
        ]),
      '#default_value'  => $config->get('gitPath'),
      '#required'       => TRUE,
    ];

    $form['githubDeploy']['whiteListOnly'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Accept callback only from Github.'),
      '#description'    => $this->t('Callback will be accepted based on github whitelist ip\'s.'),
      '#default_value'  => $config->get('whiteListOnly'),
    ];

    $form['githubDeploy']['clearCache'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Clear cache'),
      '#description'    => $this->t('Clear drupal cache after deploy callback completed.'),
      '#default_value'  => $config->get('clearCache'),
    ];

    $form['githubDeploy']['composerInstall'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Execute composer install'),
      '#description'    => $this->t('Execute composer install after deployment.'),
      '#default_value'  => $config->get('composerInstall'),
    ];

    $form['githubDeploy']['featuresRevert'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Revert features'),
      '#description'    => $this->t('Revert all features.'),
      '#default_value'  => $config->get('featuresRevert'),
    ];


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory->getEditable('github_deploy.settings')
      ->set('secret', $form_state->getValue('secret'))
      ->set('gitPath', $form_state->getValue('gitPath'))
      ->set('whiteListOnly', $form_state->getValue('whiteListOnly'))
      ->set('clearCache', $form_state->getValue('clearCache'))
      ->set('composerInstall', $form_state->getValue('composerInstall'))
      ->set('featuresRevert', $form_state->getValue('featuresRevert'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
