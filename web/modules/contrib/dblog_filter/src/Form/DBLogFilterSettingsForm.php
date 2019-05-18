<?php

namespace Drupal\dblog_filter\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\RfcLogLevel;

class DBLogFilterSettingsForm extends ConfigFormBase {

  /**
   * Constructs a \Drupal\dblog_filter\Form\DBLogFilterSettingsForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dblog_filter_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dblog_filter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dblog_filter.settings');
		$log_levels = RfcLogLevel::getLevels();
		foreach ($log_levels as $key => $log_level) {
			$log_level_value = $log_level->getUntranslatedString();
			$severity_levels[strtolower($log_level_value)] = $log_level_value;
		}
		$form['severity_levels'] = array(
			'#type' => 'checkboxes',
			'#title' => t('Select Severity Levels'),
			'#description' => t('Only the selected severity logs will be recorded'),
			'#options' => $severity_levels,
			'#default_value' => $config->get('severity_levels'),
		);
    $form['log_values'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Enter DB Log type and Severity Levels.'),
      '#default_value' => $config->get('log_values'),
    );
		$levels = '<i>Severity Levels: emergency, alert, critical, error, '
			. 'warning, notice, info, debug</i>';
		$log_types = '<i>Log Types: php, cron, mail, mymodule, etc.</i>';
    $description = $levels;
		$description .= '<br/>' . $log_types;
		$description .= '<p>' . t('Enter one value per line, '
            . 'in the format log_type|level1,level2,level3.. to restrict the 
            log messages stored');
    $description .= '<br/>' . t('<b>Example: php|error,notice,warning '
            . '<br> mymodule|error,notice</b>');
    $description .= '</p>';
    $form['description'] = array(
      '#markup' => $description,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save configurations.
    $config = $this->config('dblog_filter.settings')
			->set('log_values', $form_state->getValue('log_values'))
			->set('severity_levels', $form_state->getValue('severity_levels'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
