<?php
/**
 * @file
 * Contains \Drupal\mailmute\Controller\MailmuteController.
 */

namespace Drupal\mailmute\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\mailmute\SendStateManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * General content controller for Mailmute.
 */
class MailmuteController extends ControllerBase {

  /**
   * Injected send state manager.
   *
   * @var \Drupal\mailmute\SendStateManagerInterface
   */
  protected $manager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Creates a new Mailmute controller.
   */
  public function __construct(SendStateManagerInterface $manager, RendererInterface $renderer) {
    $this->manager = $manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.sendstate'),
      $container->get('renderer')
    );
  }

  /**
   * Returns a hierarchical list of all send state plugins.
   */
  public function sendstateList() {
    $build['states'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('State'),
        $this->t('Module'),
        $this->t('Description'),
        $this->t('Muting'),
      ),
    );

    // Fill table rows with plugin details. Elements are addded directly instead
    // of using #rows, in order to enable #markup for indentation.
    foreach ($this->manager->getPluginHierarchyLevels() as $id => $level) {
      $definition = $this->manager->getDefinition($id);
      $indentation = array(
        '#theme' => 'indentation',
        '#size' => $level,
      );
      $build['states'][] = array(
        'label' => array('#markup' => $this->renderer->render($indentation) . $definition['label']),
        'module' => array('#markup' => $definition['provider']),
        'description' => array('#markup' => $definition['description']),
        'muting' => array('#markup' => $definition['mute'] ? $this->t('Yes') : $this->t('No')),
      );
    }

    return $build;
  }

}
