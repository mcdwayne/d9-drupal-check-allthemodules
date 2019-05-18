<?php

namespace Drupal\route_iframes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Routing\RouteBuilderInterface;

/**
 * Class RouteIframesConfiguration.
 *
 * @package Drupal\route_iframes\Form
 */
class RouteIframesConfiguration extends ConfigFormBase {

  /**
   * Drupal\user\PrivateTempStoreFactory definition.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $userPrivateTempStore;

  /**
   * Drupal\Core\Routing\RouteBuilder definition.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(PrivateTempStoreFactory $user_private_temp_store, RouteBuilderInterface $route_builder) {
    $this->userPrivateTempStore = $user_private_temp_store->get('route_iframes');
    $this->routeBuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'route_iframes.routeiframesconfiguration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'route_iframes_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('route_iframes.routeiframesconfiguration');
    $form['route_iframe_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Route Iframe Base URL'),
      '#description' => $this->t('Enter the base URL that should be used in all iframes. Please do not include a trailing slash.'),
      '#maxlength' => 100,
      '#size' => 64,
      '#default_value' => $config->get('route_iframe_base_url'),
    ];
    $form['route_iframe_main_tab_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of main tab'),
      '#description' => $this->t('If you create default, content type, or list of ID Route Iframe configurations, this is the text that appears on the tab of node pages.'),
      '#default_value' => $config->get('route_iframe_main_tab_name'),
    ];
    $form['route_iframe_main_tab_path'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Path of main tab'),
      '#description' => $this->t('If you create default, content type, or list of ID Route Iframe configurations, this is part of the path that appears after node/nid/ for the tab that appears on node pages.'),
      '#default_value' => $config->get('route_iframe_main_tab_path'),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ]
    ];
    // @todo: These tabs should have an order / weight to control tab order.
    $form['route_iframe_tabs'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Tabs'),
      '#prefix' => '<div id="route-iframe-tabs">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    $form['route_iframe_tabs']['instructions'] = [
      '#type' => 'html_tag',
      '#tag' => 'em',
      '#value' => $this->t('If more than one tab is defined, the tabs will be available to choose from on the route iframe configurations. This separates how items are overridden and allows for sub tabs to display under the main tab of node pages.'),
    ];

    $tabs = $this->userPrivateTempStore->get('route_iframe_tabs');
    if (empty($tabs)) {
      $tabs = $config->get('route_iframe_tabs');
      if (empty($tabs)) {
        $tabs = [];
        $tabs[] = ['name' => '', 'path' => ''];
      }
      $this->userPrivateTempStore->set('route_iframe_tabs', $tabs);
    }
    foreach ($tabs as $key => $tab) {
      $form['route_iframe_tabs'][$key] = [
        '#type' => 'fieldset',
      ];
      $form['route_iframe_tabs'][$key]['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Tab Name'),
        '#default_value' => $tab['name'],
        '#description' => $this->t('This text will show on the actual tab.')
      ];
      $form['route_iframe_tabs'][$key]['path'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Tab partial path'),
        '#default_value' => $tab['path'],
        '#description' => $this->t('This will be the path that shows after the main tab path above.'),
      ];
      $form['route_iframe_tabs'][$key]['remove_tab'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove tab'),
        '#submit' => ['::removeTab'],
        '#name' => $key,
        '#ajax' => [
          'callback' => '::updateTabsCallback',
          'wrapper' => 'route-iframe-tabs',
        ],
      ];
    }

    $form['route_iframe_tabs']['add_tab'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add tab'),
      '#submit' => ['::addTab'],
      '#ajax' => [
        'callback' => '::updateTabsCallback',
        'wrapper' => 'route-iframe-tabs',
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * A submit function to add a tab element to the form.
   *
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function addTab(array &$form, FormStateInterface $form_state) {
    $tabs = $this->userPrivateTempStore->get('route_iframe_tabs');
    $tabs[] = ['name' => '', 'path' => ''];
    $this->userPrivateTempStore->set('route_iframe_tabs', $tabs);
    $form_state->setRebuild();
  }

  /**
   * A submit function to remove a tab element from the form.
   *
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function removeTab(array &$form, FormStateInterface $form_state) {
    $tabs = $this->userPrivateTempStore->get('route_iframe_tabs');
    $form_tabs = $form_state->getValue('route_iframe_tabs');
    foreach ($tabs as $key => &$tab) {
      $tab['name'] = $form_tabs[$key]['name'];
      $tab['path'] = $form_tabs[$key]['path'];
    }
    $triggering_element = $form_state->getTriggeringElement();
    unset($tabs[$triggering_element['#name']]);
    $this->userPrivateTempStore->set('route_iframe_tabs', $tabs);
    $form_state->setRebuild();
  }

  /**
   * The ajax callback to replace form elements.
   *
   * This updates the tabs form element after the add tab or remove tab are
   * processed.
   *
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return mixed
   *   The form element to be updated by ajax.
   */
  public function updateTabsCallback(array &$form, FormStateInterface $form_state) {
    return $form['route_iframe_tabs'];
  }

  /**
   * Function to make a machine name element work.
   *
   * The machine name form element used for route_iframe_main_tab_path will
   * try to check for existing items with that name. This function allows any
   * value to be considered unique since the field itself does not represent
   * a list of data.
   *
   * @param string $value
   *   The value submitted with the form.
   * @param array $element
   *   The form element that is being submitted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Always returns FALSE to indicate that the item is unique.
   */
  public function exists($value, array $element, FormStateInterface $form_state) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $tabs = $form_state->getValue('route_iframe_tabs');
    foreach ($tabs as $key => $tab) {
      if ($key !== 'add_tab') {
        if (empty($tab['name']) && empty($tab['path'])) {
          unset($tabs[$key]);
        }
        elseif (empty($tab['name']) || empty($tab['path'])) {
          $form_state->setErrorByName('route_iframe_tabs',
            'Each tab must have a name and path.');
        }
      }
    }
    $form_state->setValue('route_iframe_tabs', $tabs);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $fields = [
      'route_iframe_base_url',
      'route_iframe_main_tab_name',
      'route_iframe_main_tab_path',
      'route_iframe_tabs',
    ];
    $config = $this->config('route_iframes.routeiframesconfiguration');

    // Remove buttons from tabs tree.
    $tabs = $form_state->getValue('route_iframe_tabs');
    unset($tabs['add_tab']);
    foreach ($tabs as &$tab) {
      unset($tab['remove_tab']);
    }
    $form_state->setValue('route_iframe_tabs', $tabs);
    foreach ($fields as $field) {
      $value = $form_state->getValue($field);
      if ($field == 'route_iframe_base_url') {
        // Strip off any trailing slashes if they exist.
        $value = trim($value, '/');
      }
      $config->set($field, $value);
    }
    $config->save();
    $this->userPrivateTempStore->delete('route_iframe_tabs');
    // @todo: If tabs change, the related configuration entities need to change.
    // Clear all caches since this update requires multiple bins to be cleared.
    drupal_flush_all_caches();
  }

}
