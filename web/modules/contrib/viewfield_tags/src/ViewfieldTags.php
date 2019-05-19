<?php

namespace Drupal\viewfield_tags;

use Drupal\viewfield\Plugin\Field\FieldType\ViewfieldItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\views\Views;

/**
 * Class implementation to override the ViewfieldItem Class.
 */
class ViewfieldTags extends ViewfieldItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'allowed_tags' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['allowed_tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed View Tags'),
      '#default_value' => $this->getSetting('allowed_tags'),
      '#description' => $this->t('Enter a comma-separated list of tags for views available for content authors. Leave empty to allow all.'),
      '#autocomplete_route_name' => 'views_ui.autocomplete',
    ];

    return $form;
  }

  /**
   * Get an options array of views.
   *
   * @param bool $filter
   *   (optional) Flag to filter the output using the 'allowed_views' setting.
   *
   * @return array
   *   The array of options.
   */
  public function getViewOptions($filter = FALSE) {
    $views_options = [];
    foreach (Views::getEnabledViews() as $key => $view) {

      if ($view->get('tag') == $this->getSetting('allowed_tags')) {

        $views_options[$key] = FieldFilteredMarkup::create($view->get('label'));
      }
    }
    natcasesort($views_options);

    return $views_options;
  }

}
