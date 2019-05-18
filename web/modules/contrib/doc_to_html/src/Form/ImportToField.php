<?php

namespace Drupal\doc_to_html\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Entity;

/**
 * Class ImportToField.
 *
 * @package Drupal\doc_to_html\Form
 */
class ImportToField extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigManager definition.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $importtofield;
  public function __construct(
    ConfigFactoryInterface $config_factory,
      ConfigManager $config_manager
    ) {
    parent::__construct($config_factory);
        $this->importtofield = $config_manager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
            $container->get('config.manager')
    );
  }

  /**
   * @return array
   */
  protected function getEditableConfigNames() {
    return [
      'doc_to_html.importtofield',
    ];
  }

  /**
   * @return string
   */
  public function getFormId() {
    return 'import_to_field';
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('doc_to_html.importtofield');

    // Get all field configuration
    $config_name =  \Drupal::service('doc_to_html.settings')->GetEntityBundleFieldBy('text_with_summary');

    foreach($config_name as $key => $config_item){
      // Generate Details wrapper
      $form['doc_to_html'][$key] = array(
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $this->t($config_item['bundle_title']),
      );

      // Generate Field checkbox
      $form['doc_to_html'][$key][$key.'-field'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t($config_item['field_label']),
        '#default_value' => $config->get($key.'-field'),
      );

      // Generate regex filter.
      $form['doc_to_html'][$key][$key.'-regex_filter'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Regex Filter'),
        '#description' => $this->t('This is an example to exsclude empty tag p and attributes style :/(style|cellspacing|title|cellpadding|start|name|src|href|width|class|align)=&quot;[a-zA-Z0-9:;\.\s\(\)\-\,]*&quot;/u'),
        '#default_value' => $config->get($key.'-regex_filter'),
      );
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config_name =  \Drupal::service('doc_to_html.settings')->GetEntityBundleFieldBy('text_with_summary');
    foreach ($config_name as $key => $config_item){
      $this->config('doc_to_html.importtofield')->set($key.'-field', $form_state->getValue($key.'-field'))->save();
      $this->config('doc_to_html.importtofield')->set($key.'-regex_filter', $form_state->getValue($key.'-regex_filter'))->save();
    }

  }

}
