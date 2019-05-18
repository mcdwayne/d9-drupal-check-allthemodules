<?php

namespace Drupal\restricted_links_filter\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter that hide all links that a user does not have access to.
 *
 * @Filter(
 *   id = "restricted_links_filter",
 *   title = @Translation("Hides links that you don't have access to"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
 * )
 */
class RestrictedLinksFilter extends FilterBase implements ContainerFactoryPluginInterface {

  protected $SubstitutionTypeRemove = 1;
  protected $SubstitutionTypeReplase = 2;

  /**
   * {@inheritdoc}
   */
  /* public function __construct(array $configuration,
  $plugin_id, $plugin_definition) {
  parent::__construct($configuration, $plugin_id, $plugin_definition);
  } */

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['substitution_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Substitution type'),
      '#required' => TRUE,
      '#default_value' => ($this->settings['substitution_type']) ? $this->settings['substitution_type'] : $this->SubstitutionTypeRemove,
      '#options' => [
        $this->SubstitutionTypeRemove => t("Remove"),
        $this->SubstitutionTypeReplase => t("Replace with custom url"),
      ],
      '#description' => t("Indicates what the filter should do when it finds a url that you do not have access to"),
    ];

    $form['replace_url'] = [
      '#type' => 'textfield',
      '#title' => t("Replace with URL"),
      '#default_value' => ($this->settings['replace_url']) ? $this->settings['replace_url'] : NULL,
      '#states' => [
        'visible' => [
          ':input[name="filters[restricted_links_filter][settings][substitution_type]"]' => ['value' => $this->SubstitutionTypeReplase],
        ],
        'required' => [
          ':input[name="filters[restricted_links_filter][settings][substitution_type]"]' => ['value' => $this->SubstitutionTypeReplase],
        ],
      ],
    ];

    $form['add_classes'] = [
      '#type' => 'checkbox',
      '#title' => t("Add custom class(es) to link"),
      '#default_value' => ($this->settings['add_classes']) ? $this->settings['add_classes'] : FALSE,
      '#states' => [
        'invisible' => [
          ':input[name="filters[restricted_links_filter][settings][substitution_type]"]' => ['value' => $this->SubstitutionTypeRemove],
        ],
      ],
    ];

    $form['classes'] = [
      '#type' => 'textfield',
      '#title' => t("Class(es)"),
      '#description' => t("Separate every class with a space"),
      '#default_value' => ($this->settings['classes']) ? $this->settings['classes'] : NULL,
      '#states' => [
        'visible' => [
          ':input[name="filters[restricted_links_filter][settings][add_classes]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="filters[restricted_links_filter][settings][add_classes]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $findLinksExpression = '/<a[\s]+?(?:[^>]*?\s+)?href=["\'](?!mailto)(.*?)["\'](?:[^>]*?.*?)?\>([\S\s\w]*?)<\/a>/';
    $match = preg_match_all($findLinksExpression, $text, $matches, PREG_SET_ORDER, 0);
    $substitutionType = ($this->settings['substitution_type']) ? $this->settings['substitution_type'] : $this->SubstitutionTypeRemove;
    $host = \Drupal::request()->getSchemeAndHttpHost();

    if ($match != FALSE && is_array($matches) && count($matches) > 0) {
      foreach ($matches as $matchedTag) {
        $linkFullTag = $matchedTag[0];
        $linkUrl = str_replace($host, '', $matchedTag[1]);
        $linkContent = $matchedTag[2];

        try {
          $url = Url::fromUserInput($linkUrl);
          if (is_object($url) && $url->access() != TRUE) {
            switch ($substitutionType) {
              case $this->SubstitutionTypeRemove:
                $text = str_replace($linkFullTag, '', $text);
                break;

              case $this->SubstitutionTypeReplase:
                if ($substitutionType != $this->SubstitutionTypeRemove && isset($this->settings['add_classes']) && $this->settings['add_classes'] == TRUE) {
                  $linkFullTag = $this->addCustomClasses($linkFullTag, $this->settings['classes']);
                }

                $linkFullTagReplaced = str_replace($linkUrl, $this->settings['replace_url'], $linkFullTag);
                $text = str_replace($linkFullTag, $linkFullTagReplaced, $text);
                break;
            }
          }
        }
        catch (\InvalidArgumentException $e) {

        }
      }
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  private function addCustomClasses($linkFullTag = NULL, $classes = NULL) {
    $classes = trim($classes);
    if (strlen($classes) > 0) {
      $match = preg_match_all('/class=["\']{1}(.*?)["\']{1}/', $linkFullTag, $matches, PREG_SET_ORDER, 0);
      if ($match != FALSE && $matches != NULL) {
        $actualClasses = $matches[0][1];
        $linkFullTag = str_replace($actualClasses, $classes, $linkFullTag);
      }
      else {
        $linkFullTag = str_replace('<a ', '<a class="' . $classes . '" ', $linkFullTag);
      }
    }

    return $linkFullTag;
  }

}
