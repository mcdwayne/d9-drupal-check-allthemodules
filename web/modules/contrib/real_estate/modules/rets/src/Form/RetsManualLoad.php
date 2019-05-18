<?php

namespace Drupal\real_estate_rets\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\real_estate_rets\RetsFetcher;
use Drupal\real_estate_rets\RetsManagerInterface;
use Drupal\Core\Config\ConfigManager;

/**
 * Class RetsManualLoad.
 */
class RetsManualLoad extends FormBase {

  /**
   * Drupal\real_estate_rets\RetsFetcher definition.
   *
   * @var \Drupal\real_estate_rets\RetsFetcher
   */
  protected $realEstateRetsFetcher;
  /**
   * Drupal\Core\Config\ConfigManager definition.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $configManager;
  /**
   * Update manager service.
   *
   * @var \Drupal\update\UpdateManagerInterface
   */
  protected $retsManager;

  /**
   * Constructs a new RetsManualLoad object.
   */
  public function __construct(
    RetsFetcher $real_estate_rets_fetcher,
    ConfigManager $config_manager,
    RetsManagerInterface $rets_manager
  ) {
    $this->realEstateRetsFetcher = $real_estate_rets_fetcher;
    $this->configManager = $config_manager;
    $this->retsManager = $rets_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('real_estate_rets.fetcher'),
      $container->get('config.manager'),
      $container->get('real_estate_rets.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'real_estate_rets_manual_load';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => '<p>' . $this->t('Load RETS data manually.') . '</p>',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Load'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->retsManager->refreshRetsData();

    $batch = [
      'operations' => [
        [[$this->retsManager, 'fetchDataBatch'], []],
      ],
      'finished' => 'rets_fetch_data_finished',
      'title' => $this->t('Load RETS Data'),
      'progress_message' => $this->t('Trying to load RETS data ...'),
      'error_message' => $this->t('Error loading RETS data.'),
    ];
    batch_set($batch);

  }

}
