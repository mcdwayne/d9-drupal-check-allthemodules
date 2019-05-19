<?php

namespace Drupal\views_tag_access\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\views\Entity\View;
use Drupal\views_tag_access\ViewsTagAccessHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Views Tag Access settings for this site.
 */
class ViewsTagAccessSettingsForm extends ConfigFormBase {

  /**
   * The private tempstore.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempstoreFactory;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PrivateTempStoreFactory $tempstore_factory) {
    $this->setConfigFactory($config_factory);
    $this->tempstoreFactory = $tempstore_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_tag_access_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'views_tag_access.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('views_tag_access.settings');

    $form['tags'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allow permissions for the selected tags'),
      '#options' => [],
      '#default_value' => $config->get('tags'),
    ];

    // Ensure anything defined in config is included.
    foreach (array_filter($form['tags']['#default_value']) as $tag) {
      $form['tags']['#options'][$tag] = $tag;
    }

    // Add in any other tags from views to be used.
    foreach (View::loadMultiple() as $view) {
      /** @var \Drupal\views\Entity\View $view */
      $helper = new ViewsTagAccessHelper($view, $this->configFactory(), $this->currentUser(), $this->tempstoreFactory);
      foreach ($helper->getTags() as $tag) {
        $form['tags']['#options'][$tag] = $this->t('@tag (in use)', ['@tag' => $tag]);
      }
    }

    $form['additional_tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional tags'),
      '#description' => $this->t('Enter a comma separated list of additional tags to add.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tags = $form_state->getValue('tags');
    $additional_tags = array_map('trim', explode(',', $form_state->getValue('additional_tags')));
    $tags = array_unique(array_filter(array_merge($tags, $additional_tags)));

    $this->config('views_tag_access.settings')
      ->set('tags', $tags)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
