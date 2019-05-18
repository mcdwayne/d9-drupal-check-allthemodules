<?php

namespace Drupal\migrate_gathercontent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate_gathercontent\DrupalGatherContentClient;
use GuzzleHttp\Exception\ClientException;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * @var \drupal\migrate_gathercontent\drupalgathercontentclient
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('config.factory'),
      $container->get('migrate_gathercontent.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, DrupalGatherContentClient $gathercontent_client) {
    parent::__construct($config_factory);
    $this->client = $gathercontent_client;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'migrate_gathercontent.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_gathercontent_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('migrate_gathercontent.settings');
    $form['email'] = [
      '#required' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#description' => $this->t('Provide an email address.'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('gathercontent_email'),
    ];

    $form['api_key'] = [
      '#required' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('Provide an API Key.'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('gathercontent_api_key'),
    ];

    /** @var \Cheppers\GatherContent\DataTypes\Account[] $data */
  try {
    $data = $this->client->accountsGet();
    if (!empty($data)) {
      $accounts = [];

      if (!is_null($data)) {
        foreach ($data as $account) {
          $accounts[$account->id] = $account->name;
        }

        $form['account'] = [
          '#type' => 'select',
          '#options' => $accounts,
          '#title' => $this->t('Select GatherContent Account'),
          '#default_value' => $config->get('gathercontent_account'),
          '#required' => TRUE,
          '#description' => $this->t('Multiple accounts will be listed if the GatherContent
     user has more than one account. Please select the account you want to
     import and update content from.'),
        ];
      }
    }
    } catch (ClientException $e) {
      // Do nothing.
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('migrate_gathercontent.settings')
      ->set('gathercontent_api_key', $form_state->getValue('api_key'))
      ->set('gathercontent_email', $form_state->getValue('email'))
      ->set('gathercontent_account', $form_state->getValue('account'))
      ->save();
  }

}
