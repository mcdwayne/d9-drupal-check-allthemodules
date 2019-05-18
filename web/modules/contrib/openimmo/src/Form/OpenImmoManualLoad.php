<?php

namespace Drupal\openimmo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openimmo\OpenImmoFetcher;
use Drupal\openimmo\OpenImmoManagerInterface;
use Drupal\Core\Config\ConfigManager;

/**
 * Class OpenImmoManualLoad.
 */
class OpenImmoManualLoad extends FormBase {

  /**
   * Drupal\openimmo\OpenImmoFetcher definition.
   *
   * @var \Drupal\openimmo\OpenImmoFetcher
   */
  protected $openimmoFetcher;
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
  protected $openimmoManager;

  /**
   * Constructs a new OpenImmoManualLoad object.
   */
  public function __construct(
    OpenImmoFetcher $openimmo_fetcher,
    ConfigManager $config_manager,
    OpenImmoManagerInterface $openimmo_manager
  ) {
    $this->openimmoFetcher = $openimmo_fetcher;
    $this->configManager = $config_manager;
    $this->openimmoManager = $openimmo_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openimmo.fetcher'),
      $container->get('config.manager'),
      $container->get('openimmo.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openimmo_manual_load';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => '<p>' . $this->t('Load OpenImmo data manually.') . '</p>',
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

    $this->openimmoManager->refreshOpenImmoData();

    $batch = [
      'operations' => [
        [[$this->openimmoManager, 'fetchDataBatch'], []],
      ],
      'finished' => 'openimmo_fetch_data_finished',
      'title' => $this->t('Load OpenImmo Data'),
      'progress_message' => $this->t('Trying to load OpenImmo data ...'),
      'error_message' => $this->t('Error loading OpenImmo data.'),
    ];
    batch_set($batch);
    $this->openimmoFetcher->readXmlFile();
    // todo: only for development
    $this->openimmoManager->refreshOpenImmoData();

  }

}
