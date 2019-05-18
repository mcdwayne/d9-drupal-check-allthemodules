<?php

namespace Drupal\flickr_filter\Plugin\Filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\flickr\Service\Helpers;
use Drupal\flickr\Service\Photosets;
use Drupal\flickr\Service\Photos;

/**
 * Provides a filter to insert Flickr photo.
 *
 * @Filter(
 *   id = "flickr_filter",
 *   title = @Translation("Embed Flickr photo"),
 *   description = @Translation("Allow users to embed a picture from Flickr website in an editable content area."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "flickr_filter_imagesize" = 200,
 *   },
 * )
 */
class FlickrFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Helpers.
   *
   * @var \Drupal\flickr\Service\Helpers
   */
  protected $helpers;

  /**
   * Photos.
   *
   * @var \Drupal\flickr\Service\Photos
   */
  protected $photos;

  /**
   * Photosets.
   *
   * @var \Drupal\flickr\Service\Photosets
   */
  protected $photosets;

  /**
   * FlickrFilter constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\flickr\Service\Helpers $helpers
   *   Helpers.
   * @param \Drupal\flickr\Service\Photos $photos
   *   Photos.
   * @param \Drupal\flickr\Service\Photosets $photosets
   *   Photosets.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              Helpers $helpers,
                              Photos $photos,
                              Photosets $photosets) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->helpers = $helpers;
    $this->photos = $photos;
    $this->photosets = $photosets;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('flickr.helpers'),
      $container->get('flickr.photos'),
      $container->get('flickr.photosets')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $text = preg_replace_callback('/\[flickr-photo:(.+?)\]/', 'self::callbackPhoto', $text);
    $text = preg_replace_callback('/\[flickr-photoset:(.+?)\]/', 'self::callbackPhotosets', $text);

    // TODO Implement the rest of the options.
    // @codingStandardsIgnoreStart
    // $text = preg_replace_callback('/\[flickr-group:(.+?)\]/', 'flickr_filter_callback_group', $text);
    // $text = preg_replace_callback('/\[flickr-gallery:(.+?)\]/', 'flickr_filter_callback_gallery', $text);
    // $text = preg_replace_callback('/\[flickr-user:(.+?)\]/', 'flickr_filter_callback_album', $text);
    // $text = preg_replace_callback('/\[flickr-favorites:(.+?)\]/', 'flickr_filter_callback_favorites', $text);.
    // @codingStandardsIgnoreEnd

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $sizes = $this->helpers->flickrApiHelpers->photoSizes();
    foreach ($sizes as $key => $size) {
      $options[$key] = $size['description']->render();
    }

    $form['flickr_filter_default_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Default size for single photos'),
      '#default_value' => $this->settings['flickr_filter_default_size'],
      '#options' => $options,
      '#description' => $this->t("A default Flickr size to use if no size is specified, for example [flickr-photo:id=3711935987].<br />TAKE CARE, the c size (800px) is missing on Flickr images uploaded before March 1, 2012!"),
    ];

    $form['flickr_filter_caption'] = [
      '#type' => 'select',
      '#title' => $this->t('Display captions for every Flickr photo'),
      '#required' => TRUE,
      '#default_value' => $this->settings['flickr_filter_caption'],
      '#description' => $this->t("If selected, flickr photos will display caption."),
      '#options' => [0 => 'No', 1 => 'Yes'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    // TODO Make this text a bit more informative.
    if ($long) {
      return $this->t(
        'Embed Flickr photos using @embed. Values for imagesize is optional, if left off the default values configured on the %filter input filter will be used',
        [
          '@embed' => '[flickr-photo:id=<photo_id>, size=<imagesize>]',
          '%filter' => 'Embed Flickr photo',
        ]
      );
    }
    else {
      return $this->t('Embed Flickr photo using @embed',
        [
          '@embed' => '[flickr-photo:id=<photo_id>, size=<imagesize>]',
        ]
      );
    }
  }

  /**
   * Filter callback for a photo.
   */
  private function callbackPhoto($matches) {
    list($config, $attribs) = $this->helpers->splitConfig($matches[1]);

    if (isset($config['id'])) {

      if ($photo = $this->photos->flickrApiPhotos->photosGetInfo($config['id'])) {
        if (!isset($config['size'])) {
          $config['size'] = $this->settings['flickr_filter_default_size'];
        }

        if (!isset($config['caption'])) {
          $config['caption'] = $this->settings['flickr_filter_caption'];
        }

        switch ($config['size']) {
          case "x":
          case "y":
            drupal_set_message(t("Do not use a slideshow for a single image."), 'error');
            $config['size'] = $this->settings['flickr_filter_default_size'];
            break;
        }

        $photoimg = $this->photos->themePhoto($photo, $config['size'], $config['caption']);

        return render($photoimg);
      }
    }

    return '';
  }

  /**
   * Filter callback for a user or set.
   */
  public function callbackPhotosets($matches) {
    list($config, $attribs) = $this->helpers->splitConfig($matches[1]);

    // Class.
    // TODO Implement this.
    if (!isset($attribs['class'])) {
      $attribs['class'] = NULL;
    }

    // Style.
    // TODO Implement this.
    if (!isset($attribs['style'])) {
      $attribs['style'] = NULL;
    }

    // Size.
    if (!isset($config['size'])) {
      $config['size'] = NULL;
    }

    // Photo count.
    if (!isset($config['num'])) {
      $config['num'] = NULL;
    }

    // Media.
    if (!isset($config['media'])) {
      $config['media'] = 'photos';
    }

    // Tags.
    if (!isset($config['tags'])) {
      $config['tags'] = '';
    }
    else {
      $config['tags'] = str_replace("/", ",", $config['tags']);
    }

    // Location options.
    if (!isset($config['location'])) {
      $config['location'][0] = NULL;
      $config['location'][1] = NULL;
      $config['location'][2] = NULL;
    }
    else {
      $config['location'] = explode("/", $config['location']);
      if (!isset($config['location'][2])) {
        $config['location'][2] = NULL;
      }
    }

    // Date options.
    if (!isset($config['date'])) {
      $config['date'][0] = NULL;
      $config['date'][1] = NULL;
    }
    else {
      $config['date'] = explode("|", $config['date']);
      if (!isset($config['date'][1])) {
        $config['date'][1] = NULL;
      }
    }

    // Sort options.
    if (!isset($config['sort'])) {
      $config['sort'] = 'unsorted';
    }

    switch ($config['sort']) {
      case 'taken':
        $config['sort'] = 'date-taken-desc';
        break;

      case 'posted':
        $config['sort'] = 'date-posted-desc';
        break;
    }

    // Show Caption.
    if (!isset($config['caption'])) {
      $config['caption'] = $this->settings['flickr_filter_caption'];
    }

    // Tag Mode.
    if (!isset($config['tag_mode'])) {
      $config['tag_mode'] = 'context';
    }

    // Mintitle?
    // TODO Implement this.
    if (!isset($config['mintitle'])) {
      $config['mintitle'] = NULL;
    }

    // Minmetsdata?
    // TODO Implement this.
    if (!isset($config['minmetadata'])) {
      $config['minmetadata'] = NULL;
    }

    // Filter
    // TODO Implement this.
    if (!isset($config['filter'])) {
      $config['filter'] = NULL;
    }

    switch ($config['filter']) {
      case 'interesting':
        $config['filter'] = 'interestingness-desc';
        break;

      case 'relevant':
        $config['filter'] = 'relevance';
        break;
    }

    // Showtime.
    $photosetPhotos = $this->photosets->flickrApiPhotosets->photosetsGetPhotos(
      $config['id'],
      [
        'per_page' => (int) $config['num'],
        'media' => 'photos',
      ],
      1
    );

    $photos = $this->photos->themePhotos($photosetPhotos['photo'], $config['size'], $config['caption'], $photosetPhotos['id']);
    $photoset = $this->photosets->themePhotoset($photos, $photosetPhotos['title']);

    return render($photoset);
  }

}
