<?php

namespace Drupal\Tests\tmgmt_smartling_log_settings\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test tmgmt_smartling_log_settings functionality.
 *
 * @group tmgmt_smartling_log_settings
 */
class TmgmtSmartlingLogSettingsTest extends KernelTestBase {

  public static $modules = ['syslog', 'syslog_test', 'tmgmt_smartling_log_settings'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['syslog', 'tmgmt_smartling_log_settings']);
  }

  /**
   * Test severity level logging: empty config.
   */
  public function testSeverityFilteringEmptyConfig() {
    /* @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')->getEditable('tmgmt_smartling_log_settings.settings');
    $config->set('severity_mapping', '');
    $config->save();

    \Drupal::logger('smartling_api')->debug('My test message.');
    \Drupal::logger('smartling_api')->info('My test message.');
    \Drupal::logger('smartling_api')->notice('My test message.');
    \Drupal::logger('smartling_api')->warning('My test message.');
    \Drupal::logger('smartling_api')->error('My test message.');
    \Drupal::logger('smartling_api')->critical('My test message.');
    \Drupal::logger('smartling_api')->alert('My test message.');
    \Drupal::logger('smartling_api')->emergency('My test message.');
    \Drupal::logger('tmgmt_smartling')->debug('My test message.');
    \Drupal::logger('tmgmt_smartling')->info('My test message.');
    \Drupal::logger('tmgmt_smartling')->notice('My test message.');
    \Drupal::logger('tmgmt_smartling')->warning('My test message.');
    \Drupal::logger('tmgmt_smartling')->error('My test message.');
    \Drupal::logger('tmgmt_smartling')->critical('My test message.');
    \Drupal::logger('tmgmt_smartling')->alert('My test message.');
    \Drupal::logger('tmgmt_smartling')->emergency('My test message.');

    $log_filename = $this->container->get('file_system')->realpath('public://syslog.log');
    $log_records = explode(PHP_EOL, file_get_contents($log_filename));

    $this->assertEquals(8, count(array_filter($log_records, function($v) {
      return strpos($v, 'smartling_api');
    })), 'Nothing is filtered.');
    $this->assertEquals(8, count(array_filter($log_records, function($v) {
      return strpos($v, 'tmgmt_smartling');
    })), 'Nothing is filtered.');
  }

  /**
   * Test severity level logging: debug.
   */
  public function testSeverityFilteringDebug() {
    /* @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')->getEditable('tmgmt_smartling_log_settings.settings');
    $config->set('severity_mapping', "smartling_api: debug\r\ntmgmt_smartling: debug");
    $config->save();

    \Drupal::logger('smartling_api')->debug('My test message.');
    \Drupal::logger('smartling_api')->info('My test message.');
    \Drupal::logger('smartling_api')->notice('My test message.');
    \Drupal::logger('smartling_api')->warning('My test message.');
    \Drupal::logger('smartling_api')->error('My test message.');
    \Drupal::logger('smartling_api')->critical('My test message.');
    \Drupal::logger('smartling_api')->alert('My test message.');
    \Drupal::logger('smartling_api')->emergency('My test message.');
    \Drupal::logger('tmgmt_smartling')->debug('My test message.');
    \Drupal::logger('tmgmt_smartling')->info('My test message.');
    \Drupal::logger('tmgmt_smartling')->notice('My test message.');
    \Drupal::logger('tmgmt_smartling')->warning('My test message.');
    \Drupal::logger('tmgmt_smartling')->error('My test message.');
    \Drupal::logger('tmgmt_smartling')->critical('My test message.');
    \Drupal::logger('tmgmt_smartling')->alert('My test message.');
    \Drupal::logger('tmgmt_smartling')->emergency('My test message.');

    $log_filename = $this->container->get('file_system')->realpath('public://syslog.log');
    $log_records = explode(PHP_EOL, file_get_contents($log_filename));

    $this->assertEquals(8, count(array_filter($log_records, function($v) {
      return strpos($v, 'smartling_api');
    })), 'Nothing is filtered.');
    $this->assertEquals(8, count(array_filter($log_records, function($v) {
      return strpos($v, 'tmgmt_smartling');
    })), 'Nothing is filtered.');
  }

  /**
   * Test severity level logging: info.
   */
  public function testSeverityFilteringInfo() {
    /* @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')->getEditable('tmgmt_smartling_log_settings.settings');
    $config->set('severity_mapping', "smartling_api: info\r\ntmgmt_smartling: debug");
    $config->save();

    \Drupal::logger('smartling_api')->debug('My test message.');
    \Drupal::logger('smartling_api')->info('My test message.');
    \Drupal::logger('smartling_api')->notice('My test message.');
    \Drupal::logger('smartling_api')->warning('My test message.');
    \Drupal::logger('smartling_api')->error('My test message.');
    \Drupal::logger('smartling_api')->critical('My test message.');
    \Drupal::logger('smartling_api')->alert('My test message.');
    \Drupal::logger('smartling_api')->emergency('My test message.');
    \Drupal::logger('tmgmt_smartling')->debug('My test message.');
    \Drupal::logger('tmgmt_smartling')->info('My test message.');
    \Drupal::logger('tmgmt_smartling')->notice('My test message.');
    \Drupal::logger('tmgmt_smartling')->warning('My test message.');
    \Drupal::logger('tmgmt_smartling')->error('My test message.');
    \Drupal::logger('tmgmt_smartling')->critical('My test message.');
    \Drupal::logger('tmgmt_smartling')->alert('My test message.');
    \Drupal::logger('tmgmt_smartling')->emergency('My test message.');

    $log_filename = $this->container->get('file_system')->realpath('public://syslog.log');
    $log_records = explode(PHP_EOL, file_get_contents($log_filename));

    $this->assertEquals(7, count(array_filter($log_records, function($v) {
      return strpos($v, 'smartling_api');
    })), 'Messages below info are filtered.');
    $this->assertEquals(8, count(array_filter($log_records, function($v) {
      return strpos($v, 'tmgmt_smartling');
    })), 'Nothing is filtered.');
  }

  /**
   * Test severity level logging: notice.
   */
  public function testSeverityFilteringNotice() {
    /* @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')->getEditable('tmgmt_smartling_log_settings.settings');
    $config->set('severity_mapping', "smartling_api: notice\r\ntmgmt_smartling: debug");
    $config->save();

    \Drupal::logger('smartling_api')->debug('My test message.');
    \Drupal::logger('smartling_api')->info('My test message.');
    \Drupal::logger('smartling_api')->notice('My test message.');
    \Drupal::logger('smartling_api')->warning('My test message.');
    \Drupal::logger('smartling_api')->error('My test message.');
    \Drupal::logger('smartling_api')->critical('My test message.');
    \Drupal::logger('smartling_api')->alert('My test message.');
    \Drupal::logger('smartling_api')->emergency('My test message.');
    \Drupal::logger('tmgmt_smartling')->debug('My test message.');
    \Drupal::logger('tmgmt_smartling')->info('My test message.');
    \Drupal::logger('tmgmt_smartling')->notice('My test message.');
    \Drupal::logger('tmgmt_smartling')->warning('My test message.');
    \Drupal::logger('tmgmt_smartling')->error('My test message.');
    \Drupal::logger('tmgmt_smartling')->critical('My test message.');
    \Drupal::logger('tmgmt_smartling')->alert('My test message.');
    \Drupal::logger('tmgmt_smartling')->emergency('My test message.');

    $log_filename = $this->container->get('file_system')->realpath('public://syslog.log');
    $log_records = explode(PHP_EOL, file_get_contents($log_filename));

    $this->assertEquals(6, count(array_filter($log_records, function($v) {
      return strpos($v, 'smartling_api');
    })), 'Messages below notice are filtered.');
    $this->assertEquals(8, count(array_filter($log_records, function($v) {
      return strpos($v, 'tmgmt_smartling');
    })), 'Nothing is filtered.');
  }

  /**
   * Test severity level logging: warning.
   */
  public function testSeverityFilteringWarning() {
    /* @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')->getEditable('tmgmt_smartling_log_settings.settings');
    $config->set('severity_mapping', "smartling_api: warning\r\ntmgmt_smartling: debug");
    $config->save();

    \Drupal::logger('smartling_api')->debug('My test message.');
    \Drupal::logger('smartling_api')->info('My test message.');
    \Drupal::logger('smartling_api')->notice('My test message.');
    \Drupal::logger('smartling_api')->warning('My test message.');
    \Drupal::logger('smartling_api')->error('My test message.');
    \Drupal::logger('smartling_api')->critical('My test message.');
    \Drupal::logger('smartling_api')->alert('My test message.');
    \Drupal::logger('smartling_api')->emergency('My test message.');
    \Drupal::logger('tmgmt_smartling')->debug('My test message.');
    \Drupal::logger('tmgmt_smartling')->info('My test message.');
    \Drupal::logger('tmgmt_smartling')->notice('My test message.');
    \Drupal::logger('tmgmt_smartling')->warning('My test message.');
    \Drupal::logger('tmgmt_smartling')->error('My test message.');
    \Drupal::logger('tmgmt_smartling')->critical('My test message.');
    \Drupal::logger('tmgmt_smartling')->alert('My test message.');
    \Drupal::logger('tmgmt_smartling')->emergency('My test message.');

    $log_filename = $this->container->get('file_system')->realpath('public://syslog.log');
    $log_records = explode(PHP_EOL, file_get_contents($log_filename));

    $this->assertEquals(5, count(array_filter($log_records, function($v) {
      return strpos($v, 'smartling_api');
    })), 'Messages below warning are filtered.');
    $this->assertEquals(8, count(array_filter($log_records, function($v) {
      return strpos($v, 'tmgmt_smartling');
    })), 'Nothing is filtered.');
  }

  /**
   * Test severity level logging: error.
   */
  public function testSeverityFilteringError() {
    /* @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')->getEditable('tmgmt_smartling_log_settings.settings');
    $config->set('severity_mapping', "smartling_api: error\r\ntmgmt_smartling: debug");
    $config->save();

    \Drupal::logger('smartling_api')->debug('My test message.');
    \Drupal::logger('smartling_api')->info('My test message.');
    \Drupal::logger('smartling_api')->notice('My test message.');
    \Drupal::logger('smartling_api')->warning('My test message.');
    \Drupal::logger('smartling_api')->error('My test message.');
    \Drupal::logger('smartling_api')->critical('My test message.');
    \Drupal::logger('smartling_api')->alert('My test message.');
    \Drupal::logger('smartling_api')->emergency('My test message.');
    \Drupal::logger('tmgmt_smartling')->debug('My test message.');
    \Drupal::logger('tmgmt_smartling')->info('My test message.');
    \Drupal::logger('tmgmt_smartling')->notice('My test message.');
    \Drupal::logger('tmgmt_smartling')->warning('My test message.');
    \Drupal::logger('tmgmt_smartling')->error('My test message.');
    \Drupal::logger('tmgmt_smartling')->critical('My test message.');
    \Drupal::logger('tmgmt_smartling')->alert('My test message.');
    \Drupal::logger('tmgmt_smartling')->emergency('My test message.');

    $log_filename = $this->container->get('file_system')->realpath('public://syslog.log');
    $log_records = explode(PHP_EOL, file_get_contents($log_filename));

    $this->assertEquals(4, count(array_filter($log_records, function($v) {
      return strpos($v, 'smartling_api');
    })), 'Messages below error are filtered.');
    $this->assertEquals(8, count(array_filter($log_records, function($v) {
      return strpos($v, 'tmgmt_smartling');
    })), 'Nothing is filtered.');
  }

  /**
   * Test severity level logging: error.
   */
  public function testSeverityFilteringCritical() {
    /* @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')->getEditable('tmgmt_smartling_log_settings.settings');
    $config->set('severity_mapping', "smartling_api: critical\r\ntmgmt_smartling: debug");
    $config->save();

    \Drupal::logger('smartling_api')->debug('My test message.');
    \Drupal::logger('smartling_api')->info('My test message.');
    \Drupal::logger('smartling_api')->notice('My test message.');
    \Drupal::logger('smartling_api')->warning('My test message.');
    \Drupal::logger('smartling_api')->error('My test message.');
    \Drupal::logger('smartling_api')->critical('My test message.');
    \Drupal::logger('smartling_api')->alert('My test message.');
    \Drupal::logger('smartling_api')->emergency('My test message.');
    \Drupal::logger('tmgmt_smartling')->debug('My test message.');
    \Drupal::logger('tmgmt_smartling')->info('My test message.');
    \Drupal::logger('tmgmt_smartling')->notice('My test message.');
    \Drupal::logger('tmgmt_smartling')->warning('My test message.');
    \Drupal::logger('tmgmt_smartling')->error('My test message.');
    \Drupal::logger('tmgmt_smartling')->critical('My test message.');
    \Drupal::logger('tmgmt_smartling')->alert('My test message.');
    \Drupal::logger('tmgmt_smartling')->emergency('My test message.');

    $log_filename = $this->container->get('file_system')->realpath('public://syslog.log');
    $log_records = explode(PHP_EOL, file_get_contents($log_filename));

    $this->assertEquals(3, count(array_filter($log_records, function($v) {
      return strpos($v, 'smartling_api');
    })), 'Messages below critical are filtered.');
    $this->assertEquals(8, count(array_filter($log_records, function($v) {
      return strpos($v, 'tmgmt_smartling');
    })), 'Nothing is filtered.');
  }

  /**
   * Test severity level logging: alert.
   */
  public function testSeverityFilteringAlert() {
    /* @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')->getEditable('tmgmt_smartling_log_settings.settings');
    $config->set('severity_mapping', "smartling_api: alert\r\ntmgmt_smartling: debug");
    $config->save();

    \Drupal::logger('smartling_api')->debug('My test message.');
    \Drupal::logger('smartling_api')->info('My test message.');
    \Drupal::logger('smartling_api')->notice('My test message.');
    \Drupal::logger('smartling_api')->warning('My test message.');
    \Drupal::logger('smartling_api')->error('My test message.');
    \Drupal::logger('smartling_api')->critical('My test message.');
    \Drupal::logger('smartling_api')->alert('My test message.');
    \Drupal::logger('smartling_api')->emergency('My test message.');
    \Drupal::logger('tmgmt_smartling')->debug('My test message.');
    \Drupal::logger('tmgmt_smartling')->info('My test message.');
    \Drupal::logger('tmgmt_smartling')->notice('My test message.');
    \Drupal::logger('tmgmt_smartling')->warning('My test message.');
    \Drupal::logger('tmgmt_smartling')->error('My test message.');
    \Drupal::logger('tmgmt_smartling')->critical('My test message.');
    \Drupal::logger('tmgmt_smartling')->alert('My test message.');
    \Drupal::logger('tmgmt_smartling')->emergency('My test message.');

    $log_filename = $this->container->get('file_system')->realpath('public://syslog.log');
    $log_records = explode(PHP_EOL, file_get_contents($log_filename));

    $this->assertEquals(2, count(array_filter($log_records, function($v) {
      return strpos($v, 'smartling_api');
    })), 'Messages below alert are filtered.');
    $this->assertEquals(8, count(array_filter($log_records, function($v) {
      return strpos($v, 'tmgmt_smartling');
    })), 'Nothing is filtered.');
  }

  /**
   * Test severity level logging: emergency.
   */
  public function testSeverityFilteringEmergency() {
    /* @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')->getEditable('tmgmt_smartling_log_settings.settings');
    $config->set('severity_mapping', "smartling_api: emergency\r\ntmgmt_smartling: debug");
    $config->save();

    \Drupal::logger('smartling_api')->debug('My test message.');
    \Drupal::logger('smartling_api')->info('My test message.');
    \Drupal::logger('smartling_api')->notice('My test message.');
    \Drupal::logger('smartling_api')->warning('My test message.');
    \Drupal::logger('smartling_api')->error('My test message.');
    \Drupal::logger('smartling_api')->critical('My test message.');
    \Drupal::logger('smartling_api')->alert('My test message.');
    \Drupal::logger('smartling_api')->emergency('My test message.');
    \Drupal::logger('tmgmt_smartling')->debug('My test message.');
    \Drupal::logger('tmgmt_smartling')->info('My test message.');
    \Drupal::logger('tmgmt_smartling')->notice('My test message.');
    \Drupal::logger('tmgmt_smartling')->warning('My test message.');
    \Drupal::logger('tmgmt_smartling')->error('My test message.');
    \Drupal::logger('tmgmt_smartling')->critical('My test message.');
    \Drupal::logger('tmgmt_smartling')->alert('My test message.');
    \Drupal::logger('tmgmt_smartling')->emergency('My test message.');

    $log_filename = $this->container->get('file_system')->realpath('public://syslog.log');
    $log_records = explode(PHP_EOL, file_get_contents($log_filename));

    $this->assertEquals(1, count(array_filter($log_records, function($v) {
      return strpos($v, 'smartling_api');
    })), 'Messages below emergency are filtered.');
    $this->assertEquals(8, count(array_filter($log_records, function($v) {
      return strpos($v, 'tmgmt_smartling');
    })), 'Nothing is filtered.');
  }

}
