<?php

namespace Drupal\tmgmt_extension_suit\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TMGMT Extension Suit settings form.
 */
class TmgmtExtensionSuitSettingsForm extends ConfigFormBase {

  /**
   * Constructs a TmgmtExtensionSuitSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
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
  protected function getEditableConfigNames() {
    return [
      'tmgmt_extension_suit.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tmgmt_extension_suit_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('tmgmt_extension_suit.settings');

    $form['do_track_changes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Track changes of the translatable entities.'),
      '#description' => 'If checked, all the entities that once were submitted for translation, would be re-sent automatically.',
      '#default_value' => $config->get('do_track_changes'),
      '#required' => FALSE,
    ];


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('tmgmt_extension_suit.settings');
    $config
      ->set('do_track_changes', $form_state->getValue('do_track_changes'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
