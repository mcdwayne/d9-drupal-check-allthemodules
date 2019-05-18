<?php
/**
 * @file
 * Contains \Drupal\hijri\Form\SettingsController.
 */
namespace Drupal\hijri\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;


class SettingsController extends ConfigFormBase {
    /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hijri_admin_settings';
  }
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hijri.config'];
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
      );
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  $system_date = $this->config('hijri.config');
  $correction = $system_date->get('correction_value');


  $hijri_types = array(
    'full' => t('Hijri full format: @date', array('@date' =>  t('@hijri on @gregorian', array('@hijri' => hijri('l j F  Y', time(), $correction), '@gregorian' => format_date(time(), 'custom', 'F j, Y'))))),
    'long' => t('Hijri long format: @date', array('@date' => hijri_format_date(time(), 'long', NULL, $correction))),
    'medium' => t('Hijri medium format: @date', array('@date' => hijri_format_date(time(), 'medium', NULL, $correction))),
    'short' => t('Hijri short format: @date', array('@date' => hijri_format_date(time(), 'short', NULL, $correction))),
  );
  // content types settings.
  $form['hijri_settings'] = array(
    '#type' => 'vertical_tabs',
  );
  $form['hijri_settings']['correction'] = array(
    '#type' => 'details',
    '#title' => t('Hijri Correction'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'hijri_settings',
    '#weight' => 0,
  );
  $form['hijri_settings']['correction']['hijri_correction_value'] = array(
    '#type' => 'select',
    '#title' => t('Correction days'),
    '#options' => array(-2 => -2, -1 => -1,
      0 => 0, + 1 => + 1, + 2 => + 2,
    ),
    '#default_value' => $correction,
  );


  // Per-path visibility.
  $form['hijri_settings']['node_type'] = array(
    '#type' => 'details',
    '#title' => t('Content types'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'hijri_settings',
    '#weight' => 0,
  );
  $types = array_map(array('\Drupal\Component\Utility\Html', 'escape'), node_type_get_names());
  $form['hijri_settings']['node_type']['hijri_types'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Hijri date Correction field for specific content types'),
    '#default_value' => $system_date->get('hijri_types'),
    '#options' => $types,
    '#description' => t('Add/Remove the Correction field for content type.'),
  );

  $form['hijri_settings']['node_display'] = array(
    '#type' => 'details',
    '#title' => t('Node Display'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'hijri_settings',
    '#weight' => 0,
  );

  $form['hijri_settings']['node_display']['hijri_display'] = array(
    '#type' => 'radios',
    '#title' => t('Hijri date on the node view'),
    '#default_value' => $system_date->get('hijri_display'),
    '#options' => $hijri_types,
    '#description' => t('Select the display type you want to be in the node view page'),
  );


  $form['hijri_settings']['hijri_comment_display'] = array(
    '#type' => 'details',
    '#title' => t('Comment Display'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'hijri_settings',
    '#weight' => 0,
  );

  $form['hijri_settings']['hijri_comment_display']['hijri_comment_display'] = array(
    '#type' => 'radios',
    '#title' => t('Hijri date on the comment area'),
    '#default_value' => $system_date->get('hijri_comment_display'),
    '#options' => $hijri_types,
    '#description' => t('Select the display type you want to be in the comment area'),
  );


  $form['hijri_settings']['block_display'] = array(
    '#type' => 'details',
    '#title' => t('Block Display'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'hijri_settings',
    '#weight' => 0,
  );

  $form['hijri_settings']['block_display']['hijri_display_block'] = array(
    '#type' => 'radios',
    '#title' => t('Hijri date on Hijri block'),
    '#default_value' => $system_date->get('hijri_display_block'),
    '#options' => $hijri_types,
    '#description' => t('Select the display type you want in Hijri block'),
  );
  return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
      $this->config('hijri.config')
      ->set('correction_value', $form_state->getValue('hijri_correction_value'))
      ->set('hijri_types', $form_state->getValue('hijri_types'))
      ->set('hijri_display', $form_state->getValue('hijri_display'))
      ->set('hijri_comment_display', $form_state->getValue('hijri_comment_display'))
      ->set('hijri_display_block', $form_state->getValue('hijri_display_block'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
