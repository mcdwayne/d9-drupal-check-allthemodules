<?php
namespace Drupal\pagedesigner\Plugin\pagedesigner\Asset;

use Drupal\pagedesigner\Plugin\AssetPluginBase;

/**
 * @PagedesignerAsset(
 *   id = "standard",
 *   name = @Translation("Default asset"),
 *   types = {
 *      "*",
 *   },
 * )
 */
class Standard extends AssetPluginBase
{

    public function get($filter = [])
    {
        return [];
    }
}
