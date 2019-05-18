<?php

namespace Drupal\spin\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'spin_widget' widget.
 *
 * @FieldWidget(
 *   id = "spin_widget",
 *   module = "spin",
 *   label = @Translation("Spin Widget"),
 *   field_types = {
 *     "spin"
 *   }
 * )
 */
class SpinWidget extends WidgetBase implements ContainerFactoryPluginInterface {
  protected $fileMgr;
  protected $fileUsage;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ElementInfoManagerInterface $element_info, EntityTypeManagerInterface $entityMgr, FileUsageInterface $fileUsage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->elementInfo = $element_info;
    $this->fileMgr = $entityMgr->getStorage('file');
    $this->fileUsage = $fileUsage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('element_info'), $container->get('entity_type.manager'), $container->get('file.usage'));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'progress_indicator' => 'throbber',
      'file_directory'     => 'spin_img',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $file = !empty($items[$delta]->fid) ? $this->fileMgr->load($items[$delta]->fid) : NULL;

    if (!empty($element['#field_parents']) &&  in_array('default_value_input', $element['#field_parents'])) {
      return [];
    }
    $element['fid'] = [
      '#type'            => 'hidden',
      '#default_value'   => isset($items[$delta]->fid) ? $items[$delta]->fid : '',
      '#delta'           => $delta,
      '#element_validate' => [[$this, 'validate']],
    ];
    $element['spin'] = [
      '#title'         => $this->t('Spin URL'),
      '#type'          => 'textfield',
      '#default_value' => isset($items[$delta]->spin) ? $items[$delta]->spin : '',
      '#delta'         => $delta,
      '#maxlength'     => 120,
    ];
    if ($file) {
      $element['preview'] = [
        '#theme'      => 'image_style',
        '#style_name' => 'medium',
        '#uri'        => $file->getFileUri(),
      ];
    }
    $element['img'] = [
      '#title'           => $this->t('Thumbnail'),
      '#type'            => 'managed_file',
      '#default_value'   => isset($items[$delta]->fid) ? [$items[$delta]->fid] : [],
      '#delta'           => $delta,
      '#upload_location' => file_default_scheme() . '://' . $this->getFieldSetting('file_directory'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['progress_indicator'] = [
      '#title'         => $this->t('Progress indicator'),
      '#type'          => 'radios',
      '#description'   => $this->t('The throbber display does not show the status of uploads but takes up less space. The progress bar is helpful for monitoring progress on large uploads.'),
      '#access'        => file_progress_implementation(),
      '#default_value' => $this->getSetting('progress_indicator'),
      '#weight'        => 16,
      '#options'       => [
        'throbber' => $this->t('Throbber'),
        'bar'      => $this->t('Bar with progress meter'),
      ],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [$this->t('Progress indicator: @progress_indicator', ['@progress_indicator' => $this->getSetting('progress_indicator')])];
  }

  /**
   * Sync fid with img and manage file usage.
   */
  public function validate($element, FormStateInterface $form_state) {
    $delta = isset($element['#delta']) ? $element['#delta'] : -1;
    $spins = $form_state->getValue('field_spin');

    if (empty($spins[$delta]['img']['fids'][0]) && empty($spins[$delta]['fid'])) {
      return;
    }
    if (empty($spins[$delta]['img']['fids'][0])) {
      $fid = (int) $spins[$delta]['fid'];
      $file = $fid ? $this->fileMgr->load($fid) : FALSE;

      if (!empty($file)) {
        $this->fileUsage->delete($file, 'spin', 'file', $fid);
        $form_state->setValueForElement($element, '');
      }
    }
    else {
      $fid = (int) $spins[$delta]['img']['fids'][0];
      $file = $fid ? $this->fileMgr->load($fid) : FALSE;

      if (!empty($file)) {
        $this->fileUsage->add($file, 'spin', 'file', $fid);
        $form_state->setValueForElement($element, $fid);
      }
    }
  }

}
