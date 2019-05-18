<?php

namespace Drupal\cctags\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CctagsSettingsForm.
 *
 * @package Drupal\cctags\Form
 */
class AdminSettingsForm extends ConfigFormBase {

  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->configFactory = $configFactory->getEditable('cctags.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cctags_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cctags.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $items = _cctags_get_settings(NULL);
//    $form['#tree']=TRUE;

    $form['cctags_item'] = array(
      '#type' => 'table',
      '#header' => [
        'cctags_item_name' => $this->t('Cctags Item Name'),
        'cctags_id' => $this->t('Cctag ID'),
        'cctags_item_block' => $this->t('Block'),
        'cctags_item_page' => $this->t('Page'),
        'cctags_item_path'=> $this->t('Page Path'),
        'cctags.setting_edit_item_form' => $this->t('Operation')
      ],
    );


    foreach ($items as $key => $item) {
      $form['cctags_item'][$key]['cctags_item_name'][] = array('#markup' => $item['name']);
      $form['cctags_item'][$key]['cctags_id'][] = array('#markup' =>  $key);
      $form['cctags_item'][$key]['cctags_item_block'][] = array('#type' => 'checkbox', '#default_value' => $item['block']);
      $form['cctags_item'][$key]['cctags_item_page'][] = array('#type' => 'checkbox', '#default_value' => $item['page']);

      $form['cctags_item'][$key]['cctags_item_path'][] = $item['page'] ? [
        '#title' => $item['page_path'],
        '#type' => 'link',
        '#url' => Url::fromRoute('cctags.route'. $key)] : ' ';

      $form['cctags_item'][$key]['cctags_item_edit'][] = [
        '#title' => $this->t('Settings'),
        '#type' => 'link',
        '#url' => Url::fromRoute('cctags.setting_edit_item_form', ['cctid' => $key])];
      $form['cctags_item'][$key]['cctags_item_edit'][] = [
        '#title' => $this->t('Delete'),
        '#type' => 'link',
        '#url' => Url::fromRoute('cctags.setting_delete_item', ['cctid' => $key])];



//      $form['cctags_item'][$key]['cctags_item_id'] = array('#markup' => $item['cctid']);
      $form['cctags_item'][$key]['cctags_item_page_path'] = array('#type' => 'hidden', '#value' => $item['page_path']);
      $form['cctags_item'][$key]['cctags_item_page_title'] = array('#type' => 'hidden', '#value' => $item['page_title']);
      $form['cctags_item'][$key]['cctags_item_block_id'] = array('#type' => 'hidden', '#value' => $item['block_id']);
    }

    $form['cctags_is_cache'] = array(
      '#title' => $this->t('Enable cctags cache'),
      '#type' => 'checkbox',
      '#default_value' => $this->configFactory->get('cctags_is_cache'),
      '#description' => $this->t('If you are using modules delimiting access to content (e.g. OG), disable this option.'),
    );

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    foreach (Element::children($form) as $variable) {
      $this->configFactory->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $this->configFactory->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}
