<?php

namespace Drupal\ddate_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use EmperorNortonCommands\lib\Ddate;

/**
 * Defines a ddate block block type.
 *
 * @Block(
 *   id = "ddate_block",
 *   admin_label = @Translation("Ddate Block"),
 *   category = @Translation("Ddate"),
 * )
 */
class DdateBlock extends BlockBase implements BlockPluginInterface {

  /**
   * @var array
   */
  private $config;

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['ddate_block_format'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Format'),
      '#description' => $this->t('The format of the outputted date string, e.g. %{%A, %e of %B%}, %Y. %N%n&lt;br&gt;Celebrate %H'),
      '#default_value' => isset($config['ddate_block_format']) ? $config['ddate_block_format'] : '',
    );

    $form['ddate_block_date'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Date'),
      '#description' => $this->t('Enter a custom date (dmY, e.g. 29022012 for 29th of February 2012). If not set todays date is shown.'),
      '#default_value' => isset($config['ddate_block_date']) ? $config['ddate_block_date'] : '',
    );

    $form['ddate_block_maxcachelifetime'] = array(
      '#type' => 'number',
      '#title' => $this->t('Maximum Cache Lifetime'),
      '#description' => $this->t('Maximum number of seconds that this block may be cached.'),
      '#default_value' => isset($config['ddate_block_maxcachelifetime']) ? $config['ddate_block_maxcachelifetime'] : '',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['ddate_block_format'] = $values['ddate_block_format'];
    $this->configuration['ddate_block_date'] = $values['ddate_block_date'];
    $this->configuration['ddate_block_maxcachelifetime'] = $values['ddate_block_maxcachelifetime'];
  }

  /**
   * @inheritdoc
   */
  public function build() {
    $ddate = new Ddate();

    $format = $this->getConfigValue('ddate_block_format', '%{%A, %e of %B%}, %Y. %N%n<br>Celebrate %H');
    $date = $this->getConfigValue('ddate_block_date', date('dmY'));

    return [
      '#markup' => $ddate->ddate($format, $date),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->getConfigValue('ddate_block_maxcachelifetime', 900);
  }

  /**
   * Get a value from the configuration.
   *
   * @param string $key
   * @param mixed $default
   *
   * @return mixed
   */
  private function getConfigValue($key, $default) {
    if (is_null($this->config)) {
      $this->config = $this->getConfiguration();
    }

    if (!empty($this->config[$key])) {
      return $this->config[$key];
    }

    return $default;
  }

}
