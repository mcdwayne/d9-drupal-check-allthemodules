<?php

namespace Drupal\claim_on_registration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManager;

/**
 * Class Admin Claim On Registration.
 *
 * @package Drupal\claim_on_registration\Form
 */
class AdminClaimOnRegistration extends ConfigFormBase {

  /**
   * Entity Manager.
   *
   * Provided by service container, used to find all help nodes pointing to the
   * current node.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  private $entityManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
      EntityManager $entityManager
    ) {
    parent::__construct($config_factory);
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'claim_on_registration.adminclaimonregistration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_claim_on_registration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('claim_on_registration.adminclaimonregistration');
    // Get Content types.
    $content_types = $this->entityManager->getStorage('node_type')->loadMultiple();
    $options = [];
    if (!empty($content_types)) {
      foreach ($content_types as $content_type) {
        $options[$content_type->id()] = $content_type->label();
      }
    }

    $form['select_content_type'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Select Content type(s)'),
      '#description' => $this->t('You will have to add the create content type permission for the selected content types for anonymous users.'),
      '#options' => $options,
      '#size' => 5,
      '#default_value' => $config->get('select_content_type'),
      '#required' => TRUE,
    ];

    $default_cookie = $config->get('cookie_expiry');
    if (empty($default_cookie)) {
      $default_cookie = '432000';
    }

    $form['cookie_expiry'] = [
      '#title' => $this->t('Cookie Expiry'),
      '#type' => 'number',
      '#description' => $this->t('This is time() + value See http://php.net/manual/en/function.setcookie.php Example enter 3600 to expire in one hour - or 86400 For 1 day. Default is 5 days.'),
      '#default_value' => $default_cookie,
      '#required' => TRUE,
    ];

    $default_cookie_name = $config->get('cookie_name');
    if (empty($default_cookie_name)) {
      $default_cookie_name = 'claim_on_registration';
    }

    $form['cookie_name'] = [
      '#title' => $this->t('Cookie Name'),
      '#type' => 'textfield',
      '#description' => $this->t('This is the cookie name.'),
      '#default_value' => $default_cookie_name,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Check cookie_expiry is an int.
    $cookie_raw = $form_state->getValue('cookie_expiry');
    if (!is_numeric($cookie_raw)) {
      $form_state->setErrorByName('cookie_expiry', $this->t('You must enter a number.'));
    }

    $cookie_name = $form_state->getValue('cookie_name');
    if (!is_string($cookie_name)) {
      $form_state->setErrorByName('cookie_name', $this->t('You must enter a string.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('claim_on_registration.adminclaimonregistration')
      ->set('select_content_type', $form_state->getValue('select_content_type'))
      ->set('cookie_expiry', $form_state->getValue('cookie_expiry'))
      ->set('cookie_name', $form_state->getValue('cookie_name'))
      ->save();
  }

}
