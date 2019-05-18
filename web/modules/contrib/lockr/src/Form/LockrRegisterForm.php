<?php

namespace Drupal\lockr\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Lockr\Lockr;

use Drupal\lockr\CertWriter;
use Drupal\lockr\SettingsFactory;

class LockrRegisterForm implements ContainerInjectionInterface, FormInterface {

  /** @var Lockr */
  protected $lockr;

  /** @var SettingsFactory */
  protected $settingsFactory;

  /** @var ConfigFactoryInterface */
  protected $configFactory;

  public function __construct(
    Lockr $lockr,
    SettingsFactory $settings_factory,
    ConfigFactoryInterface $config_factory
  ) {
    $this->lockr = $lockr;
    $this->settingsFactory = $settings_factory;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('lockr.lockr'),
      $container->get('lockr.settings_factory'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lockr_register_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'lockr/register';
    $form['#attached']['drupalSettings']['lockr'] = [
      'site_name' => $this->getSiteName(),
      'accounts_host' => 'https://accounts.lockr.io',
    ];

    $form['client_token'] = [
      '#type' => 'hidden',
      '#required' => TRUE,
    ];

    $form['register'] = [
      '#type' => 'button',
      '#value' => 'Register Site',
      '#attributes' => [
        'class' => ['register-site'],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
      '#attributes' => [
        'class' => ['register-submit'],
      ],
    ];

    return $form;
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
    $client_token = $form_state->getValue('client_token');
    $partner = $this->settingsFactory->getPartner();
    try {
      if (is_null($partner)) {
        $dn = [
          'countryName' => 'US',
          'stateOrProvinceName' => 'Washington',
          'localityName' => 'Tacoma',
          'organizationName' => 'Lockr',
        ];
        $result = $this->lockr->createCertClient($client_token, $dn);
        CertWriter::writeCerts('dev', $result);
        $config = $this->configFactory->getEditable('lockr.settings');
        $config->set('custom', TRUE);
        $config->set('cert_path', 'private://lockr/dev/pair.pem');
        $config->save();
      }
      elseif ($partner['name'] === 'pantheon') {
        $this->lockr->createPantheonClient($client_token);
      }
    }
    catch (\Exception $e) {
      // XXX: probably log and/or show message
      throw $e;
      return;
    }
    drupal_set_message("That's it! You're signed up with Lockr; your keys are now safe.");
    $form_state->setRedirect('entity.key.collection');
  }

  /**
   * Get the human readable name of the site.
   */
  protected function getSiteName() {
    return $this->configFactory->get('system.site')->get('name');
  }

}
