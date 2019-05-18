<?php

namespace Drupal\phpexcel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure phpexcel settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
   return array('phpexcel.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'phpexcel_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('phpexcel.settings');

    $form['cache_mechanism'] = array(
      '#type' => 'radios',
      '#title' => $this->t("Cache mechanism"),
      '#description' => $this->t("The PHPExcel library uses an average of 1k of memory for <em>each cell</em>. This can quickly use up available memory. This can be reduced, however, by specifiying a caching method. This will cache each cell, reducing memory usage. Note, however, that all caching methods are slower than the default <em>Cache in memory</em> method."),
      '#options' => array(
        'cache_in_memory' => $this->t("Cache in memory. Default method. Fastest, but uses a lot of memory"),
        'cache_in_memory_serialized' => $this->t("Cache in memory, serialized. Fast, uses slightly less memory than the previous option."),
        'cache_in_memory_gzip' => $this->t("Cache in memory, GZipped. Fast, uses slightly less memory that the previous option."),
        'cache_to_phpTemp' => $this->t("Cache to php://temp. Slow. Will still cache to memory up to a certain limit (default 1MB) to speed up the process."),
        'cache_to_apc' => $this->t("Cache to APC. Fast."),
        'cache_to_memcache' => $this->t("Cache to Memcache. Fast."),
        'cache_to_sqlite3' => $this->t("Cache to SQLite 3. Slowest, but most memory-efficient."),
      ),
      '#default_value' => $config->get('cache_mechanism'),
    );

    // PHPTemp settings.
    $form['phptemp'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t("PHPTemp options"),
      '#states' => array(
        'visible' => array(
          ':input[value="cache_to_phpTemp"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['phptemp']['phptemp_limit'] = array(
      '#title' => $this->t("PHPTemp memory cache size"),
      '#description' => $this->t("The limit before which PHPExcel will still use memory instead of disk for cell caching. Value in MB (only give a numerical value)."),
      '#type' => 'textfield',
      '#default_value' => $config->get('phptemp_limit'),
    );

    // APC settings.
    $form['apc'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t("APC options"),
      '#states' => array(
        'visible' => array(
          ':input[value="cache_to_apc"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['apc']['apc_cachetime'] = array(
      '#title' => $this->t("APC cache timeout"),
      '#description' => $this->t("The time the cell data remains valid in APC. Defaults to 600 seconds. Data is automatically cleared from the cache when the script terminates."),
      '#type' => 'textfield',
      '#default_value' => $config->get('apc_cachetime'),
    );

    // Memcache settings.
    $form['memcache'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t("Memcache options"),
      '#states' => array(
        'visible' => array(
          ':input[value="cache_to_memcache"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['memcache']['memcache_host'] = array(
      '#title' => $this->t("Memcache server"),
      '#description' => $this->t("If you use Memcache, specify it's host here (e.g. 'localhost')."),
      '#type' => 'textfield',
      '#default_value' => $config->get('memcache_host'),
    );
    $form['memcache']['memcache_port'] = array(
      '#title' => $this->t("Memcache port"),
      '#description' => $this->t("If you use Memcache, specify it's port here."),
      '#type' => 'textfield',
      '#default_value' => $config->get('memcache_port'),
    );
    $form['memcache']['memcache_cachetime'] = array(
      '#title' => $this->t("Memcache cache timeout"),
      '#description' => $this->t("The time the cell data remains valid in Memcache. Defaults to 600 seconds. Data is automatically cleared from the cache when the script terminates."),
      '#type' => 'textfield',
      '#default_value' => $config->get('memcache_cachetime'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    switch ($form_state->getValue('cache_mechanism')) {
      case 'cache_to_phpTemp':
        if (!preg_match('/^[0-9]+$/', $form_state->getValue('phptemp_limit'))) {
          $form_state->setErrorByName(
            'phptemp_limit',
            $this->t("You must provide an integer value. The unit is in megabytes. Defaults to 1 (recommended).")
          );
        }
        break;

      case 'cache_to_apc':
        if (!preg_match('/^[0-9]+$/', $form_state->getValue('apc_cachetime'))) {
          $form_state->setErrorByName(
            'apc_cachetime',
            $this->t("You must provide an integer value. The unit is in seconds. Defaults to 600 (recommended). Remember that all cells cached in APC will get cleared at the end of the script run.")
          );
        }
        break;

      case 'cache_to_memcache':
        if (trim($form_state->getValue('memcache_host')) == '') {
          $form_state->setErrorByName(
            'memcache_host',
            $this->t("You must provide a host for Memcache. Defaults to 'localhost'.")
          );
        }
        if (!preg_match('/^[0-9]+$/', $form_state->getValue('memcache_port'))) {
          $form_state->setErrorByName(
            'memcache_port',
            $this->t("You must provide a port for Memcache. Defaults to '11211'.")
          );
        }
        if (!preg_match('/^[0-9]+$/', $form_state->getValue('memcache_cachetime'))) {
          $form_state->setErrorByName(
            'memcache_cachetime',
            $this->t("You must provide an integer value. The unit is in seconds. Defaults to 600 (recommended). Remember that all cells cached in Memcache will get cleared at the end of the script run.")
          );
        }
        break;
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('phpexcel.settings')
      ->set('cache_mechanism', $form_state->getValue('cache_mechanism'))
      ->set('phptemp_limit', $form_state->getValue('phptemp_limit'))
      ->set('apc_cachetime', $form_state->getValue('apc_cachetime'))
      ->set('memcache_host', $form_state->getValue('memcache_host'))
      ->set('memcache_port', $form_state->getValue('memcache_port'))
      ->set('memcache_cachetime', $form_state->getValue('memcache_cachetime'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
