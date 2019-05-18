<?php

namespace Drupal\search_api_view_modes\Plugin\search_api\processor;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\UserSession;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\search_api\Datasource\DatasourceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Item\ItemInterface;

/**
 * Adds additional fields containing the rendered items. Use this when you want to store multiple renderings.
 *
 * @SearchApiProcessor(
 *   id = "rendered_items",
 *   label = @Translation("Rendered items"),
 *   description = @Translation("Adds additional fields containing the rendered items as they would look when viewed. Use this when you want to store multiple renderings."),
 *   stages = {
 *     "pre_index_save" = -10,
 *     "preprocess_index" = -30
 *   }
 * )
 */
class RenderedItems extends ProcessorPluginBase {

  /**
   * The current_user service used by this plugin.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|null
   */
  protected $currentUser;

  /**
   * The renderer to use.
   *
   * @var \Drupal\Core\Render\RendererInterface|null
   */
  protected $renderer;

  /**
   * The logger to use for log messages.
   *
   * @var \Psr\Log\LoggerInterface|null
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    /** @var \Drupal\Core\Session\AccountProxyInterface $current_user */
    $current_user = $container->get('current_user');
    $plugin->setCurrentUser($current_user);

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $container->get('renderer');
    $plugin->setRenderer($renderer);

    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = $container->get('logger.factory')->get('search_api');
    $plugin->setLogger($logger);

    return $plugin;
  }

  /**
   * Retrieves the current user.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   *   The current user.
   */
  public function getCurrentUser() {
    return $this->currentUser ?: \Drupal::currentUser();
  }

  /**
   * Sets the current user.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   *
   * @return $this
   */
  public function setCurrentUser(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
    return $this;
  }

  /**
   * Retrieves the renderer.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer.
   */
  public function getRenderer() {
    return $this->renderer ?: \Drupal::service('renderer');
  }

  /**
   * Sets the renderer.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The new renderer.
   *
   * @return $this
   */
  public function setRenderer(RendererInterface $renderer) {
    $this->renderer = $renderer;
    return $this;
  }

  /**
   * Retrieves the logger to use for log messages.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger to use.
   */
  public function getLogger() {
    return $this->logger ?: \Drupal::logger('search_api');
  }

  /**
   * Sets the logger to use for log messages.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The new logger.
   *
   * @return $this
   */
  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'roles' => array(AccountInterface::ANONYMOUS_ROLE),
      'view_modes' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $roles = user_role_names();

    $form['roles'] = array(
      '#type' => 'select',
      '#title' => $this->t('User roles'),
      '#description' => $this->t('Your item will be rendered as seen by a user with the selected roles. We recommend to just use "@anonymous" here to prevent data leaking out to unauthorized roles.', array('@anonymous' => $roles[AccountInterface::ANONYMOUS_ROLE])),
      '#options' => $roles,
      '#multiple' => TRUE,
      '#default_value' => $this->configuration['roles'],
      '#required' => TRUE,
    );

    $form['view_modes'] = array(
      '#type' => 'item',
      '#description' => $this->t('You can choose the view modes to use for rendering the items of each datasource.'),
    );

    $options_present = FALSE;

    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      $view_modes = $datasource->getViewModes();

      if (count($view_modes) > 1) {
        $form['view_modes'][$datasource_id] = array(
          '#type' => 'select',
          '#title' => $this->t('View modes for %datasource', array('%datasource' => $datasource->label())),
          '#options' => $view_modes,
          '#multiple' => TRUE,
        );

        if (isset($this->configuration['view_modes'][$datasource_id])) {
          $form['view_modes'][$datasource_id]['#default_value'] = $this->configuration['view_modes'][$datasource_id];
        }

        $options_present = TRUE;
      } else {
        $form['view_modes'][$datasource_id] = array(
          '#type' => 'value',
          '#value' => $view_modes ? key($view_modes) : FALSE,
        );
      }
    }

    // If there are no datasources/bundles with more than one view mode, don't
    // display the description either.
    if (!$options_present) {
      unset($form['view_modes']['#type']);
      unset($form['view_modes']['#description']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterPropertyDefinitions(array &$properties, DatasourceInterface $datasource = NULL) {
    if ($datasource) {
      return;
    }

    $config = $this->getConfiguration();

    foreach ($config['view_modes'] as $source => $modes) {
      foreach ($modes as $mode) {
        $definition = array(
          'type' => 'text',
          'label' => $this->t('Rendered HTML output (@mode view mode)', array('@mode' => $mode)),
          'description' => $this->t('The complete HTML which would be displayed when viewing the item', array('@mode' => $mode)),
        );

        $properties['view_mode_' . $mode] = new DataDefinition($definition);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    $config = $this->getConfiguration();

    foreach ($config['view_modes'] as $datasource => $modes) {
      foreach ($modes as $mode) {
        $this->ensureField(NULL, 'view_mode_' . $mode);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array &$items) {
    // Change the current user to our dummy implementation to ensure we are
    // using the configured roles.
    $original_user = $this->currentUser->getAccount();
    $this->currentUser->setAccount(new UserSession(array('roles' => $this->configuration['roles'])));

    // Annoyingly, this doc comment is needed for PHPStorm. See
    // http://youtrack.jetbrains.com/issue/WI-23586
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {
      $datasource_id = $item->getDatasourceId();
      $datasource = $item->getDatasource();

      foreach ($datasource->getViewModes() as $mode => $label) {
        if (in_array($mode, $this->configuration['view_modes'][$datasource_id])) {
          $build = $datasource->viewItem($item->getOriginalObject(), $mode);
          $value = (string) $this->getRenderer()->renderPlain($build);

          if ($value) {
            $field = $item->getField('view_mode_' . $mode);
            $field->addValue($value);
          }
        }
      }
    }

    // Restore the original user.
    $this->currentUser->setAccount($original_user);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    $view_modes = $this->configuration['view_modes'];
    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      if (($entity_type_id = $datasource->getEntityTypeId()) && !empty($view_modes[$datasource_id])) {
        foreach ($view_modes[$datasource_id] as $view_mode) {
          if ($view_mode) {
            /** @var \Drupal\Core\Entity\EntityViewModeInterface $view_mode_entity */
            $view_mode_entity = EntityViewMode::load($entity_type_id . '.' . $view_mode);
            if ($view_mode_entity) {
              $this->addDependency($view_mode_entity->getConfigDependencyKey(), $view_mode_entity->getConfigDependencyName());
            }
          }
        }
      }
    }

    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    // All dependencies of this processor are entity view modes, so we go
    // through our configuration and remove the settings for all datasources or
    // bundles which were set to one of the removed view modes. This will always
    // result in the removal of all those dependencies.
    // The code is highly similar to calculateDependencies(), only that we
    // remove the setting (if necessary) instead of adding a dependency.
    $view_modes = $this->configuration['view_modes'];
    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      if ($entity_type_id = $datasource->getEntityTypeId() && !empty($view_modes[$datasource_id])) {
        foreach ($view_modes[$datasource_id] as $bundle => $view_mode_id) {
          if ($view_mode_id) {
            /** @var \Drupal\Core\Entity\EntityViewModeInterface $view_mode */
            $view_mode = EntityViewMode::load($entity_type_id . '.' . $view_mode_id);
            if ($view_mode) {
              $dependency_key = $view_mode->getConfigDependencyKey();
              $dependency_name = $view_mode->getConfigDependencyName();
              if (!empty($dependencies[$dependency_key][$dependency_name])) {
                unset($this->configuration['view_modes'][$datasource_id][$view_mode->label()]);
              }
            }
          }
        }
      }
    }

    return TRUE;
  }

}
