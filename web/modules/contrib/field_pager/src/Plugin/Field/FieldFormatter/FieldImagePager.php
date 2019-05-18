<?php

namespace Drupal\image\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\field_pager\PagerHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Plugin implementation of the 'image_pager' formatter.
 *
 * @FieldFormatter(
 *   id = "image_pager",
 *   label = @Translation("Image (Pager)"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class FieldImagePager extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    return PagerHelper::mergeDefaultSettings($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    return PagerHelper::mergeSettingsForm($form, $form_state, $this, $elements);

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    return PagerHelper::mergeSettingsSummary($summary, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $fields = parent::view($items, $langcode);
    return PagerHelper::mergeView(count($items), $this, $fields);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->urlInfo();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }

    $index_name = $this->getSetting('index_name');
    $delta = (int) (isset($_GET[$index_name]) ? $_GET[$index_name] : 0);
    if (empty($files[$delta])) {
      throw new NotFoundHttpException();
    }
    $file = $files[$delta];

    $cache_contexts = [];
    if (isset($link_file)) {
      $image_uri = $file->getFileUri();
      // @todo Wrap in file_url_transform_relative(). This is currently
      // impossible. As a work-around, we currently add the 'url.site' cache
      // context to ensure different file URLs are generated for different
      // sites in a multisite setup, including HTTP and HTTPS versions of the
      // same site. Fix in https://www.drupal.org/node/2646744.
      $url = Url::fromUri(file_create_url($image_uri));
      $cache_contexts[] = 'url.site';
    }
    $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());

    // Extract field item attributes for the theme function, and unset them
    // from the $item so that the field template does not re-render them.
    $item = $file->_referringItem;
    $item_attributes = $item->_attributes;
    unset($item->_attributes);

    $elements[$delta] = [
      '#theme' => 'image_formatter',
      '#item' => $item,
      '#item_attributes' => $item_attributes,
      '#image_style' => $image_style_setting,
      '#url' => $url,
      '#cache' => [
        'tags' => $cache_tags,
        'contexts' => $cache_contexts,
      ],
    ];

    return $elements;
  }

}
