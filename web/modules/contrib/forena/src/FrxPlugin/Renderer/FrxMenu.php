<?php


namespace Drupal\forena\FrxPlugin\Renderer;


/**
 * Class FrxMenu
 * @FrxRenderer(id = "FrxMenu")
 */
class FrxMenu extends RendererBase {

  public function render() {
    $output = '' ;
    $attributes = $this->mergedAttributes();
    if (!empty($attributes['menu-id'])) {
      $menu_id = $attributes['menu-id'];
      $output =  $this->app()->renderMenu($menu_id);
    }
    return $output;
  }

}