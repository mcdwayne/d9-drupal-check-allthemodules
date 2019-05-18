<?php

/**
 * @file Token hook implementations for the Type Style module.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Config\Entity\ConfigEntityType;

/**
 * Implements hook_tokens().
 */
function type_style_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if (isset($data[$type]) && $data[$type] instanceof EntityInterface) {
    foreach ($tokens as $name => $original) {
      if (strpos($name, 'type-style-') === 0) {
        $style_name = str_replace('type-style-', '', $name);
        if ($style = type_style_get_style($data[$type], $style_name)) {
          $replacements[$original] = $style;
        }
      }
    }
  }

  return $replacements;
}

/**
 * Implements hook_token_info().
 */
function type_style_token_info() {
  $tokens = [];

  foreach (\Drupal::entityTypeManager()->getDefinitions() as $definition) {
    if ($definition instanceof ConfigEntityType && $type = $definition->getBundleOf()) {
      $label = $definition->getLabel();
      $tokens[$type]['type-style-color'] = [
        'name' => t('@label color', ['@label' => $label]),
        'description' => t('The color of the related type'),
      ];
      $tokens[$type]['type-style-icon'] = [
        'name' => t('@label icon', ['@label' => $label]),
        'description' => t('The icon of the related type'),
      ];
      $tokens[$type]['type-style-*'] = [
        'name' => t('@label custom style', ['@label' => $label]),
        'description' => t('A custom style, i.e. type-style-aura'),
      ];
    }
  }

  return [
    'tokens' => $tokens,
  ];
}
