<?php

namespace Drupal\webpurify\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\webpurify\Plugin\Filter\FilterWebpurifyProfanity;

/**
 * Validates the SafeSearch constraint.
 */
class WebPurifyConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The WebPurify API.
   *
   * @var \Drupal\webpurify\WebPurifyAPI.
   */
  protected $webPurifyAPI;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface.
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function validate($data, Constraint $constraint) {
    $field_value = $data->getValue();
    if (!empty($field_value) && is_array($field_value)) {
      $fields_value_info = reset($field_value);
      if (!empty($fields_value_info['format'])) {
        $format = FilterFormat::load($fields_value_info['format']);
        $webpurify_filter_instance = $format->filters('filter_webpurify_profanity');
        if ($webpurify_filter_instance instanceof FilterWebpurifyProfanity
          && $webpurify_filter_instance->settings['webpurify_mode'] == WEBPURIFY_VALIDATION_MODE
        ) {
          $webpurify_api = \Drupal::service('webpurify.api');
          $count = (bool) $webpurify_api->count($field_value[0]['value']);
          if ($count) {
            $this->context->addViolation($webpurify_filter_instance->settings['webpurify_validation_message']);
          }
        }
      }
      else {
        $field_definition = $data->getFieldDefinition();
        $field_id = $field_definition->id();
        if (!empty($field_id)) {
          $webpurify_filter_info = webpurify_field_config_get($field_id);
          if (isset($webpurify_filter_info['data']['status'])
            && $webpurify_filter_info['data']['status']
            && !empty($webpurify_filter_info['data']['mode'])
            && $webpurify_filter_info['data']['mode'] == WEBPURIFY_VALIDATION_MODE
          ) {
            $this->context->addViolation($webpurify_filter_info['data']['validation_message']);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('webpurify.api'),
      $container->get('file_system')
    );
  }
}
