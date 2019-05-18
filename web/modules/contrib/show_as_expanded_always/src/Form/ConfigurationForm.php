<?php

namespace Drupal\show_as_expanded_always\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Class ConfigurationForm.
 */
class ConfigurationForm extends ConfigFormBase {

  /**
   * Entity Query used for menu fetching.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactory $config_factory, QueryFactory $entityQuery) {
    parent::__construct($config_factory);
    $this->entityQuery = $entityQuery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'show_as_expanded_always.configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'show_as_expanded_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('show_as_expanded_always.configuration');

    $menus = $this->getAvailableMenus();

    /** @var \Drupal\system\Entity\Menu $menu */
    foreach ($menus as $menu) {
      $configName = 'enable_' . $menu->id();
      $menuLabel = $menu->label() === NULL ? $menu->id() : $menu->label();

      $form[$configName] = [
        '#type' => 'checkbox',
        '#title' => $menuLabel,
        '#description' => sprintf($this->t('If checked, "Show as expanded" will be checked by default for menu "%s"'), $menuLabel),
        '#default_value' => $config->get($configName) === NULL ? TRUE : $config->get($configName),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('show_as_expanded_always.configuration');

    $menus = $this->getAvailableMenus();

    /** @var \Drupal\system\Entity\Menu $menu */
    foreach ($menus as $menu) {
      $configName = 'enable_' . $menu->id();

      $config->set($configName, $form_state->getValue($configName));
    }
    $config->save();
  }

  /**
   * Get all available menus.
   *
   * @return array
   *   All available menus of type Menu as array.
   */
  protected function getAvailableMenus() {
    $menuIds = $this->entityQuery->get('menu', 'AND')
      ->condition('status', 1)
      ->execute();

    $menus = [];
    foreach (array_keys($menuIds) as $menuId) {
      $menu = Menu::load($menuId);
      if ($menu) {
        $menus[] = $menu;
      }
    }

    return $menus;
  }

}
