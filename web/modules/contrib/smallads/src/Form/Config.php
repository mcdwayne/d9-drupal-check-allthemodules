<?php

namespace Drupal\smallads\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for configuring this module.
 */
class Config extends ConfigFormBase {

  /**
   * @var RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * @param ConfigFactoryInterface $config_factory
   * @param RouteBuilderInterface $router_builder
   */
  function __construct(\Drupal\Core\Config\ConfigFactoryInterface $config_factory, RouteBuilderInterface $router_builder) {
    parent::__construct($config_factory);
    $this->routeBuilder = $router_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smallads_config_form';
  }

  /**
   * {@inheritdoc}
   *
   * @todo compose emails for expiry
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('smallads.settings');
    $form['default_expiry'] = [
      '#title' => $this->t('Default expiry date'),
      '#description' => $this->t('Default value of the small ad expiry form widget'),
      '#type' => 'select',
      '#options' => [
        // '' => t('Permanent'),the date widget can't handle empty''.
        '+1 week' => $this->t('1 week'),
        '+1 month' => $this->t('1 month'),
        '+3 months' => $this->t('@count months', ['@count' => 3]),
        '+6 months' => $this->t('@count months', ['@count' => 6]),
        '+1 year' => $this->t('1 year'),
      ],
      '#default_value' => $config->get('default_expiry'),
    ];

    // Since there is a datatype for storing email templates I'm surprised there's no form widget
    $form['expiry_mail'] = [
      '#title' => $this->t('Expiry mail'),
      '#description' => $this->t('This mail is sent to the owner when an ad passes its expiry date.'),
      '#type' => 'fieldset',
      '#open' => TRUE,
      'subject' => [
        '#title' => $this->t('Subject line for the expiry mail'),
        '#type' => 'textfield',
        '#default_value' => $config->get('expiry_mail')['subject'],
        '#weight'=> 0
      ],
      'body' => [
        '#title' => $this->t('Template of the expiry email'),
        '#title' => $this->t('Use Smallads, User and Site tokens, e.g. [smallad:link], [user:edit-url] [site:name])'),
        '#type' => 'textarea',
        '#default_value' => $config->get('expiry_mail')['body'],
        '#weight'=> 1
      ]
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('smallads.settings');
    $mail = [
      'subject' => $form_state->getValue('subject'),
      'body' => $form_state->getValue('body')
    ];
    $config
      ->set('default_expiry', $form_state->getValue('default_expiry'))
      ->set('expiry_mail', $mail)
      ->save();

    parent::submitForm($form, $form_state);
    $this->routeBuilder->rebuild();

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['smallads.settings'];
  }

}
