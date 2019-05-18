<?php

namespace Drupal\graphql_entity_definitions\Plugin\GraphQL\Fields\EntityDefinition\Fields;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * @GraphQLField(
 *   id = "entity_definition_field_setting",
 *   secure = true,
 *   name = "settings",
 *   type = "[KeyVal]",
 *   parents = {"EntityDefinitionField"}
 * )
 */
class Settings extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    $field_id = $value->getName();
    $settings = $value->getSettings();

    $form_display = $context->getContext('entity_form_display', $info);
    if ($form_display) {
      $content = $form_display->get('content');
      if (isset($content[$field_id])) {
        $form_settings = $content[$field_id]['settings'];
        $settings['form_settings'] = $form_settings;
      }
    }

    foreach ($settings as $key => $val) {
      yield [
        'key' => $key,
        'value' => $val,
      ];
    }
  }

}
