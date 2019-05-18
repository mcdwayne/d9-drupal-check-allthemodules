<?php

namespace Drupal\nodeify;

trait TokenInfoTrait {

  /**
   * Modified version of Drupal\views\PluginBase::getAvailableGlobalTokens()
   */
  protected function getTokenInfo($prepared = FALSE, array $types = []) {
    $info = \Drupal::token()->getInfo();
    // Site and view tokens should always be available.
    $types = array_merge(['site'], $types);
    $available = array_intersect_key($info['tokens'], array_flip($types));

    // Construct the token string for each token.
    if ($prepared) {
      $prepared = [];
      foreach ($available as $type => $tokens) {
        foreach (array_keys($tokens) as $token) {
          $prepared[$type][] = "[$type:$token]";
        }
      }

      return $prepared;
    }

    return $available;
  }

  /**
   * Modified version of Drupal\views\PluginBase::globalTokenForm()
   */
  protected function getTokenInfoList($types = ['node', 'user'], &$element = FALSE) {
    $token_items = [];

    foreach ($this->getTokenInfo(FALSE, $types) as $type => $tokens) {
      $item = [
        '#markup' => $type,
        'children' => [],
      ];
      foreach ($tokens as $name => $info) {
        $description = !empty($info['description']) ? ': ' . $info['description'] : '';
        $item['children'][$name] = "[$type:$name]" . ' - ' . $info['name'] . $description;
      }

      $token_items[$type] = $item;
    }

    $element['token_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Available token replacements'),
    ];
    $element['token_info']['list'] = [
      '#theme' => 'item_list',
      '#items' => $token_items,
      '#attributes' => [
        'class' => ['global-tokens'],
      ],
    ];
    return $element;
  }
}
