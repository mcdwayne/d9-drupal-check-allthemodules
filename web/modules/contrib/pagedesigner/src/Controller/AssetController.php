<?php

namespace Drupal\pagedesigner\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\pagedesigner\Service\AssetPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AssetController.
 */
class AssetController extends ControllerBase
{

    /**
     * Drupal\pagedesigner\Service\AssetPluginManager definition.
     *
     * @var \Drupal\pagedesigner\Service\AssetPluginManager
     */
    protected $assetManager;

    /**
     * Constructs a new AssetController object.
     */
    public function __construct(AssetPluginManager $asset_manager)
    {
        $this->assetManager = $asset_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('plugin.manager.pagedesigner_asset')
        );
    }

    /**
     * Getassetform.
     *
     * @return AjaxResponse
     *   Return Hello string.
     */
    public function getSearchForm($type)
    {
        $response = new AjaxResponse();
        $handler = $this->assetManager->getInstance(['type' => $type]);
        $form = $handler->getSearchForm();
        $response->addCommand(new InsertCommand('.gjs-am-add-asset', $form));
        return $response;
    }
    /**
     * Getassetform.
     *
     * @return AjaxResponse
     *   Return Hello string.
     */
    public function getCreateForm()
    {
        $response = new AjaxResponse();
        $handler = $this->assetManager->getInstance(['type' => 'image']);
        $form = $handler->getCreateForm();
        $response->addCommand(new InsertCommand('.gjs-am-add-asset', $form));
        return $response;
    }


}
