<?php

namespace Drupal\arb_token\Form;

use Drupal\arb_token\ArbitraryTokenPluginManager;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ArbitraryTokenForm.
 */
class ArbitraryTokenForm extends EntityForm {

  /**
   * The plugin manager.
   *
   * @var \Drupal\arb_token\ArbitraryTokenPluginManager
   */
  protected $pluginManager;

  /**
   * ArbitraryTokenForm constructor.
   *
   * @param \Drupal\arb_token\ArbitraryTokenPluginManager $plugin_manager
   *   The plugin manager.
   */
  public function __construct(ArbitraryTokenPluginManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.arb_token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $plugins = $this->pluginManager->getDefinitions();
    $plugins = array_map(function ($item) {
      return $item['label'];
    }, $plugins);

    if (!$plugin = $this->entity->getPluginId()) {
      $plugin = reset($plugins);
    }
    asort($plugins);

    $wrapper_id = $form['#wrapper_id'] = Html::getUniqueId('arb-token-form');
    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\arb_token\Entity\ArbitraryToken::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];
    $form['plugin'] = [
      '#type' => 'radios',
      '#title' => $this->t('Plugin'),
      '#options' => $plugins,
      '#default_value' => $plugin,
      '#required' => TRUE,
      '#disabled' => !$this->entity->isNew(),
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => $wrapper_id,
      ],
    ];

    if ($plugin = $this->getPlugin()) {
      $form['configuration'] = $plugin->buildConfigurationForm($form, $form_state);
    }

    return $form;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $form_state->setRedirect('entity.arb_token.collection');
  }

  /**
   * Gets the action plugin while ensuring it implements configuration form.
   *
   * @return \Drupal\Core\Action\ActionInterface|\Drupal\Core\Plugin\PluginFormInterface|null
   *   The arbitrary token plugin, or NULL if it does not implement
   *   configuration forms.
   */
  protected function getPlugin() {
    if ($this->entity->getPlugin() instanceof PluginFormInterface) {
      return $this->entity->getPlugin();
    }

    return NULL;
  }

}
