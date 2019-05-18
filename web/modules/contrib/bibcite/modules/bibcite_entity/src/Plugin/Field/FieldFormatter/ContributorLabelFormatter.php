<?php

namespace Drupal\bibcite_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity reference label' formatter.
 *
 * @FieldFormatter(
 *   id = "bibcite_contributor_label",
 *   label = @Translation("Label"),
 *   description = @Translation("Display the label of the contributors."),
 *   field_types = {
 *     "bibcite_contributor"
 *   }
 * )
 */
class ContributorLabelFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    $show_role = $this->getSetting('role');
    $show_category = $this->getSetting('category');

    /* @var \Drupal\bibcite_entity\ContributorPropertiesServiceInterface $contributorPropertiesService */
    $contributorPropertiesService = \Drupal::service('bibcite_entity.contributor_properties_service');

    $roles = $contributorPropertiesService->getRoles();
    $categories = $contributorPropertiesService->getCategories();

    if (($show_role && !empty($roles)) || ($show_category  && !empty($categories))) {

      $default_role_value = $contributorPropertiesService->getDefaultRole();
      $default_role = isset($default_role_value) ? $roles[$default_role_value] : NULL;
      $default_category_value = $contributorPropertiesService->getDefaultCategory();
      $default_category = isset($default_category_value) ? $categories[$default_category_value] : NULL;

      foreach ($items as $delta => $item) {
        $add = '';
        if ($show_role && !empty($roles)) {
          $role_value = $item->get('role')->getValue();
          if (isset($role_value, $roles[$role_value])) {
            $role = $roles[$role_value];
          }
          else {
            $role = $default_role;
          }
          if ($role) {
            $add .= ", {$role}";
          }
        }
        if ($show_category && !empty($categories)) {
          $category_value = $item->get('category')->getValue();
          if (isset($category_value, $categories[$category_value])) {
            $category = $categories[$category_value];
          }
          else {
            $category = $default_category;
          }
          if ($category) {
            $add .= ", {$category}";
          }
        }
        if (isset($elements[$delta]['#type']) && $elements[$delta]['#type'] == 'link') {
          $elements[$delta]['#title'] .= $add;
        }
        else {
          $elements[$delta]['#plain_text'] .= $add;
        }
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'role' => FALSE,
      'category' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['role'] = [
      '#title' => t('Show role'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('role'),
    ];
    $element['category'] = [
      '#title' => t('Show category'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('category'),
    ];

    return $element;
  }

}
