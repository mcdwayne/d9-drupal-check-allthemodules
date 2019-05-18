<?php
/**
 * @file
 * Contains
 */


function hierarchical_config_token_info() {

  $type = array(
    'name' => t('Configurations'),
    'description' => t('Tokens related to individual configuration entities.'),
  );

  $configuration = [];

  $bundles = \Drupal::entityManager()->getBundleInfo('hierarchical_configuration');

  foreach ($bundles as $bundle => $bundleInfo) {

    $fields = \Drupal::entityManager()
      ->getFieldDefinitions('hierarchical_configuration', $bundle);

    foreach ($fields as $field) {
      if ($field instanceof Drupal\field\Entity\FieldConfig) {

        $configuration[$field->get('field_name')] = array(
          'name' => $field->get('label'),
          'description' => $field->get('description'),
        );
      }
    }
  }

  return array(
    'types' => array('hierarchical_configuration' => $type),
    'tokens' => array('hierarchical_configuration' => $configuration),
  );
}


/**
 * Implements hook_tokens().
 */
function hierarchical_config_tokens($type, $tokens, array $data, array $options, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  $sanitize = !empty($options['sanitize']);
  $replacements = array();

  /** @var \Drupal\hierarchical_config\HierarchicalConfigurationService $hierarchicalConfigService */
  $hierarchicalConfigService = Drupal::service('hierarchical_config');

  if ($type == 'hierarchical_configuration') {
    foreach ($tokens as $name => $original) {
      $replacement = $hierarchicalConfigService->getSetting($name);
      $replacements[$original] = $sanitize ? \Drupal\Component\Utility\Xss::filter($replacement) : $replacement;
    }
  }
  return $replacements;
}
