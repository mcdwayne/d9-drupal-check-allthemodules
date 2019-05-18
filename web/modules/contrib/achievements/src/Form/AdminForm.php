<?php

namespace Drupal\achievements\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AdminForm.
 *
 * @package Drupal\achievements\Form
 */
class AdminForm extends ConfigFormBase {

  /**
   *
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
    return ['achievements.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'achievements_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('achievements.settings');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $settings = [];
    foreach ($settings as $name) {
      $this->config('achievements.settings')
        ->set($name, $form_state->getValue($name));
    }

    $this->config('achievements.settings')->save();
    parent::submitForm($form, $form_state);
  }

}
