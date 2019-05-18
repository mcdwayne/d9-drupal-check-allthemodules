<?php

namespace Drupal\adminrss\Form;

use Drupal\adminrss\AdminRss;
use Drupal\adminrss\ViewsManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AdminRssSettingsForm is the AdminRSS configuration form.
 */
class AdminRssSettingsForm extends ConfigFormBase {
  /**
   * The adminrss.views_manager service.
   *
   * @var \Drupal\adminrss\ViewsManager
   */
  protected $viewsManager;

  /**
   * AdminRssSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   * @param \Drupal\adminrss\ViewsManager $views_manager
   *   The adminrss.views_manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ViewsManager $views_manager) {
    parent::__construct($config_factory);
    $this->viewsManager = $views_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $config_factory = $container->get('config.factory');
    $views_manager = $container->get('adminrss.views_manager');
    return new static($config_factory, $views_manager);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      AdminRss::CONFIG,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_rss_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(AdminRss::CONFIG);
    $token = $config->get(AdminRss::TOKEN);

    $form[AdminRss::TOKEN] = array(
      '#default_value' => $token,
      '#description' => t('This is the token that will be required in order to get access to the AdminRSS feeds.'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#size' => 50,
      '#title' => $this->t('Admin RSS Token'),
      '#type' => 'textfield',
      '#weight' => -5,
    );

    $feed_links = $this->viewsManager->getFeedLinks();
    if (!empty($feed_links)) {
      $form['feeds'] = array(
        '#type' => 'details',
        '#title' => $this->t('Admin RSS Feeds locations'),
        '#description' => $this->t('Copy and paste these links to your RSS aggregator.'),
        '#open' => TRUE,
      );

      $form['feeds']['links'] = array(
        '#theme' => 'item_list',
        '#items' => $feed_links,
      );
    }

    $form_with_actions = parent::buildForm($form, $form_state);
    $form_with_actions['actions']['save-new'] = [
      '#button_type' => 'default',
      '#op' => 'new',
      '#type' => 'submit',
      '#value' => $this->t('Save with new generated token'),
    ];
    return $form_with_actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $token = empty($form_state->getTriggeringElement()['#op'])
      ? $form_state->getValue(AdminRss::TOKEN)
      : NULL;
    AdminRss::saveNewToken($token);
    parent::submitForm($form, $form_state);
  }

}
