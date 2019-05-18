<?php

namespace Drupal\dcat_landingpage\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\fixed_text_link_formatter\Plugin\Field\FieldFormatter\FixedTextLink;
use Drupal\Core\Url;

/**
 * Plugin implementation landingpage link.
 *
 * @FieldFormatter(
 *   id = "dcat_landingpage_landingpage",
 *   label = @Translation("Landingpage"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LandingpageFormatter extends FixedTextLink {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    if (count($items) === 0) {
      $settings = $this->getSettings();
      $dataset = $items->getParent()->getValue();

      $url = Url::fromRoute('view.dataset_landingpage.page', array('arg_0' => $dataset->id()));
      $link_title = $settings['link_text'];

      return [
        [
          '#type' => 'link',
          '#title' => $link_title,
          '#options' => $url->getOptions(),
          '#url' => $url,
        ]
      ];
    }

    return parent::viewElements($items, $langcode);
  }

}
