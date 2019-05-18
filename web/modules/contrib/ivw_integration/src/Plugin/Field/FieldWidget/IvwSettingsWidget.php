<?php

namespace Drupal\ivw_integration\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ivw_integration\IvwLookupServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ivw_integration_settings' widget.
 *
 * @FieldWidget(
 *   id = "ivw_integration_widget",
 *   module = "ivw_integration",
 *   label = @Translation("IVW Settings"),
 *   field_types = {
 *     "ivw_integration_settings"
 *   }
 * )
 */
class IvwSettingsWidget extends WidgetBase implements ContainerFactoryPluginInterface {
  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The IVW lookup service.
   *
   * @var \Drupal\ivw_integration\IvwLookupServiceInterface
   */
  protected $lookupService;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    ConfigFactoryInterface $config_factory,
    IvwLookupServiceInterface $lookup_service
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->configFactory = $config_factory;
    $this->lookupService = $lookup_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('ivw_integration.lookup')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    $settings = $this->configFactory->get('ivw_integration.settings');

    if ($settings->get('offering_overridable')) {
      $element['offering'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Offering code'),
        '#default_value' => isset($items[$delta]->offering) ? $items[$delta]->offering : NULL,
        '#description' => $this->t('A single ivw site can have multiple offerings, they can be differentiated by different numbers.'),
        '#required' => FALSE,
        '#empty_option' => $this->t('Parent value'),
        '#min' => 1,
      ];
    }

    if ($settings->get('language_overridable')) {
      $element['language'] = [
        '#type' => 'select',
        '#options' => [
          1 => $this->t('German'),
          2 => $this->t('Other language, content is verifiable'),
          3 => $this->t('Other language, content is not verifiable'),
        ],
        '#title' => $this->t('Language'),
        '#required' => FALSE,
        '#empty_option' => $this->t('Parent value'),
        '#default_value' => isset($items[$delta]->language) ? $items[$delta]->language : NULL,
      ];
    }
    if ($settings->get('frabo_overridable')) {
      $element['frabo'] = [
        '#type' => 'select',
        '#options' => [
          'in' => $this->t('in: Deliver questionaire (preferred implementation)'),
          'i2' => $this->t('i2: Alternative implementation, use this if in does not work'),
          'ke' => $this->t('ke: Do not deliver questionaire'),
        ],
        '#title' => $this->t('Frabo control'),
        '#empty_option' => $this->t('Parent value'),
        '#default_value' => isset($items[$delta]->frabo) ? $items[$delta]->frabo : NULL,
      ];
    }

    if ($settings->get('frabo_mobile_overridable')) {
      $element['frabo_mobile'] = [
        '#type' => 'select',
        '#options' => [
          'mo' => $this->t('mo: Mobile delivery of questionaire'),
          'ke' => $this->t('ke: Do not deliver questionaire'),
        ],
        '#title' => $this->t('Frabo mobile control'),
        '#empty_option' => $this->t('Parent value'),
        '#default_value' => isset($items[$delta]->frabo_mobile) ? $items[$delta]->frabo_mobile : NULL,
      ];
    }

    if ($settings->get('format_overridable')) {
      $element['format'] = [
        '#type' => 'select',
        '#options' => [
          1 => $this->t('Image/Text'),
          2 => $this->t('Audio'),
          3 => $this->t('Video'),
          4 => $this->t('Other dynamic format'),
        ],
        '#title' => $this->t('Format'),
        '#required' => FALSE,
        '#empty_option' => $this->t('Parent value'),
        '#default_value' => isset($items[$delta]->format) ? $items[$delta]->format : NULL,
      ];
    }

    if ($settings->get('creator_overridable')) {
      $element['creator'] = [
        '#type' => 'select',
        '#options' => [
          1 => $this->t('Editors'),
          2 => $this->t('User'),
          3 => $this->t('Unknown'),
        ],
        '#title' => $this->t('Creator'),
        '#required' => FALSE,
        '#empty_option' => $this->t('Parent value'),
        '#default_value' => isset($items[$delta]->creator) ? $items[$delta]->creator : NULL,
      ];
    }

    if ($settings->get('homepage_overridable')) {
      $element['homepage'] = [
        '#type' => 'select',
        '#options' => [
          1 => $this->t('Homepage of the site'),
          2 => $this->t('No Homepage'),
          3 => $this->t('Hompage of foreign site'),
        ],
        '#title' => $this->t('Homepage flag'),
        '#required' => FALSE,
        '#empty_option' => $this->t('Parent value'),
        '#default_value' => isset($items[$delta]->homepage) ? $items[$delta]->homepage : NULL,
      ];
    }

    if ($settings->get('delivery_overridable')) {
      $element['delivery'] = [
        '#type' => 'select',
        '#options' => [
          1 => $this->t('Online'),
          2 => $this->t('Mobile'),
          3 => $this->t('Connected TV'),
        ],
        '#title' => $this->t('Delivery'),
        '#required' => FALSE,
        '#empty_option' => $this->t('Parent value'),
        '#default_value' => isset($items[$delta]->delivery) ? $items[$delta]->delivery : NULL,
      ];
    }

    if ($settings->get('app_overridable')) {
      $element['app'] = [
        '#type' => 'select',
        '#options' => [
          1 => $this->t('App'),
          2 => $this->t('No App'),
        ],
        '#title' => $this->t('Fallback app flag'),
        '#required' => FALSE,
        '#empty_option' => $this->t('Parent value'),
        '#default_value' => isset($items[$delta]->app) ? $items[$delta]->app : NULL,
      ];
    }

    if ($settings->get('paid_overridable')) {
      $element['paid'] = [
        '#type' => 'select',
        '#options' => [
          1 => $this->t('Paid'),
          2 => $this->t('Not assigned'),
        ],
        '#title' => $this->t('Paid flag'),
        '#required' => FALSE,
        '#empty_option' => $this->t('Parent value'),
        '#default_value' => isset($items[$delta]->paid) ? $items[$delta]->paid : NULL,
      ];
    }

    if ($settings->get('content_overridable')) {
      $name = 'content';
      $options = [
        '01' => $this->t('News'),
        '02' => $this->t('Sport'),
        '03' => $this->t('Entertainment/Boulevard/Stars/Film/Music'),
        '04' => $this->t('Fashion/Beauty'),
        '05' => $this->t('Family/Children/Counseling'),
        '06' => $this->t('Life/Psychology/Relationships'),
        '07' => $this->t('Cars/Traffic/Mobility'),
        '08' => $this->t('Travel/Tourism'),
        '09' => $this->t('Computer'),
        '10' => $this->t('Consumer Electronics'),
        '11' => $this->t('Telecommunication/Internet services'),
        '12' => $this->t('Games'),
        '13' => $this->t('Living/Real estate/Garden/Home'),
        '14' => $this->t('Economy/Finance/Job/Career'),
        '15' => $this->t('Health'),
        '16' => $this->t('Food/Beverages'),
        '17' => $this->t('Art/Culture/Litarature'),
        '18' => $this->t('Erotic'),
        '19' => $this->t('Science/Education/Nature/Environment'),
        '20' => $this->t('Information about the offer'),
        '21' => $this->t('Miscellaneous'),
        '22' => $this->t('Remaining topics'),
        '23' => $this->t('Games sitemap'),
        '24' => $this->t('Casual Games'),
        '25' => $this->t('Core Games'),
        '26' => $this->t('Remaining topics (Games)'),
        '27' => $this->t('Social Networking - Private'),
        '28' => $this->t('Social Networking - Business'),
        '29' => $this->t('Dating'),
        '30' => $this->t('Newsletter'),
        '31' => $this->t('E-Mail/SMS/E-Cards'),
        '32' => $this->t('Messenger/Chat'),
        '33' => $this->t('Remaining topics (Networking/Communikation'),
        '34' => $this->t('Searchengine'),
        '35' => $this->t('Directories/Information service'),
        '36' => $this->t('Remaining topics (Searchengine/Directories)'),
        '37' => $this->t('Onlineshops/Shopping Mall/Auctions/B2b Marketplace'),
        '38' => $this->t('Real estate classifieds'),
        '39' => $this->t('Jobs classifieds'),
        '40' => $this->t('Cars classifieds'),
        '41' => $this->t('Miscellaneous classifieds'),
        '42' => $this->t('Miscellaneous (E-Commerce)'),
      ];
      $element[$name] = [
        '#type' => 'select',
        '#group' => 'ivw_integration_settings_override',
        '#options' => $options,
        '#title' => $this->t('Content category'),
        '#required' => FALSE,
        '#empty_option' => $this->t('Parent value (:value)', [':value' => $options[$this->getParentSetting($name)]]),
        '#default_value' => isset($items[$delta]->$name) ? $items[$delta]->$name : NULL,
      ];
    }

    return $element;
  }

  /**
   * Get parent setting.
   *
   * @param string $name
   *   The name of the IVW property to look up.
   *
   * @return string
   *   The property value.
   */
  private function getParentSetting($name) {
    return $this->lookupService->byCurrentRoute($name, TRUE);
  }

}
