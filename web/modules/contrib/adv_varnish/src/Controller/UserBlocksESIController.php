<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\Controller\UserBlocksESIController.
 */

namespace Drupal\adv_varnish\Controller;

use Drupal\adv_varnish\Response\ESIResponse;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;

class UserBlocksESIController extends ControllerBase {

  /**
   * Return rendered block html to replace esi tag.
   */
  public function content($block_id){
    $content = '';
    $js_data = [];
    $user_data = [];
    $response = new ESIResponse();

    // Call for plugins to retrieve user blocks data.
    $plugins = \Drupal::service('plugin.manager.user_block')->getDefinitions();
    foreach ($plugins as $plugin_id => $plugin) {
      $plugin_result = call_user_func([$plugin['class'], 'content']);
      if (is_array($plugin_result)) {
        $user_data = array_merge($user_data, $plugin_result);
      }
    }

    // Defaults for each SCRIPT element.
    $element_defaults = array(
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '',
    );
    $user_blocks = [];

    // Parse returned data.
    foreach ($user_data as $target => $data) {
      if (is_array($data)) {
        $js_data[$target] = $data;
      }
      elseif (is_string($data)) {
        $user_block = $element_defaults;
        $user_block['#value'] = $data;
        $user_block['#attributes'] = [
          'class' => ['avc-user-block'],
          'data-target' => $target,
        ];
        $user_blocks[] = $user_block;
      }
    }

    // Prepare user settings which will be merged with drupalSettings on page load.
    $embed_prefix = "\n<!--//--><![CDATA[//><!--\n";
    $embed_suffix = "\n//--><!]]>\n";

    // Defaults for each SCRIPT element.
    $element_defaults = array(
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#value' => '',
    );
    $avc_script = $element_defaults;
    $avc_script['#value_prefix'] = $embed_prefix;
    $avc_script['#value'] = 'var avcUserBlocksSettings = ' . Json::encode(NestedArray::mergeDeepArray($js_data)) . ";";
    $avc_script['#value_suffix'] = $embed_suffix;

    // Render user block data.
    $script = \Drupal::service('renderer')->renderPlain($avc_script);
    $html = \Drupal::service('renderer')->renderPlain($user_blocks);
    $content[] = $html;
    $content[] = $script;

    $content = implode(PHP_EOL, $content);
    $content = '<div id="avc-user-blocks" style="display:none;" time="' . time() . '">' . $content . '</div>';

    // Set rendered block as response object content.
    $response->setContent($content);

    return $response;
  }

}
