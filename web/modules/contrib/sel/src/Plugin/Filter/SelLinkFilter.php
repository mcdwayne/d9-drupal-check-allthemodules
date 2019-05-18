<?php

namespace Drupal\sel\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to process external links.
 *
 * @Filter(
 *   id = "filter_sel",
 *   title = @Translation("Safe external link filter"),
 *   description = @Translation("Blank target and proper nofollow/noreferrer relation for external links"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {
 *     "rel_required" = "noreferrer",
 *     "rel_optionals" = {}
 *   }
 * )
 */
class SelLinkFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['rel_required'] = [
      '#type' => 'select',
      '#title' => $this->t('Required rel attribute value for external links'),
      '#description' => $this->t('One of these rel values are required for protecting the `window` object of this site'),
      '#options' => _sel_rel_defaults(),
      '#default_value' => $this->settings['rel_required'],
      '#description' => $this->t('Rel attribute values which have to be applied for discovered external links'),
      '#required' => TRUE,
    ];
    $form['rel_optionals'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Optional rel attributes for external links'),
      '#description' => $this->t('These rel values are optional. Some validators may report invalidity even if the attribute value is valid.'),
      '#default_value' => $this->settings['rel_optionals'],
      '#options' => _sel_rel_optionals(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (isset($configuration['settings']['rel_optionals'])) {
      $configuration['settings']['rel_optionals'] = array_filter($configuration['settings']['rel_optionals']);
    }

    parent::setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $html_dom = Html::load($text);
    $links = $html_dom->getElementsByTagName('a');
    $rel_values = $this->settings['rel_optionals'];
    array_unshift($rel_values, $this->settings['rel_required']);

    foreach ($links as $link) {
      $uri_string = $link->getAttribute('href');
      if (
        _sel_uri_is_external($uri_string)
      ) {
        $link_rel = $link->getAttribute('rel');

        foreach ($rel_values as $rel_value) {
          if (
            empty($link_rel) ||
            strpos($link_rel, $rel_value) === FALSE
          ) {
            $link_rel = empty($link_rel) ?
            $rel_value :
            $link_rel . ' ' . $rel_value;
          }
        }

        $link->setAttribute('target', '_blank');
        $link->setAttribute('rel', $link_rel);
      }
    }
    $text = Html::serialize($html_dom);

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Blank target and nofollow/noreferrer relation added for external links, automagically.');
  }

}
