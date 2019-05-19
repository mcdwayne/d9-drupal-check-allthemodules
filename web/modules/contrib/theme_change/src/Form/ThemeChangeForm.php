<?php

namespace Drupal\theme_change\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Example add and edit forms.
 */
class ThemeChangeForm extends EntityForm {

  /**
   * Constructs an ThemeChangeForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $theme_change = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $theme_change->label(),
      '#description' => $this->t("Label for the ThemeChange."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $theme_change->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$theme_change->isNew(),
    ];
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Route/Path'),
      '#options' => ['route' => 'Route', 'path' => 'Path'],
      '#default_value' => $theme_change->get_type(),
    ];
    $form['route'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter Route'),
      '#description' => $this->t("Enter route to change theme."),
      '#states' => [
        'visible' => ['select[name=type]' => ['value' => 'route']],
      ],
      '#default_value' => $theme_change->get_route(),
    ];
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter Path'),
      '#description' => $this->t("Enter Path to change theme. And also Supports wildcards like(/user/*, /node/*)"),
      '#states' => [
        'visible' => ['select[name=type]' => ['value' => 'path']],
      ],
      '#default_value' => $theme_change->get_path(),
    ];
    $list_themes = [];
    $themes = \Drupal::service('theme_handler')->listInfo();
    foreach ($themes as $key => $value) {
      $list_themes[$key] = \Drupal::service('theme_handler')->getName($key);
    }
    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Theme'),
      '#options' => $list_themes,
      '#default_value' => $theme_change->get_theme(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $route = $form_state->getValue('route');
    $path = $form_state->getValue('path');
    $type = $form_state->getValue('type');
    $route_provider = \Drupal::service('router.route_provider');
    $exists = count($route_provider->getRoutesByNames([$route])) === 1;
    // If no route exists.
    if ($type == 'route' && $route && !$exists) {
      $form_state->setErrorByName('route', $this->t('%route Route Doesnot exists', ['%route' => $route]));
    }
    // If type is route and path value is filled.
    if ($type == 'route' && !$route && $path) {
      $form_state->setErrorByName('type', $this->t("Select type as Path"));
    }
    // If type is path and route value is filled.
    if ($type == 'path' && !$path && $route) {
      $form_state->setErrorByName('type', $this->t("Select type as Route"));
    }
    if ($type == 'path' && $path[0] !== '/') {
      $form_state->setErrorByName('path', t('The path needs to start with a slash.'));
    }
    // If route and path values are not filled.
    if (!$route && !$path) {
      $form_state->setErrorByName('route', $this->t('Route/Path Required'));
      $form_state->setErrorByName('path');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $theme_change = $this->entity;
    $status = $theme_change->save();
    if ($status) {
      drupal_set_message($this->t('Saved the %label theme change.', ['%label' => $theme_change->label()]));
    }
    else {
      drupal_set_message($this->t('The %label theme change was not saved.', ['%label' => $theme_change->label()]));
    }
    $form_state->setRedirect('entity.theme_change.collection');
  }

  /**
   * Helper function to check whether an ThemeChange configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityQuery->get('theme_change')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
