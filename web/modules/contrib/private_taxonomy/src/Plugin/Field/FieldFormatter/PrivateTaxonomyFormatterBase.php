<?php

namespace Drupal\private_taxonomy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for the taxonomy_term formatters.
 */
abstract class PrivateTaxonomyFormatterBase extends FormatterBase implements ContainerFactoryPluginInterface {

  protected $currentUser;

  /**
   * Constructs a StringFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The logged user.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountProxy $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   *
   * This preloads all taxonomy terms for multiple loaded objects at once and
   * unsets values for invalid terms that do not exist.
   */
  public function prepareView(array $entities_items) {
    $terms = [];
    $user = $this->currentUser;
    // Collect every possible term attached to any of the fieldable entities.
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemList $items */
    foreach ($entities_items as $items) {
      /* @var \Drupal\Core\Entity\ContentEntityBase $parent */
      $parent = $items->getEntity();
      $active_langcode = $parent->language()->getId();
      /* @var \Drupal\taxonomy\Entity\Term $term */
      foreach ($items->referencedEntities() as $term) {
        if ($term->hasTranslation($active_langcode)) {
          $translated_term = $term->getTranslation($active_langcode);
          if ($translated_term->access('view')) {
            $term = $translated_term;
          }
        }
        if (!$term->isNew()) {
          $terms[$term->id()] = $term;
        }
      }
    }
    if ($terms) {
      // Iterate through the fieldable entities again to attach the loaded term
      // data.
      foreach ($entities_items as $items) {
        $rekey = FALSE;

        foreach ($items as $item) {
          // Check whether the taxonomy term field value could be loaded.
          if (isset($terms[$item->target_id])) {
            if (!$user->hasPermission('view private taxonomies') &&
              $user->id() != private_taxonomy_term_get_user($item->target_id)) {

              // User does not have access to this term.
              $item->setValue(NULL);
              $rekey = TRUE;
            }
            else {
              // Replace the instance value with the term data.
              $item->entity = $terms[$item->target_id];
            }
          }
          // Terms to be created are not in $terms, but are still legitimate.
          elseif ($item->hasNewEntity()) {
            // Leave the item in place.
          }
          // Otherwise, unset the instance value, since the term does not exist.
          else {
            $item->setValue(NULL);
            $rekey = TRUE;
          }
        }

        // Rekey the items array if needed.
        if ($rekey) {
          $items->filterEmptyItems();
        }
      }
    }
  }

}
