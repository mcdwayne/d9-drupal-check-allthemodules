<?php

namespace Drupal\beforeafter\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'beforeafter_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "beforeafter_field_formatter",
 *   label = @Translation("Before/After Field Formatter"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class BeforeAfterFieldFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * Constructs an ImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->imageStyleStorage = $image_style_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity.manager')->getStorage('image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      // Implement default settings.
      'beforeafter_style' => '',
      'beforeafter_prev_next' => FALSE,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t("Configure Image Styles"),
      Url::fromRoute('entity.image_style.collection')
    );
    $element['beforeafter_style'] = [
      '#title' => t("Image style"),
      '#type' => 'select',
      '#default_value' => $this->getSetting('beforeafter_style'),
      '#empty_option' => t("None (original image)"),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
        '#access' => $this->currentUser->hasPermission('administer image styles')
      ],
    ];
    $element['beforeafter_prev_next'] = [
      '#title' => $this->t("Enable Before & After button"),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('beforeafter_prev_next'),
      '#description' => $this->t('This will show the Before After button for image field formatter<br/> <b>Before After Will work only if it has 2 images in image field.</b>'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('beforeafter_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t("Image style: @style", array('@style' => $image_styles[$image_style_setting]));
    }
    else {
      $summary[] = t("Original image");
    }
    $image_prev_next = $this->getSetting('beforeafter_prev_next');
    if ($image_prev_next) {
      $summary[] .= t("Before & After : Enable");
    }
    if (!$image_prev_next) {
      $summary[] .= t("Before & After : Disable");
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    $image_style_setting = $this->getSetting('beforeafter_style');
    $image_style = NULL;
    if (!empty($image_style_setting)) {
      $image_style = \Drupal::entityTypeManager()->getStorage('image_style')->load($image_style_setting);
    }

    $image_uri_values = [];
    foreach ($items as $item) {
      if ($item->entity) {
        $image_uri = $item->entity->getFileUri();
        // Get image style URL
        if ($image_style) {
          $image_uri = ImageStyle::load($image_style->getName())->buildUrl($image_uri);
        } else {
          // Get absolute path for original image
          $image_uri = $item->entity->url();
        }
        $image_uri_values[] = $image_uri;
      }
    }

    // Enable Before After if only two images.
    $prev_next = $this->getSetting('beforeafter_prev_next');
    if (count($image_uri_values) <= 1 ){
      $prev_next = FALSE;
    }
  elseif (count($image_uri_values) >= 3 ){
    $prev_next = FALSE;
    } 
    $elements[] = array(
      '#theme' => 'beforeafter',
      '#url' => $image_uri_values,
      '#prev_next' => $prev_next,
    );

    // Attach the image field Before/After Field library.
    $elements['#attached']['library'][] = 'beforeafter/beforeafter';

    // Attach the drupal
    $drupalSettings = [
      'prev_next' => $prev_next,
    ];
    $elements['#attached']['drupalSettings']['beforeafter'] = $drupalSettings;

    // Not to cache this field formatter.
    $elements['#cache']['max-age'] = 0;

    return $elements;
  }

}
