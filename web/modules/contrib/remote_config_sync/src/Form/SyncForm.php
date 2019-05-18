<?php

namespace Drupal\remote_config_sync\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\remote_config_sync\Service\Sync;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SyncForm.
 */
class SyncForm extends FormBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The sync service.
   *
   * @var \Drupal\remote_config_sync\Service\Sync
   */
  protected $sync;

  /**
   * SyncForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\remote_config_sync\Service\Sync $sync
   */
  public function __construct(ConfigFactoryInterface $config_factory, Sync $sync) {
    $this->configFactory = $config_factory;
    $this->sync = $sync;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('remote_config_sync.sync')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remote_config_sync_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('remote_config_sync.settings');

    $form['sync'] = [
      '#type' => 'fieldset',
    ];

    $form['sync']['notice'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Before pushing the configuration, make sure that you installed this module on your remote site and that you properly configured your remotes.'),
    ];

    $form['sync']['remotes'] = [
      '#type' => 'select',
      '#title' => $this->t('Remotes'),
      '#options' => $this->remoteList(),
      '#description' => $this->t('Select a remote where you want to push the configuration.'),
    ];

    $form['sync']['operation'] = [
      '#type' => 'radios',
      '#title' => $this->t('Operation'),
      '#options' => [
        'push' => $this->t('Push the configuration to a remote site'),
        'push_import' => $this->t('Push the configuration to a remote site and import it'),
      ],
      '#default_value' => 'push',
    ];

    $form['sync']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Synchronize'),
    ];

    if (!$config->get('disable_confirmation')) {
      $form['sync']['confirm'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('I confirm this operation.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('remote_config_sync.settings');

    if (!$form_state->getValue('remotes')) {
      $form_state->setErrorByName('remotes',
        $this->t('Please select a remote site.')
      );
    }

    if (!$form_state->getValue('confirm') && !$config->get('disable_confirmation')) {
      $form_state->setErrorByName('confirm',
        $this->t('Before proceeding you must confirm this operation.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $remote = $form_state->getValue('remotes');
    $operation = $form_state->getValue('operation');

    if ($operation == 'push') {
      $result = $this->sync->push($remote);
      drupal_set_message($result['message'], $result['status']);

      if (isset($result['host'])) {
        $url = Url::fromUri($result['host'] . '/admin/config/development/configuration');
        $link = Link::fromTextAndUrl(t('Visit your remote site'), $url);
        drupal_set_message(t('@link to manually review and import it!', ['@link' => $link->toString()]));
      }
    }
    else {
      $result = $this->sync->push($remote, TRUE);
      drupal_set_message($result['message'], $result['status']);
    }
  }

  /**
   * Build remote site list.
   */
  protected function remoteList() {
    $config = $this->configFactory->get('remote_config_sync.settings');
    $remotes = explode("\r\n", $config->get('remotes'));

    $remote_list = [];
    foreach ($remotes as $remote) {
      $remote_data = explode('|', $remote);
      $remote_url = isset($remote_data[0]) ? $remote_data[0] : NULL;
      if (!$remote_url) {
        continue;
      }
      $remote_list[$remote] = $remote_url;
    }

    return $remote_list;
  }

}
