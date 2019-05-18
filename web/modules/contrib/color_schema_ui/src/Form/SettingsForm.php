<?php

namespace Drupal\color_schema_ui\Form;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class SettingsForm extends ConfigFormBase {

  /**
   * @var Yaml
   */
  private $yaml;

  public function __construct(Yaml $yaml) {
    $this->yaml = $yaml;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('serialization.yaml')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'color_schema_ui_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'color_schema_ui.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $description_colors_definition_json = <<<EOT
Specify your projects individual colors in Yaml language. The key is the colors machine name. Value is the human 
readable color name. See default Yaml string. E.g.: machine_name: 'Human readable name'. The human readable name must be
in English, because it goes trough Drupals t() function.
EOT;

    $form['colors_definition_yaml'] = [
      '#type' => 'textarea',
      '#title' => t('Colors definition Yaml'),
      '#description' => t($description_colors_definition_json),
      '#default_value' => \Drupal::config('color_schema_ui.settings')->get('colors_definition_yaml'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    try {
      $colorsDefinitionYaml = $this->yaml::decode($form_state->getValue('colors_definition_yaml'));
    } catch(\Exception $exception) {
      $form_state->setErrorByName('colors_definition_yaml', $this->t('Looks like invalid Yaml. Please check and re-enter.'));
    }

    if (empty($colorsDefinitionYaml)) {
      $form_state->setErrorByName('colors_definition_yaml', $this->t('Field cannot be empty.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    \Drupal::configFactory()
      ->getEditable('color_schema_ui.settings')
      ->set('colors_definition_yaml', $form_state->getValue('colors_definition_yaml'))
      ->save();

    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

}
