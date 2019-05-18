<?php

namespace Drupal\admin_user_language\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BasicForm.
 *
 * @package Drupal\admin_user_language\Form
 */
class BasicForm extends ConfigFormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  private $languageManager;

  /**
   * BasicForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *    The config factory.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *    The language manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManager $languageManager) {
    parent::__construct($config_factory);
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'admin_user_language.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basic_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('admin_user_language.settings');
    $form['default_language_to_assign'] = [
      '#type' => 'select',
      '#title' => $this->t('Default language to assign'),
      '#description' => $this->t('Select a default administration language to assign on user registration/update.'),
      '#options' => $this->getActiveLanguages(),
      '#size' => 1,
      '#default_value' => $config->get('default_language_to_assign'),
    ];

    $form['prevent_user_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force language'),
      '#description' => $this->t('Activating this option a user will not be able to save its chosen administration language.'),
      '#default_value' => $config->get('prevent_user_override'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Gets the active languages.
   *
   * @return array
   *    Returns the active languages.
   */
  protected function getActiveLanguages() {
    $languages = $this->languageManager->getLanguages();

    $displayLanguages = [
      '-1' => $this->t('- No preference -')
    ];
    /** @var \Drupal\Core\Language\Language $lang */
    // Building an array of language code => language name.
    foreach ($languages as $lang) {
      $displayLanguages[$lang->getId()] = $lang->getName();
    }
    return $displayLanguages;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('admin_user_language.settings')
      ->set('default_language_to_assign', $form_state->getValue('default_language_to_assign'))
      ->set('prevent_user_override', $form_state->getValue('prevent_user_override'))
      ->save();
  }

}
