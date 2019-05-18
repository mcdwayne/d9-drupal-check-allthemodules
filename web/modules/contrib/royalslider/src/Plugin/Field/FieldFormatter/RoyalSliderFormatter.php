<?php
/**
 * @file
 * Contains \Drupal\royalslider\Plugin\Field\FieldFormatter\RoyalSliderFormatter.
 */

namespace Drupal\royalslider\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'royalslider' formatter.
 *
 * @FieldFormatter(
 *   id = "royalslider",
 *   label = @Translation("RoyalSlider"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class RoyalSliderFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  /**
   * Constructs an RoyalSliderFormatter object.
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
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, LinkGeneratorInterface $link_generator) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->linkGenerator = $link_generator;
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
      $container->get('link_generator')
    );
  }

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'royalslider_optionset' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $royalslider_optionsets = royalslider_optionset_options(FALSE);
    $element['royalslider_optionset'] = array(
      '#title' => t('OptionSet'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('royalslider_optionset'),
      '#empty_option' => t('None (defaults)'),
      '#options' => $royalslider_optionsets,
      '#description' => array(
        '#markup' => $this->linkGenerator->generate($this->t('Configure RoyalSlider OptionSets'), new Url('entity.royalslider_optionset.collection')),
        '#access' => $this->currentUser->hasPermission('administer site configuration'),
      ),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $optionsets = royalslider_optionset_options(FALSE);
    // Unset possible 'No defined optionset' option.
    unset($optionsets['']);
    // Optionsets could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $optionset_setting = $this->getSetting('royalslider_optionset');
    if (isset($optionsets[$optionset_setting])) {
      $summary[] = t('OptionSet: @optionset', array('@optionset' => $optionsets[$optionset_setting]));
    }
    else {
      $summary[] = t('Default options');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $optionset_setting = $this->getSetting('royalslider_optionset');
    // @TODO load a default optionset if it is set to default.
    $optionset = entity_load('royalslider_optionset', $optionset_setting);

    $entity = $items->getEntity();
    $fieldname = $items->getName();
    $slider_id = 'royalslider-' . $entity->getEntityTypeId() . '-' .$entity->id() . '-' . $fieldname;

    $elements = array(
      '#theme' => 'royalslider_formatter',
      '#attributes' => array(
        'id' => $slider_id,
      ),
      '#items' => array(),
      '#optionset' => $optionset_setting,
      '#cache' => ['tags' => $optionset->getCacheTags()],
    );
    foreach ($items as $delta => $item) {
      $elements['#items'][] = [
        '#theme' => 'image_formatter',
        '#item' => $item,
      ];
    }

    $elements['#attached'] = [
      'library' => [
        'royalslider/royalslider',
        'royalslider/royalslider-rsdefault',
        ],
      'drupalSettings' => [
        'royalslider' => [
          'optionsets' => [$optionset->label() => $optionset->buildJsOptionset()],
          'instances' => [$slider_id => ['optionset' => $optionset->label()]],
          ],
        ]
    ];
    return $elements;
  }
}