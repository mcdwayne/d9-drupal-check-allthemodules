<?php

namespace Drupal\domain_facebook_pixel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\domain\DomainStorage;

/**
 * Class FacebookPixelConfigForm.
 *
 * @package Drupal\domain_facebook_pixel\Form
 */
class DomainFacebookPixelConfigForm extends ConfigFormBase {

  /**
   * Domain storage.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * The config object for the domain_facebook_pixel settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Construct function.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory load.
   *
   * @param \Drupal\domain\DomainStorageInterface $domainStorage
   *   The domain loader.
   */
  public function __construct(DomainStorage $domainStorage) {
    $this->domainStorage = $domainStorage;
  }


  /**
   * Create function return static domain loader configuration.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Load the ContainerInterface.
   *
   * @return \static
   *   return domain loader configuration.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('domain')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'domain_facebook_pixel.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_facebook_pixel_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('domain_facebook_pixel.settings');
    $domains = $this->domainStorage->loadMultipleSorted();
    $link = Link::fromTextAndUrl($this->t('Pixel'), Url::fromUri('https://developers.facebook.com/docs/marketing-api/audiences-api/pixel'));
    foreach ($domains as $domain) {
      $domainId = $domain->id();
      $hostname = $domain->get('name');
      $default_value = '';
      if (!empty($config->get($domainId . '_domain_facebook_id'))) {
        $default_value = $config->get($domainId . '_domain_facebook_id');
      }

      $form[$domainId] = [
        '#type' => 'details',
        '#title' => $this->t('Facebook ID for "@domain"', ['@domain' => $hostname]),
      ];

      $form[$domainId][$domainId . '_domain_facebook_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Facebook ID'),
        '#description' => $this->t('Using the Marketing API with the Facebook @Pixel.', ['@Pixel' => $link->toString()]),
        '#default_value' => $default_value,
      ];
    }
    if (count($domains) === 0) {
      $form['domain_facebook_pixel_message'] = [
        '#markup' => $this->t('Zero domain records found. Please @link to create the domain.', ['@link' => $this->l($this->t('click here'), Url::fromRoute('domain.admin'))]),
      ];
      return $form;
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('domain_facebook_pixel.settings');
    $domains = $this->domainStorage->loadOptionsList();
    foreach ($domains as $key => $value) {
      $config->set($key . '_domain_facebook_id', $form_state->getValue($key . '_domain_facebook_id'))
        ->save();
    }
    parent::submitForm($form, $form_state);

  }

}
