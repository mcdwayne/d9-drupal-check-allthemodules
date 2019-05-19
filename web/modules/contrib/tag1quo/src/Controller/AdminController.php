<?php

namespace Drupal\tag1quo\Controller;

use Drupal\tag1quo\Adapter\Core\Core;
use Drupal\tag1quo\Heartbeat;
use Drupal\tag1quo\VersionedClass;

/**
 * Class AdminController.
 */
class AdminController extends VersionedClass {

  /**
   * The Core adapter.
   *
   * @var \Drupal\tag1quo\Adapter\Core\Core
   */
  protected $core;

  /**
   * The Tag1 Quo configuration.
   *
   * @var \Drupal\tag1quo\Adapter\Config\Config
   */
  protected $config;

  /**
   * AdminSettingsForm constructor.
   */
  public function __construct() {
    $this->core = Core::create();
    $this->config = $this->core->config('tag1quo.settings');
  }

  /**
   * Creates a new AdminSettingsForm instance.
   *
   * @return static
   */
  public static function create() {
    return static::createVersionedStaticInstance();
  }

  /**
   * Admin review tab page
   */
  public function review() {
    $heartbeat = Heartbeat::create()->setStale(TRUE)->validate();
    if ($errorMessage = $heartbeat->getErrorMessage()) {
      $build[] = $this->core->buildElement(array(
        '#type' => 'item',
        '#markup' => $errorMessage,
      ));
      return $build;
    }

    $data = $heartbeat->getData();

    $header = array(
      $this->core->t('Name'),
      $this->core->t('Type'),
      $this->core->t('Version'),
      $this->core->t('Schema version'),
      $this->core->t('Enabled'),
      $this->core->t('Filename'),
    );

    $rows = array();
    $data['field_json_data'][0]['value'] = $this->core->jsonDecode($data['field_json_data'][0]['value']);
    foreach ($data['field_json_data'][0]['value'] as &$project) {
      $info = isset($project['info']) ? unserialize($project['info']) : array();
      $project['info'] = $info;

      $row = array(
        $project['name'],
        $project['type'],
        isset($info['version']) ? $info['version'] : '',
        $project['schema_version'],
        $project['status'],
        $project['filename'],
      );
      $rows[] = $row;
    }

    $build['status_table'] = $this->core->buildElement(array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => TRUE,
    ));

    // Show the actual JSON that's being sent to Tag1 Quo.
    $build['field_json_data'] = $this->core->buildElement(array(
      '#type' => 'details',
      '#title' => $this->core->t('Raw data'),
      '#markup' => '<pre><code>' . $this->core->jsonEncode($data, TRUE) . '</code></pre>',
      '#attributes' => array(),
      '#children' => '',
    ));

    return $build;
  }

  public function status() {
    $heartbeat = Heartbeat::create()->setStale(TRUE)->validate();
    if ($errorMessage = $heartbeat->getErrorMessage()) {
      $build[] = $this->core->buildElement(array(
        '#type' => 'item',
        '#markup' => $errorMessage,
      ));
      return $build;
    }

    $requestTime = $this->core->requestTime();

    $args = array(
      '@title' => Core::TITLE,
      '!configuration_page' => $this->core->l($this->core->t('configuration page'), 'tag1quo.admin_settings'),
    );

    // Last heartbeat.
    $lastTimestamp = $heartbeat->lastTimestamp();
    if (!$lastTimestamp || ($requestTime - $lastTimestamp) > Heartbeat::FREQUENCY * 2) {
      $this->core->setMessage($this->core->t('No recent heartbeat has been sent to @title. Verify the settings on the !configuration_page.', $args), 'error');
    }

    $build[] = $this->core->buildElement(array(
      '#type' => 'item',
      '#title' => $this->core->t('Last Heartbeat:'),
      '#markup' => !$lastTimestamp ? $this->core->t('Never') : $this->core->t('@time ago', array(
        '@time' => $this->core->formatInterval($requestTime - $lastTimestamp),
      )),
    ));

    // Next heartbeat.
    $nextTimestamp = $heartbeat->nextTimestamp();
    $build[] = $this->core->buildElement(array(
      '#type' => 'item',
      '#title' => $this->core->t('Next Heartbeat:'),
      '#markup' => $heartbeat->isStale() ? $this->core->t('Next cron run') : $this->core->t('A heartbeat will be sent to Tag1 Quo in @time.', array(
        '@time' => $this->core->formatInterval($nextTimestamp - $this->core->requestTime()),
      ))
    ));

    $build[] = $this->core->buildElement(array(
      '#type' => 'item',
      '#markup' => $this->core->l($this->core->t('Send manually'), 'tag1quo.admin_send_manually', array(
        'attributes' => array(
          'class' => 'button button--primary btn btn-primary',
        ),
      )),
    ));

    return $build;
  }

  /**
   * Manually send a heartbeat.
   */
  public function sendManually() {
    Heartbeat::manual()->send();
    return $this->core->redirect('tag1quo.admin_status');
  }

}
