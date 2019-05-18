<?php

namespace Drupal\neutral_paths\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pathauto\AliasTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure file system settings for this site.
 */
class NeutralPathsSettingsForm extends ConfigFormBase {

  /**
   * The alias type manager.
   *
   * @var \Drupal\pathauto\AliasTypeManager
   */
  protected $aliasTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasTypeManager $alias_type_manager) {
    $this->aliasTypeManager = $alias_type_manager;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.alias_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'neutral_paths_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['neutral_paths.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('neutral_paths.settings');

    $form['neutral_paths_fix_new'] = [
      '#type' => 'checkboxes',
      '#options' => [],
      '#title' => $this->t('Select the types of paths to set as language neutral'),
      '#default_value' => $config->get('neutral_paths_fix_new'),
    ];

    $definitions = $this->aliasTypeManager->getVisibleDefinitions();

    foreach ($definitions as $id => $definition) {
      $alias_type = $this->aliasTypeManager->createInstance($id);
      $form['neutral_paths_fix_new']['#options'][$id] = $alias_type->getLabel();
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('neutral_paths.settings');

    $values = $form_state->getValues();
    $config->set('neutral_paths_fix_new', array_keys(array_filter($values['neutral_paths_fix_new'])));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
