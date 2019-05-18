<?php

namespace Drupal\commerce_country_store\Form;


use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_store\CurrentStore;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;

class StoreSelectorForm extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * @var Store
   */
  protected $store;

  /**
   * The current request
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, CurrentStore $currentStore, RequestStack $requestStack, AliasManagerInterface $alias_manager,  CurrentPathStack $current_path) {
    $this->store = $currentStore->getStore();
    $this->storage = $entity_type_manager->getStorage('commerce_store');
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->aliasManager = $alias_manager;
    $this->currentPath = $current_path;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('commerce_store.current_store'),
      $container->get('request_stack'),
      $container->get('path.alias_manager'),
      $container->get('path.current')
    );
  }

  public function getFormId() {
    return 'commerce_country_store_selector';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $default = FALSE;

    $current_path = $this->currentPath->getPath();

    foreach ($this->storage->loadMultiple() as $store) {
      $path_prefix = "";
      if ($store->hasField('path_prefix')) {
        $path_prefix = $store->path_prefix->value;
      }

      $path_alias = $this->aliasManager->getAliasByPath($current_path, $path_prefix);
      $url_string = "/".$path_prefix.$path_alias;

      if ($store->id() == $this->store->id()) {
        $default = $url_string;
      }

      $options[$url_string] = $this->storeLabel($store);
    }

    $form['selector'] = [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $default,
      '#attributes' => [
        'class' => ['currency-select'],
        'onChange' => ['window.location.href=this.value']
      ],
    ];

    return $form;
  }

  protected function storeLabel(Store $store) {
    return $store->label();
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url_string = $form_state->getValue('selector');
    $form_state->setRedirectUrl($url_string);
  }

}