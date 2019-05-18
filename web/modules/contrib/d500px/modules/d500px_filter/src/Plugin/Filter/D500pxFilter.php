<?php

namespace Drupal\d500px_filter\Plugin\Filter;

use Drupal\Component\Utility\Xss;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a filter to insert 500px photo.
 *
 * @Filter(
 *   id = "d500px_filter",
 *   title = @Translation("Embed 500px photo"),
 *   description = @Translation("Allow users to embed a picture from 500px website in an editable content area."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "d500px_filter_imagesize" = 200,
 *   },
 * )
 */
class D500pxFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $new_text = preg_replace_callback(
      '/\[d500px((?:\s).*)]/i',
      function ($matches) {
        $retval = '';
        if (isset($matches[1])) {
          $attrs = explode(' ', trim($matches[1]));
          $vars = [];
          foreach ($attrs as $attr) {
            list($name, $val) = explode('=', trim($attr), 2);
            $vars[Xss::filter($name)] = Xss::filter($val);
          }

          // Check if the photoid was set.
          if (!isset($vars['photoid'])) {
            return $retval;
          }

          $photoid = $vars['photoid'];
          $params = [
            'image_size'    => $this->getSize($vars),
          ];

          $d500pxphotos = \Drupal::service('d500px.D500pxPhotos');
          $content = $d500pxphotos->getPhotoById($photoid, $params);

          if (!is_array($content)) {
            return $retval;
          }

          $retval = render($content);
        }
        return $retval;
      },
      $text
    );

    return new FilterProcessResult($new_text);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // TODO
    // Refactor this to use standard sizes from D500pxHelpers::photoGetSizes.
    $form['d500px_filter_imagesize'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default imagesize of embed'),
      '#description' => $this->t('The default size of the embedded 500px photo (size ID) to use if not specified in the embed tag.'),
      '#default_value' => $this->settings['d500px_filter_imagesize'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t(
        'Embed 500px photo using @embed. Values for imagesize is optional, if left off the default values configured on the %filter input filter will be used',
        [
          '@embed' => '[d500px photoid=<photo_id> imagesize=<imagesize>]',
          '%filter' => 'Embed 500px photo',
        ]
      );
    }
    else {
      return $this->t('Embed 500px photo using @embed', ['@embed' => '[d500px photoid=<photo_id> imagesize=<imagesize>]']);
    }
  }

  /**
   * Returns the set imagesize or the default.
   *
   * @param array $vars
   *   An array of filter arguments.
   *
   * @return int
   *   The imagesize for the photo.
   */
  protected function getSize(array $vars) {
    if (isset($vars['imagesize']) && is_numeric($vars['imagesize'])) {
      return $vars['imagesize'];
    }

    return $this->settings['d500px_filter_imagesize'];
  }

}
