<?php

namespace Drupal\fullscreen_gallery\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for Fullscreen gallery routes.
 */
class FullscreenGalleryController extends ControllerBase {

  /**
   * The Request holder.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The Theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Drupal renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The entity type which contains the Fullscreen gallery page.
   *
   * @var string|null
   */
  protected $entityType = NULL;

  /**
   * The id of entity which contains the Fullscreen gallery page.
   *
   * @var int|null
   */
  protected $entityId = NULL;

  /**
   * The name of the field with Fullscreen gallery formatter.
   *
   * @var string|null
   */
  protected $fieldName = NULL;

  /**
   * The delta of current image in Fullscreen gallery page.
   *
   * @var int|null
   */
  protected $imageDelta = NULL;

  /**
   * The entity that contains the Fullscreen gallery.
   *
   * @var \Drupal\Core\Entity\FieldableEntityInterface
   */
  protected $entity;

  /**
   * The destination for Fullscreen gallery back links.
   *
   * @var string|null
   */
  protected $destination = NULL;

  /**
   * The delta of image which invoked the Fullscreen gallery view.
   *
   * This ID is needed for generating the correct back link url.
   *
   * @var string|null
   */
  protected $clickedImageId = NULL;

  /**
   * The array containing render variables for Fullscreen gallery theme.
   *
   * @var array|null
   */
  protected $render = NULL;

  /**
   * The array containing the Fullscreen gallery display settings.
   *
   * @var array|null
   */
  protected $displaySettings = NULL;

  /**
   * The array containing the Fullscreen gallery field images.
   *
   * @var array|null
   */
  protected $images = NULL;

  /**
   * Constructs the Fullscreen gallery controller.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme handler.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   */
  public function __construct(RequestStack $request_stack, ThemeManagerInterface $theme_manager, Renderer $renderer) {
    $this->requestStack = $request_stack;
    $this->themeManager = $theme_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('theme.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Check arguments for exploits.
   *
   * @param string $entity_type
   *   Drupal entity type.
   * @param int $entity_id
   *   Drupal entity id.
   * @param string $field_name
   *   The image fields name.
   * @param int $image_delta
   *   The delta of image to show.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the parameters are invalid.
   */
  protected function checkArguments($entity_type, $entity_id, $field_name, $image_delta) {
    try {
      // Try to set and validate Fullscreen gallery variables.
      $this->entity = $this->entityTypeManager()->getStorage($entity_type)->load($entity_id);
      if (empty($this->entity) or !$this->entity->hasField($field_name) or !$this->entity->$field_name->access('view')) {
        // Show a standard "access denied" page if given entity doesn't exists
        // or the actual user doesn't have view access on given field.
        throw new AccessDeniedHttpException();
      }
      // Set Fullscreen gallery variables if given arguments are valid and
      // accessible.
      $this->entityType = $entity_type;
      $this->entityId = $entity_id;
      $this->fieldName = $field_name;
      $this->imageDelta = $image_delta;
      $this->destination = $this->requestStack->getCurrentRequest()->query->get('destination');
      $this->clickedImageId = $this->requestStack->getCurrentRequest()->query->get('clicked_image_id');
      $this->render = ['#theme' => 'fullscreen_gallery'];
      // Set and store Fullscreen gallery display settings.
      $default_settings = $this->config('fullscreen_gallery.settings')->get();
      $formatter_settings = $this->entityTypeManager->getStorage('entity_view_display')->load($this->entityType . '.' . $this->entity->bundle() . '.' . 'default')->getRenderer($this->fieldName)->getSettings();
      $this->displaySettings = $this->getDisplaySettings($default_settings, $formatter_settings);
    }
    catch (\Exception $e) {
      // Show a standard "Page not found" if given entity type doesn't exists.
      throw new NotFoundHttpException();
    }
  }

  /**
   * Returns the calculated display settings for gallery.
   *
   * @param array $default
   *   The default settings for Fullscreen gallery.
   * @param array $formatter
   *   The formatter settings for Fullscreen gallery field.
   *
   * @return array
   *   The display settings for gallery.
   */
  private function getDisplaySettings(array $default, array $formatter) {
    if (isset($formatter['use_default']) and $formatter['use_default'] > 0) {
      return [
        'disabled_titles' => $default['fullscreen_gallery_disable_titles'],
        'right_sidebar_width' => $default['fullscreen_gallery_rs_width'],
        'right_sidebar_wtype' => $default['fullscreen_gallery_rs_width_type'],
      ];
    }
    else {
      return [
        'disabled_titles' => $formatter['disable_titles'],
        'right_sidebar_width' => $formatter['right_sidebar_width'],
        'right_sidebar_wtype' => $formatter['right_sidebar_width_type'],
      ];
    }
  }

  /**
   * Generates and returns needed gallery page variables for rendering.
   *
   * This callback is mapped to the path:
   * 'fullscreen_gallery/{entity_type}/{entity_id}/{field_name}/{image_delta}'
   *
   * @param string $entity_type
   *   Drupal entity type.
   * @param int $entity_id
   *   Drupal entity id.
   * @param string $field_name
   *   The image fields name.
   * @param int $image_delta
   *   The number of image to show.
   *
   * @return array
   *   The renderable array of fullscreen gallery page.
   */
  public function galleryView($entity_type, $entity_id, $field_name, $image_delta) {
    // Make sure to don't trust the URL to be safe!
    $this->checkArguments($entity_type, $entity_id, $field_name, $image_delta);

    // Set back link url from gallery.
    $back_link_url = !empty($this->destination) ? $this->destination : $this->entity->toUrl()->toString();
    $back_link_url_parts = UrlHelper::parse($back_link_url);
    $options = [
      'query' => $back_link_url_parts['query'],
      'fragment' => $this->clickedImageId,
      'attributes' => ['class' => ['back-link']],
      'absolute' => TRUE,
    ];
    $this->render['#back_link'] = Link::fromTextAndUrl($this->t('Back'), Url::fromUri('internal:' . $back_link_url_parts['path'], $options))->toString();

    // Store Fullscreen gallery field images.
    $this->images = $this->getGalleryImages();

    // If no images found redirect user to entity page (defined in destination)
    // or redirect to homepage if entity doesn't exists.
    if (!is_array($this->images) or !count($this->images)) {
      drupal_set_message($this->t('The requested image could not be found.'), 'error');
      return $this->redirect('<front>');
    }

    // If wrong image_delta given, but the gallery exists, modify delta to show
    // first image.
    $this->imageDelta = !isset($this->images[$this->imageDelta]) ? 0 : $this->imageDelta;

    // Set needed jquery and css files to load on fullscreen gallery page.
    $this->render['#attached']['library'][] = 'fullscreen_gallery/fullscreen-gallery';
    $this->render['#attached']['drupalSettings']['fullscreen_gallery'] = $this->getJquerySettings();

    // Set gallery thumbnail images.
    $gallery_link = 'fullscreen_gallery/' . $this->entityType . '/' . $this->entityId . '/' . $this->fieldName;
    $this->render['#thumbnails'] = $this->getThumbnails($this->images, $gallery_link);

    // Set Image title.
    $this->render['#image_title'] = $this->getImageTitle($this->images);

    // Render current image.
    $this->render['#image'] = $this->getStyledImage($this->images[$this->imageDelta]['uri'], 'fullscreen_gallery_xs', '');

    // Set gallery navigation buttons.
    $this->render['#prev'] = $this->imageDelta ? $this->createNavButton($this->t('Previous'), $this->imageDelta - 1) : '';
    $this->render['#next'] = isset($this->images[$this->imageDelta + 1]) ? $this->createNavButton($this->t('Next'), $this->imageDelta + 1) : '';

    // Set gallery image counter.
    $this->render['#counter'] = ($this->imageDelta + 1) . '/' . count($this->images);

    return $this->render;
  }

  /**
   * Returns Fullscreen gallery field images.
   *
   * @return array
   *   The array of field images data.
   */
  protected function getGalleryImages() {
    $images = [];
    $language = $this->languageManager()->getCurrentLanguage()->getId();
    $images_raw = $this->entity->getTranslation($language)->get($this->fieldName)->getValue();
    foreach ($images_raw as $key => $image) {
      $file = $this->entityTypeManager()->getStorage('file')->load($image['target_id']);
      $images[$key]['uri'] = $file->getFileUri();
      $images[$key]['image_data'] = $image;
    }
    return $images;
  }

  /**
   * Creates jquery settings object with defined image styles and current image.
   *
   * On the js side the current image is rendered based on these settings,
   * and the actual browser window size.
   *
   * @return array
   *   The array containing variables for fullscreen_gallery.js.
   */
  protected function getJquerySettings() {
    // Store main variables for fullscreen_gallery.js.
    $js_settings = ['current_style' => 'fullscreen_gallery_xs'];

    // Get defined image styles.
    $styles = ImageStyle::loadMultiple();
    foreach ($styles as $style) {
      if (strpos($style->getName(), 'fullscreen_gallery_') !== FALSE) {
        // Get the first defined effect.
        $effect_configurations = $style->getEffects()->getConfiguration();
        $effect = reset($effect_configurations);
        // Store the given style height attribute.
        $js_settings['styles'][$effect['uuid']] = $effect['data']['height'];
        // Store the styled image urls for given image and style.
        $js_settings['style_urls'][$effect['uuid']] = $style->buildUrl($this->images[$this->imageDelta]['uri']);
      }
    }

    // Get fullscreen gallery Right sidebar.
    $right_sidebar = $this->getGalleryRightSidebar();
    if (empty($this->displaySettings['right_sidebar_width']) or empty(render($right_sidebar))) {
      // If fullscreen gallery right sidebar region has no loaded content or the
      // sidebar width is not set add extra css file to hide the region.
      $this->render['#attached']['library'][] = 'fullscreen_gallery/fullscreen-gallery-no-sidebar';
    }
    else {
      // Otherwise set additional parameters to js for showing sidebar as it
      // is defined in settings page.
      $js_settings['rs_width'] = $this->displaySettings['right_sidebar_width'];
      $js_settings['rs_width_type'] = $this->displaySettings['right_sidebar_wtype'];
    }

    return $js_settings;
  }

  /**
   * Get fullscreen gallery right sidebar regions content.
   *
   * @return string
   *   The rendered right sidebar region contents.
   */
  protected function getGalleryRightSidebar() {
    $theme = $this->themeManager->getActiveTheme()->getName();
    $blocks = $this->entityTypeManager->getStorage('block')->loadByProperties(['theme' => $theme, 'region' => 'fullscreen_gallery_right']);
    uasort($blocks, 'Drupal\block\Entity\Block::sort');
    $build = [];
    foreach ($blocks as $key => $block) {
      if ($block->access('view')) {
        $render_controller = $this->entityTypeManager->getViewBuilder($block->getEntityTypeId());
        $build[$key] = $render_controller->view($block, 'block', NULL);
      }
    }
    // Set fullscreen gallery right sidebar regions content.
    $this->render['#fullscreen_gallery_right'] = render($build);

    return $this->render['#fullscreen_gallery_right'];
  }

  /**
   * Created an array with rendered fullscreen gallery thumbnails.
   *
   * @param array $images
   *   The image field items.
   * @param string $gallery_link
   *   The url for fullscreen gallery.
   *
   * @return array
   *   The array containing rendered gallery thumbnail links.
   */
  protected function getThumbnails(array $images, $gallery_link) {
    $thumbnails = [];
    foreach ($images as $delta => $photo) {
      $thumbnails[$delta]['link'] = $this->getStyledImage($photo['uri'], 'fullscreen_gallery_thumb', $gallery_link, $delta);
      $thumbnails[$delta]['class'] = $this->imageDelta == $delta ? 'active' : '';
    }
    return $thumbnails;
  }

  /**
   * Creates an styled image for fullscreen gallery.
   *
   * @param string $image_uri
   *   The uri for image.
   * @param string $style
   *   The image style to use for image rendering.
   * @param string $gallery_link
   *   The url for fullscreen gallery.
   * @param int $delta
   *   The delta of image to render.
   *
   * @return string|array
   *   The array containing rendered gallery thumbnail links.
   */
  protected function getStyledImage($image_uri, $style, $gallery_link, $delta = NULL) {
    $image = [
      '#theme' => 'image_style',
      '#style_name' => $style,
      '#uri' => $image_uri,
    ];

    $image_output = $this->renderer->render($image);
    if (!empty($gallery_link)) {
      $gallery_link_parameters = explode('/', $gallery_link);
      $parameters = [
        'query' => [
          'destination' => !empty($this->destination) ? $this->destination : '',
          'clicked_image_id' => $this->clickedImageId,
        ],
      ];
      $url = Url::fromRoute('fullscreen_gallery.page', [
        'entity_type' => $gallery_link_parameters[1],
        'entity_id' => $gallery_link_parameters[2],
        'field_name' => $gallery_link_parameters[3],
        'image_delta' => isset($delta) ? $delta : $this->imageDelta,
      ], $parameters);

      return Link::fromTextAndUrl($image_output, $url)->toString();
    }
    else {
      return $image_output;
    }
  }

  /**
   * Creates a button with given label to load image by given delta.
   *
   * @param string $button_text
   *   The label of button.
   * @param int $delta
   *   The delta of image to render.
   *
   * @return array
   *   The array containing rendered navigation button.
   */
  protected function createNavButton($button_text, $delta) {
    $url = Url::fromRoute('fullscreen_gallery.page',
      [
        'entity_type' => $this->entityType,
        'entity_id' => $this->entityId,
        'field_name' => $this->fieldName,
        'image_delta' => $delta,
      ],
      [
        'query' => [
          'destination' => !empty($this->destination) ? $this->destination : '',
          'clicked_image_id' => $this->clickedImageId,
        ],
      ]
    );
    return Link::fromTextAndUrl($button_text, $url)->toString();
  }

  /**
   * Returns the image title.
   *
   * Priority: Entity title, Image alt text, Image title.
   *
   * @param array $images
   *   The image field items.
   *
   * @return string
   *   The image title, or empty string if title is not set.
   */
  protected function getImageTitle(array $images) {
    // Get gallery settings to check whether title display is enabled.
    if (!empty($this->displaySettings['disabled_titles'])) {
      // Displaying image titles is disabled through admin interface.
      $image_title = '';
    }
    else {
      // Get entity title if exists.
      if (method_exists($this->entity, 'label')) {
        $entity_title = $this->entity->label();
      }

      // Set image title. Priority: Entity title, Image alt text, Image title.
      $image_title = isset($entity_title) ? $entity_title : '';
      if (!empty($images[$this->imageDelta]['image_data']['alt'])) {
        $image_title = $images[$this->imageDelta]['image_data']['alt'];
      }
      if (!empty($images[$this->imageDelta]['image_data']['title'])) {
        $image_title = $images[$this->imageDelta]['image_data']['title'];
      }
    }
    return $image_title;
  }

  /**
   * Returns the page title.
   *
   * This callback is mapped to the path:
   * 'fullscreen_gallery/{entity_type}/{entity_id}/{field_name}/{image_delta}'
   *
   * @param string $entity_type
   *   Drupal entity type.
   * @param int $entity_id
   *   Drupal entity id.
   * @param string $field_name
   *   The image fields name.
   * @param int $image_delta
   *   The number of image to show.
   *
   * @return string
   *   The fullscreen gallery page title.
   */
  public function getPageTitle($entity_type, $entity_id, $field_name, $image_delta) {
    $title = '';
    // Get entity title if exists.
    try {
      // Try to load given entity.
      $entity = $this->entityTypeManager()->getStorage($entity_type)->load($entity_id);
      if (method_exists($entity, 'label')) {
        $title = $entity->label();
      }
      // Get current image title. Priority: Entity title, Image alt text,
      // Image title.
      $images = $entity->get($field_name)->getValue();
      if (!empty($images[$image_delta]['alt'])) {
        $title = $images[$image_delta]['alt'];
      }
      if (!empty($images[$image_delta]['title'])) {
        $title = $images[$image_delta]['title'];
      }
    }
    catch (\Exception $e) {
      // Load the site name out of configuration and return it as Page title.
      $title = $this->config('system.site')->get('name');
    }
    return $title;
  }

}
