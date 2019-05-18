<?php

namespace Drupal\demandbase_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\KeyRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\demandbase_api\DemandbaseApiConnector;

/**
 * Class DemandbaseApiTestForm.
 */
class DemandbaseApiTestForm extends FormBase {

  /**
   * Drupal\demandbase_api\DemandbaseApiConnector definition.
   *
   * @var \Drupal\demandbase_api\DemandbaseApiConnector
   */
  protected $demandbaseApiConnector;

  protected $keyRepository;

  protected $config;

  /**
   * Constructs a new DemandbaseApiTestForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, KeyRepositoryInterface $key_repository,
    DemandbaseApiConnector $demandbase_api_connector
  ) {
    $this->config = $config_factory->get('demandbase_api.settings');
    $this->keyRepository = $key_repository;
    $this->demandbaseApiConnector = $demandbase_api_connector;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('key.repository'),
      $container->get('demandbase_api.connector')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'demandbase_api_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $enabled = false;
    if($api_key = $this->config->get('api_key')) {
      if($key = $this->keyRepository->getKey($api_key)) {
        if($key->getKeyValue()) {
         $enabled = true;
        }
      }
    }
    //@todo: add description
    if($enabled) {
      $form['ip'] = [
        '#type' => 'textfield',
        '#title' => $this->t('IP'),
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ];
      if($last_request_data = $form_state->get('last_request_data')) {
        $items = [];
        foreach($last_request_data as $key => $value) {
          $items[] = [$key, $value];
        }
        $form['result'] = [
          '#theme' => 'table',
          '#header' => [$this->t('Label'), $this->t('Value')],
          '#rows' => $items,
        ];
      }
    }
    else {
      $settings_link = \Drupal::l($this->t('Demandbase API Settings'), \Drupal\Core\Url::fromRoute('demandbase_api.settings_form'));
      drupal_set_message($this->t('You must first configure the @link.', ['@link' => $settings_link]), 'warning');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //@todo: validate ip if ip entered
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->set('last_request_data', $this->demandbaseApiConnector->getCompanyData($form_state->getValue('ip')));
    $form_state->setRebuild();
  }

}
