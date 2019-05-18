<?php

namespace Drupal\ip2location;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [
      'ip2location.settings',
    ];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'ip2location_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ip2location.settings');

    $database_path = $config->get('database_path');
	$cache_mode = $config->get('cache_mode');

    if (!in_array($cache_mode, array('no_cache', 'memory_cache', 'shared_memory'))) {
		$cache_mode = 'no_cache';
	}

	$form['database_path'] = array(
      '#type' => 'textfield',
      '#title' => t('IP2Location BIN database path'),
      '#description' => t('Relative path to your Drupal installation of to where the IP2Location BIN database was uploaded. For example: sites/default/files/IP2Location-LITE-DB11.BIN. Note: You can get the latest BIN data at <a href="http://lite.ip2location.com/?r=drupal" target="_blank">http://lite.ip2location.com</a> (free LITE edition) or <a href="http://www.ip2location.com/?r=drupal" target="_blank">http://www.ip2location.com</a> (commercial edition).'),
      '#default_value' => $database_path,
      '#states' => array(
        'visible' => array(
          ':input[name="ip2location_source"]' => array(
            'value' => 'ip2location_bin',
          ),
        ),
      ),
  );

  $form['cache_mode'] = array(
    '#type' => 'select',
    '#title' => t('Cache Mode'),
    '#description' => t('"No cache" - standard lookup with no cache. "Memory cache" - cache the database into memory to accelerate lookup speed. "Shared memory" - cache whole database into system memory and share among other scripts and websites. Please make sure your system have sufficient RAM if enabling "Memory cache" or "Shared memory".'),
    '#options' => array(
      'no_cache' => t('No cache'),
      'memory_cache' => t('Memory cache'),
      'shared_memory' => t('Shared memory'),
    ),
    '#default_value' => $cache_mode,
  );
  return parent::buildForm($form, $form_state);
}

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!is_file($form_state->getValue('database_path'))) {
      $form_state->setErrorByName('database_path', $this->t('The IP2Location binary database path is not valid.'));
    }
	else {
      try {
        module_load_include('inc', 'ip2location', 'src/IP2Location');
        $ip2location = new \IP2Location\Database($form_state->getValue('database_path'),  \IP2Location\Database::FILE_IO);
        $records = $ip2location->lookup('8.8.8.8',  \IP2Location\Database::ALL);

        if (empty($records['ipNumber'])) {
		  $form_state->setErrorByName('database_path', $this->t('The IP2Location binary database is not valid or corrupted.'));
        }
      } catch (Exception $error) {
        $form_state->setErrorByName('database_path', $this->t('The IP2Location binary database is not valid or corrupted.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('ip2location.settings')->set('database_path', $values['database_path'])->save();
	$this->config('ip2location.settings')->set('cache_mode', $values['cache_mode'])->save();

    parent::submitForm($form, $form_state);
  }
}