<?php
namespace Drupal\pagedesigner\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Node\Entity\Node;
use Drupal\pagedesigner\Service\RendererPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Pagedesigner block
 *
 * @Block(
 *   id = "pagedesigner",
 *   admin_label = @Translation("Pagedesigner"),
 * )
 */

class Pagedesigner extends BlockBase implements ContainerFactoryPluginInterface
{

    protected $_rendererManager = null;

    /**
     * @param array $configuration
     * @param string $plugin_id
     * @param mixed $plugin_definition
     * @param RendererPluginManager $nodecollector
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererPluginManager $nodecollector)
    {
        // Call parent construct method.
        parent::__construct($configuration, $plugin_id, $plugin_definition);

        // Store our dependency.
        $this->_rendererManager = $nodecollector;
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param array $configuration
     * @param string $plugin_id
     * @param mixed $plugin_definition
     * @return static
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('plugin.manager.pagedesigner_handler')
        );
    }

    public function build()
    {
        $build = [];
        // Get node from route
        $node = \Drupal::routeMatch()->getParameter('node');
        if ($node != null) {
            $renderer = \Drupal::service('pagedesigner.service.render');
            if (\Drupal::currentUser()->hasPermission('edit pagedesigner element entities') || \Drupal::currentUser()->hasPermission('view unpublished pagedesigner element entities')) {
                $renderer->render($node);
            } else {
                $renderer->renderForPublic($node);
            }
            $build = array(
                '#type' => 'markup',
                '#markup' => $renderer->getMarkup($node),
                '#cache' =>
                ['max-age' => 0],
            );
        }
        return $build;
    }
}
