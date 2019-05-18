<?php

namespace Drupal\cocoon_media\Form;

use Drupal\cocoon_media\CocoonController;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;

/**
 * Class CMMAddMediaForm.
 *
 * @package Drupal\cocoon_media\Form
 */
class CMMAddMediaForm extends ConfigFormBase {
  /**
   * Default settings.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The cocoonController.
   *
   * @var \Drupal\cocoon_media\CocoonController
   */
  protected $cocoonController;

  /**
   * Duration setting of cache.
   *
   * @var float|int
   */
  protected $cacheDuration;

  /**
   * TODO replace with interface constants.
   *
   * @var array
   */
  protected $fileTypeImage = [
    'jpg',
    'jpeg',
    'png',
    'gif',
    'tiff',
    'bmp',
  ];

  /**
   * TODO replace with interface constants.
   *
   * @var array
   */
  protected $fileTypeVideo = [
    'mp4',
    'avi',
    'flv',
    'mov',
  ];

  /**
   * The bundle name to save images to.
   *
   * @var array|mixed|string|null
   */
  protected $mediaImageBundle = '';

  /**
   * The bundle name to save videos to.
   *
   * @var array|mixed|string|null
   */
  protected $mediaVideoBundle = '';

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->config = $this->config('cocoon_media.settings');
    $this->cocoonController = new CocoonController(
      $this->config->get('cocoon_media.domain'),
      $this->config->get('cocoon_media.username'),
      $this->config->get('cocoon_media.api_key')
    );
    $this->mediaImageBundle = $this->config->get('cocoon_media.media_image_bundle');
    $this->mediaVideoBundle = $this->config->get('cocoon_media.media_video_bundle');
    $this->cacheDuration = $this->config->get('cocoon_media.cache_duration') ?: 60 * 5;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cocoon_media_add_media_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    $form['cocoon_media_browser'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Cocoon Media Management Browse'),
      '#collapsible' => FALSE,
      '#tree' => TRUE,
    );
    // CMM Label.
    $form['cocoon_media_browser']['othertable'] = array(
      '#type' => 'tablegridselect',
    );
    // CMM Label.
    $form['cocoon_media_browser']['description'] = array(
      '#markup' => $this->t("Browse and add Cocoon Media to your library.") . '<br/>',
    );

    // Add the following form elements only if the module API is configured.
    if (!empty($this->config->get('cocoon_media.api_key'))
      && !empty($this->config->get('cocoon_media.domain'))
      && !empty($this->config->get('cocoon_media.username'))) {
      $form['cocoon_media_browser']['clear_cache'] = array(
        '#type' => 'button',
        '#value' => $this->t('Refresh library'),
        '#ajax' => array(
          'callback' => array($this, 'refreshLibrary'),
          'wrapper' => 'edit-cocoon-media-browser',
          'effect' => 'fade',
          'prevent' => 'onfocus',
          'keypress' => TRUE,
        ),
      );
      $sets = $this->cocoonController->getSets();
      $radio_sets = [];
      $total_count = 0;
      foreach ($sets as $set) {
        $radio_sets[$set['id']] = $set['title'] . ' (' . $set['file_count'] . ')';
        $total_count += $set['file_count'];
      }
      $radio_sets['all'] = 'All (' . $total_count . ')';
      $form['cocoon_media_browser']['sets'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Select a set'),
        '#default_value' => 'all',
        '#options' => $radio_sets,
        '#ajax' => array(
          'callback' => array($this, 'ajaxCallbackGetFilesBySet'),
          'wrapper' => 'cocoon-results',
          'effect' => 'fade',
        ),
      );

      $set = 'all';
      $tag_name = '';
      $current_page = 0;

      $values = $form_state->getValues();
      if (!empty($values)) {
        $set = $values['cocoon_media_browser']['sets'];
        $tag_name = $values['cocoon_media_browser']['tag_elements']['tagname'];
        $current_page = $values['cocoon_media_browser']['results']['pager_actions']['page'];
        if ($values['op'] == '>') {
          $current_page += 1;
        }
        if ($values['op'] == '<') {
          $current_page -= 1;
        }
      }
      $options = $this->buildOptionsElements($set);
      $options_chunk = array_chunk($options, $this->config->get('cocoon_media.paging_size', 15), TRUE);
      $total_pages = count($options_chunk);
      $current_page = $current_page < 0 ? 0 : $current_page;
      $current_page = $current_page >= $total_pages ? $total_pages - 1 : $current_page;
      $form['cocoon_media_browser']['tag_elements'] = array(
        '#prefix' => '<div class="container-inline">',
        '#suffix' => '</div>',
      );
      $form['cocoon_media_browser']['tag_elements']['tagname'] = array(
        '#type' => 'textfield',
        '#placeholder' => $this->t('Search by tag'),
        // TODO fix autocomplete, this now breaks when user has 500+ tags.
        // '#autocomplete_route_name' => 'cocoon_media.tag_autocomplete',.
        '#size' => '20',
        '#maxlength' => '60',
      );
      $form['cocoon_media_browser']['tag_elements']['tag_search'] = array(
        '#type' => 'button',
        '#value' => $this->t('Search'),
        '#ajax' => array(
          'callback' => array($this, 'ajaxCallbackGetFilesBySet'),
          'wrapper' => 'cocoon-results',
          'effect' => 'fade',
          'prevent' => 'onfocus',
          'keypress' => TRUE,
        ),
      );

      $form['cocoon_media_browser']['results'] = array(
        '#prefix' => '<div id="cocoon-results">',
        '#suffix' => '</div>',
      );

      $ajax_call = array(
        'callback' => array($this, 'ajaxCallbackGetFilesBySet'),
        'wrapper' => 'cocoon-results',
        'effect' => 'fade',
        'progress' => array(
          'message' => '',
        ),
      );

      $form['cocoon_media_browser']['results'] = array_merge($form['cocoon_media_browser']['results'], $this->buildAjaxPager($ajax_call, $current_page, $total_pages));
      $form['cocoon_media_browser']['results']['images_table'] = $this->buildTableSelect('images-table', $options_chunk[$current_page]);
    }
    else {
      // CMM Label.
      $url = Link::createFromRoute('here', 'cocoon_media.admin_settings');
      $form['cocoon_media_browser']['api_not_configured'] = array(
        '#markup' => $this->t("Please first add the configuration parameters here:"),
      );
      $form['cocoon_media_browser']['api_settings_link'] = $url->toRenderable();
    }
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download Media'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * TODO add function description.
   *
   * @param string $url
   *   TODO add url description.
   * @param string $local_url
   *   TODO add description.
   * @param bool $to_temp
   *   TODO add description.
   *
   * @return string
   *   TODO add description.
   */
  public function retrieveRemoteFile($url, $local_url = '', $to_temp = FALSE) {
    // Check the cache and download the file if needed.
    $parsed_url = parse_url($url);
    $cocoon_dir = 'cocoon_media_files';
    $cocoon_media_directory = 'public://' . $cocoon_dir . '/';
    file_prepare_directory($cocoon_media_directory, FILE_CREATE_DIRECTORY);
    if (empty($local_url)) {
      // $cocoon_media_directory = $to_temp ? 'temporary://' : 'public://';.
      // TODO replace deprecated drupal_basename.
      $local_url = $cocoon_media_directory . '/' . drupal_basename($parsed_url['path']);
    }
    return system_retrieve_file($url, $local_url, !$to_temp, FILE_EXISTS_REPLACE);
  }

  /**
   * TODO add function description.
   *
   * @param array $image_info
   *   TODO add description.
   * @param string $prefix
   *   TODO add description.
   *
   * @return string
   *   TODO add description.
   */
  public function remoteThumbToLocal(array $image_info, $prefix) {
    $filename = $prefix . $image_info['filename'] . '.' . $image_info['extension'];
    $local_path = 'public://cocoon_media_files/' . $filename;

    if (empty($filename)) {
      return '';
    }
    if (!file_exists($local_path)) {
      $thumb_info = $this->cocoonController->getThumbInfo($image_info['id']);
      if (empty($thumb_info['web'])) {
        return '';
      }
      if (!empty($thumb_info['web'])) {
        $this->retrieveRemoteFile($thumb_info['web'], $local_path);
      }
    }
    return $local_path;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $selected_images = $values['cocoon_media_browser']['results']['images_table'];
    $filenames = '';
    foreach ($selected_images as $selected_image_id) {
      if ($selected_image_id) {
        $file_info = $this->cocoonController->getThumbInfo($selected_image_id);
        if (!empty($file_info['faultstring'])) {
          $this->messenger()->addMessage($this->t("The File(s) cannot be added to the media library. Error message: %error", [
            '%error' => $file_info['faultstring'],
          ]), MessengerInterface::TYPE_ERROR);
          return;
        }
        $url = $file_info['path'];
        // Check the cache and download the file if needed.
        /** @var \Drupal\file\Entity\File $file */
        $file = $this->retrieveRemoteFile($url);
        if (empty($file)) {
          $this->messenger()->addMessage($this->t("The File(s) cannot be added to the media library."), 'error');
          return;
        }

        $media_bundle = 'file';
        $field_media_name = 'field_media_file';

        if (in_array($file_info['ext'], $this->fileTypeImage)) {
          // TODO replace with generic image bundle
          // or make configurable what the bundle is.
          $media_bundle = 'image';
          if ($this->mediaImageBundle) {
            $media_bundle = $this->mediaImageBundle;
          }
          $field_media_name = 'field_media_image';
        }

        if (in_array($file_info['ext'], $this->fileTypeVideo)) {
          // TODO replace with generic video bundle
          // or make configurable what the bundle is.
          $media_bundle = 'video';
          if ($this->mediaImageBundle) {
            $media_bundle = $this->mediaVideoBundle;
          }
          $field_media_name = 'field_media_video_file';
        }

        // Create media entity with saved file.
        // TODO use entityStorageManager.
        $media = Media::create([
          'bundle' => $media_bundle,
          'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
          'name' => $file_info['name'],
          $field_media_name => [
            'target_id' => $file->id(),
            'alt' => $file_info['name'],
            'title' => $file_info['name'],
          ],
        ]);

        // TODO check media bundle exist before calling media->save();
        // if not show error below:
        // drupal_set_message($this->t("Have you set the correct bundle
        // in the cocoon_media module"), 'error');.
        $media->save();

        // TODO use dependency injection.
        $media->setOwnerId(\Drupal::currentUser()->id());
        $filenames .= $file_info['name'] . ', ';
      }
    }
    // Redirecting to the media library page.
    $media_url = Url::fromRoute('entity.media.collection');
    $form_state->setRedirectUrl($media_url);
    $filenames = substr($filenames, 0, -2);
    // Adding custom message.
    $this->messenger()->addMessage($this->t('The File(s) <i>%filenames</i> has been added to the media library.', [
      '%filenames' => $filenames,
    ]));
  }

  /**
   * Search for images with given tag name.
   *
   * @param string $tag_name
   *   The name of the tag.
   *
   * @return array
   *   list of images.
   */
  public function getFilesByTag($tag_name) {
    $tags_images_list = [];
    $tags_list = NULL;
    $matches = [];
    $tags_list = get_cached_data('cocoon_media:all_tags', [
      $this->cocoonController,
      'getTags',
    ], [], $this->cacheDuration);

    // Do not search for tag name if the tag name is empty.
    if (!$tag_name) {
      $matches = array_column($tags_list, 'id');
    }

    if ($tag_name) {
      foreach ($tags_list as $tag) {
        $string_found = $tag_name ? strpos($tag['name'], $tag_name) : TRUE;
        if ($string_found !== FALSE) {
          $matches[] = $tag['id'];
        }
      }
    }

    foreach ($matches as $tag_id) {
      $tag_files = get_cached_data('cocoon_media:tag_' . $tag_id, [$this->cocoonController, 'getFilesByTag'], [$tag_id], $this->cacheDuration);
      $tags_images_list = array_merge($tags_images_list, $tag_files);
    }
    return $tags_images_list;
  }

  /**
   * Ajax callback.
   *
   * @param array $ajax_callback
   *   Cocoon media browser results.
   * @param int $current_page
   *   The current page.
   * @param int $total_pages
   *   Total number of pages.
   *
   * @return array
   *   Renderable array.
   */
  public function buildAjaxPager(array $ajax_callback, $current_page = 0, $total_pages = 0) {
    $form_ajax_pager['pager_actions'] = array(
      '#type' => 'actions',
      '#weight' => 0,
    );
    $form_ajax_pager['pager_actions']['prev'] = array(
      '#type' => 'button',
      '#value' => '<',
      '#ajax' => $ajax_callback,
    );
    $form_ajax_pager['pager_actions']['page'] = array(
      '#type' => 'hidden',
      '#value' => $current_page,
    );
    $form_ajax_pager['pager_actions']['pagenum'] = array(
      '#type' => 'button',
      '#value' => $current_page + 1 . ' of ' . $total_pages,
      '#disabled' => TRUE,
    );
    $form_ajax_pager['pager_actions']['next'] = array(
      '#type' => 'button',
      '#value' => '>',
      '#ajax' => $ajax_callback,
    );

    return $form_ajax_pager;
  }

  /**
   * Build single option element.
   *
   * @param array $image_info
   *   The array to render the media item from.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered HTML.
   */
  public function buildSingleOptionElement(array $image_info) {
    $thumb_url = '/' . drupal_get_path('module', 'cocoon_media')
      . '/images/generic.png';
    $thumb = $this->remoteThumbToLocal($image_info, 'thumb_');
    if (!empty($thumb)) {
      $thumb_url = file_create_url($thumb);
    }
    $elm = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
    ];
    $elm['id'] = [
      '#type' => 'hidden',
      '#value' => $image_info['id'],
    ];
    $elm['thumb'] = [
      '#type' => 'label',
      '#title_display' => 'before',
      '#title' => '&nbsp;',
      '#attributes' => [
        'class' => 'media-thumb',
        'style' => "background-image:url(" . $thumb_url . ")",
      ],
    ];
    $elm['title'] = [
      '#type' => 'label',
      '#title_display' => 'before',
      '#title' => $image_info['title'],
      '#attributes' => ['class' => 'media-title'],
    ];
    $elm['file_details'] = [
      '#markup' => '<p><b>Extension: </b>'
        . $image_info['extension']
        . '<br/><b>Size: </b>'
        . round($image_info['size'] / 1024, 2)
        . 'KB</p>',
    ];
    $rendered_item = \Drupal::service('renderer')->renderPlain($elm);
    return $rendered_item;
  }

  /**
   * Build an option list with media items to select.
   *
   * @param string $set_id
   *   The id of the set.
   *
   * @return array
   *   Options with rendered items.
   */
  public function buildOptionsElements($set_id) {

    $image_list = $this->getImagesBySetId($set_id);

    $options = [];
    foreach ($image_list as $idx => $image_info) {
      $rendered_item = get_cached_data('cocoon_media:option_item_' . $image_info['id'], [$this, 'buildSingleOptionElement'], [$image_info]);
      $options[$image_info['id']] = [
        'media_item' => $rendered_item,
      ];
    }
    return $options;
  }

  /**
   * Get Images by set id.
   *
   * @param mixed $set_id
   *   String 'all' or Int with set_id.
   *
   * @return array
   *   Images.
   */
  private function getImagesBySetId($set_id = 'all') {
    $images = [];

    if ($set_id !== 'all') {
      $results = get_cached_data('cocoon_media:set_' . $set_id, [$this->cocoonController, 'getFilesBySet'], [$set_id], $this->cacheDuration);
      if ($results) {
        $images = $results;
      }
    }

    if ($set_id == 'all') {
      foreach ($this->cocoonController->getSets() as $set) {
        $images = array_merge($images, get_cached_data('cocoon_media:set_' . $set['id'], [$this->cocoonController, 'getFilesBySet'], [$set['id']], $this->cacheDuration));
      }
    }

    return $images;
  }

  /**
   * Build table select list.
   *
   * @param string $hmtlAttributeId
   *   The html id given for this tableselect list.
   * @param array $options
   *   Table select options.
   *
   * @return array
   *   Renderable array.
   */
  public function buildTableSelect($hmtlAttributeId, array $options) {
    $header = [
      'media_item' => $this->t('Media File'),
    ];
    $table = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No media found'),
      '#multiple' => TRUE,
      '#attributes' => ['id' => $hmtlAttributeId],
      '#cache' => [
        // Cached for one day.
        'max-age' => 60 * 60 * 24,
      ],
      '#attached' => array(
        'library' => array('cocoon_media/tablegrid-select'),
      ),
    );
    return $table;
  }

  /**
   * Get cocoon media browser results.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *   The cocooon media browser form results.
   */
  public function ajaxCallbackGetFilesBySet(array &$form, FormStateInterface &$form_state) {
    return $form['cocoon_media_browser']['results'];
  }

  /**
   * Clear cache.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *   The cocooon media browser form.
   */
  public function refreshLibrary(array &$form, FormStateInterface &$form_state) {
    // TODO do we need to flush all caches?
    drupal_flush_all_caches();
    return $form['cocoon_media_browser'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cocoon_media.settings',
    ];
  }

}
