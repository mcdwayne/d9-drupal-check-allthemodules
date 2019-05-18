<?php

namespace Drupal\login_disable\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoginDisableSettingsForm.
 *
 * @package Drupal\login_disable\Form
 */
class LoginDisableSettingsForm extends ConfigFormBase {

  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->configFactory = $configFactory->getEditable('login_disable.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_disable_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    foreach (Element::children($form) as $variable) {
      $this->configFactory->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $this->configFactory->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['login_disable.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['login_disable_is_active'] = [
      '#type' => 'checkbox',
      '#title' => 'Prevent user log in',
      '#description' => $this->t('When active the user login form will be disabled for everyone. For roles granted bypass rights they must use the access key defined below.'),
      '#default_value' => (bool) $this->configFactory->get('login_disable_is_active'),
    ];

    $form['login_disable_key'] = [
      '#title' => $this->t('Access key (optional)'),
      '#description' => $this->t('For added security, a word can be required to be added to the URL.'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $this->configFactory->get('login_disable_key'),
    ];
    if (!empty($form['login_disable_key']['#default_value'])) {
      $form['login_disable_key']['#description'] .= '<br />' . $this->t('The URL to use to log in is: @url', [
        '@url' => Url::fromRoute('user.login')->toString() . '?' . $form['login_disable_key']['#default_value'],
      ]);
    }

    $form['login_disable_message'] = [
      '#title' => $this->t('End-user message when login is disabled'),
      '#description' => $this->t('Adding this word to the end of the @url url will allow access to the log in form.', [
        '@url' => 'user/login?' . $this->configFactory->get('login_disable_key'),
      ]),
      '#type' => 'textfield',
      '#size' => 80,
      '#default_value' => $this->configFactory->get('login_disable_message'),
    ];

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

}
