<?php

namespace Drupal\entityqueryapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Url;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConfigForm.
 *
 * @package Drupal\entityqueryapi\Form
 */
class ConfigController extends ControllerBase {
  use ConfigFormBaseTrait;

  /**
   * The config factory interface.
   */
  protected $configFactory;

  /**
   * The Entity Type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'entityqueryapi.config',
    ];
  }

  /**
   * Constructs a \Drupal\entityqueryapi\Form\ConfigForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function listConfig() {
    $definitions = $this->entityTypeManager->getDefinitions();
    $config = $this->config('entityqueryapi.config');

    // Translates a definition into just the simple info we need.
    $get_type_data = function ($definition) use ($config) {
      $entity_type_id = $definition->id();
      return array(
        'id' => $entity_type_id,
        'enabled' => $config->get("{$entity_type_id}.enabled"),
        'group' => $definition->getGroup(),
        'groupLabel' => $definition->getGroupLabel(),
        'name' => $definition->getLabel(),
        'path' => "entity/{$entity_type_id}",
      );
    };

    // Translates that simple info into a table row render array.
    $build_row = function ($type_data) {
      $row = array(
        'name'    => array('#markup' => sprintf("<p>%s</p>", $type_data['name'])), 
        'path'    => array('#markup' => sprintf("<p>%s</p>", $type_data['path'])), 
        'operations'  => array(
          '#type'  => 'operations',
          '#links' => array(),
        ),
      );

      $op = ($type_data['enabled']) ? 'disable' : 'enable';
      switch ($op) {
      case 'enable':
        $link = Url::fromRoute(
          'entityqueryapi.enableRoute',
          array('entity_type' => $type_data['id']),
          array('query' => array('token' => \Drupal::csrfToken()->get("entityqueryapi_{$op}")))
        );
        $row['operations']['#links']['enable']['title'] = $this->t('Enable');
        $row['operations']['#links']['enable']['url'] = $link;
        break;
      case 'disable':
        $link = Url::fromRoute(
          'entityqueryapi.disableRoute',
          array('entity_type' => $type_data['id']),
          array('query' => array('token' => \Drupal::csrfToken()->get("entityqueryapi_{$op}")))
        );
        $row['operations']['#links']['disable']['title'] = $this->t('Disable');
        $row['operations']['#links']['disable']['url'] = $link;
        break;
      }

      return $row;
    };

    // Reducer which sorts all the data arrays into the appropriate tables and
    // formats their rows.
    $build_tables = function ($tables, $type_data) use ($build_row) {
      // Get keys to the table to build.
      $enabled = ($type_data['enabled']) ? 'enabled' : 'disabled';
      $group = $type_data['group'];

      // If the table array doesn't exist, create it.
      if (!isset($tables[$enabled][$group])) $tables[$enabled][$group] = array(
        'label' => array(
          '#markup' => $type_data['groupLabel'],
          '#prefix' => "<h3>",
          '#suffix' => " Entities</h3>",
        ),
        'table' => array(
          '#type'   => 'table',
          '#header' => array(
            $this->t('Entity Type'),
            $this->t('Path'),
            $this->t('Operations'),
          ),
        ),
      );

      // Build and add a row for the current type.
      $tables[$enabled][$group]['table'][] = $build_row($type_data);

      return $tables;
    };

    // Build an array of all the tables we need.
    $tables = array_reduce(array_map($get_type_data, $definitions), $build_tables);

    $render['enabled_heading'] = array(
      '#markup' => $this->t('Enabled'),
      '#prefix' => "<h2>",
      '#suffix' => "</h2>",
    );

    $render['enabled_tables'][] = (!empty($tables['enabled'])) ? $tables['enabled'] : array(
      '#markup' => $this->t('No enabled entity query routes.'),
      '#prefix' => "<p>",
      '#suffix' => "</p>",
    );

    $render['disabled_heading'] = array(
      '#markup' => $this->t('Disabled'),
      '#prefix' => "<h2>",
      '#suffix' => "</h2>",
    );

    $render['disabled_tables'][] = (!empty($tables['disabled'])) ? $tables['disabled'] : array(
      '#markup' => $this->t('No disabled entity query routes.'),
      '#prefix' => "<p>",
      '#suffix' => "</p>",
    );

    return $render;
  }

  /**
   * Enables an Entity Query API route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition for the route to enable.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the listing page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Access is denied, if the token is invalid or missing.
   */
  public function enableRoute($entity_type, Request $request) {
    if (!\Drupal::csrfToken()->validate($request->query->get('token'), 'entityqueryapi_enable')) {
      throw new AccessDeniedHttpException();
    }

    $config = $this->config('entityqueryapi.config');

    $entity_type_id = $entity_type->id();
    $enabled = $config->get("{$entity_type_id}.enabled");

    if (!$enabled) {
      $config->set("{$entity_type_id}.enabled", TRUE);
      $config->save();
      drupal_set_message(t('The route was enabled successfully.'));
    }
    $gen = $this->getUrlGenerator();

    // Redirect back to the page.
    return new RedirectResponse(
      $this->getUrlGenerator()->generate(
        'entityqueryapi.listConfig',
        array(),
        TRUE
      )
    );
  }

  /**
   * Disables an Entity Query API route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition for the route to disable.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the listing page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Access is denied, if the token is invalid or missing.
   */
  public function disableRoute($entity_type, Request $request) {
    if (!\Drupal::csrfToken()->validate($request->query->get('token'), 'entityqueryapi_disable')) {
      throw new AccessDeniedHttpException();
    }

    $config = $this->config('entityqueryapi.config');

    $entity_type_id = $entity_type->id();
    $enabled = $config->get("{$entity_type_id}.enabled");

    if ($enabled) {
      $config->set("{$entity_type_id}.enabled", FALSE);
      $config->save();
      drupal_set_message(t('The route was disabled successfully.'));
    }

    // Redirect back to the page.
    return new RedirectResponse(
      $this->getUrlGenerator()->generate(
        'entityqueryapi.listConfig',
        array(),
        TRUE
      )
    );
  }

}
