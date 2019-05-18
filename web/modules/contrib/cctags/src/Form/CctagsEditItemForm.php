<?php

namespace Drupal\cctags\Form;

use Drupal\block\Entity\Block;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoginDisableSettingsForm.
 *
 * @package Drupal\cctags\Form
 */
class CctagsEditItemForm extends FormBase {

  protected $configFactory;

  /**
   * The route builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $configFactory, RouteBuilderInterface $route_builder, EntityManagerInterface $entity_manager) {
    $this->configFactory = $configFactory->getEditable('cctags.settings');
    $this->routeBuilder = $route_builder;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('router.builder'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cctags_edit_item_form';
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
  public function buildForm(array $form, FormStateInterface $form_state, $cctid = NULL) {

    $form = array();
    $maxlevel = 0;
    if ($cctid) {
      $items=_cctags_get_settings($cctid);
      $item = $items[$cctid];
      $form['cctags_cctid'] = array(
        '#type' => 'hidden',
        '#value' => $cctid,
      );
    }
    else {
      $item = array(
        'cctid' => NULL,
        'name' => '',
        'block' => false,
        'block_id' => '',
        'page' => false,
        'page_title' => '',
        'page_path' => '',
        'page_level' => 5,
        'page_amount' => 0,
        'page_sort' => 'title,asc',
        'page_mode' => 'mixed',
        'page_vocname' => '',
        'page_extra_class' => '',
        'item_data' => array(),
      );
    }
    $vocabularies =array();
    $v = Vocabulary::loadMultiple();
    foreach ($v as $vocabulary) {
      $tree[$vocabulary->id()] = $this->entityManager->getStorage('taxonomy_term')->loadTree($vocabulary->id());
      $vocabularies[$vocabulary->id()]['name'] = $vocabulary->label();
      foreach ($tree[$vocabulary->id()] as $l) {
        if ($maxlevel<$l->depth) {
          $maxlevel = $l->depth;
        }
        $vocabularies[$vocabulary->id()][$l->depth] = $maxlevel;
      }
    }
    $form['cctags_name']= array(
      '#type' => 'textfield',
      '#title' => $this->t('Cctags item name'),
      '#default_value' => $item['name'],
      '#required' => TRUE,
    );

    $form['vocabulary_table'] = array(
      '#type' => 'table',
      '#header' => array($this->t('Vocabulary Name'), $this->t('Level 0'), $this->t('Level 1'), $this->t('Level 2'), $this->t('Level 3')),
    );

    foreach ($vocabularies as $key => $value) {
      $form['vocabulary_table'][$key]['cctags_select_' . $key] = array(
        '#type' => 'checkbox',
        '#title' => $value['name'],
        '#default_value' => $item['item_data'][$key]['cctags_select_' . $key]
      );

      foreach ($value as $k => $count_terms) {
        if (is_numeric($k)) {
          $form['vocabulary_table'][$key]['level_' . $k] = array(
            '#type' => 'checkbox',
            '#default_value' =>  $item['item_data'][$key]['level_' . $k],
          );
        }
      }
    }

    $form['cctags_block'] = array(
      '#type' => 'details',
      '#title' => $this->t('Setting for block of this item cctags'),
      '#open' => TRUE,
    );
    $form['cctags_block']['block'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable block for this cctags item'),
      '#default_value' => $item['block'],
    );
    $form['cctags_block']['block_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Block name'),
      '#maxlength' => 255,
      '#description' => $this->t('A name of your block. Used on the block overview page. If empty then uses name this cctags item.'),
      '#default_value' => $item['block_id'],
      '#prefix' => '<div class="cctags-settings-block">',
      '#suffix' =>'</div>',
    );
    $form['cctags_page'] = array(
      '#type' => 'details',
      '#title' => $this->t('Setting for page of this item cctags'),
      '#open' => TRUE,
    );
    $form['cctags_page']['page'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable access page for this cctags item'),
      '#default_value' => $item['page'],
    );
    $form['cctags_page']['page_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#maxlength' => 64,
      '#description' => $this->t('A title of your page.'),
      '#default_value' => $item['page_title'],
      '#prefix' => '<div class="cctags-settings-page">',
    );

    $form['cctags_page']['page_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Page path'),
      '#maxlength' => 128,
      '#description' => $this->t('Path to access of your page. If empty, predefined path cctags/page/%item-id.'),
      '#default_value' => $item['page_path'],
    );
    $c = _cctags_get_select_list('level');
    $form['cctags_page']['page_level'] = array(
      '#type' => 'select',
      '#options' => $c,
      '#title' => $this->t('Number of levels fonts metrics'),
      '#default_value' => $item['page_level'],
      '#description' => $this->t('The number of levels between the least popular tags and the most popular ones. Different levels will be assigned a different class to be themed in cctags.css'),
    );
    $op_sort = array('level,asc' => $this->t('by level, ascending'), 'level,desc' => $this->t('by level, descending'), 'title,asc' => $this->t('by title, ascending'), 'title,desc' => $this->t('by title, descending'), 'random,none' => $this->t('random'));
    $form['cctags_page']['page_sort'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Tags sort order'),
      '#options' => $op_sort,
      '#default_value' => $item['page_sort'],
      '#description' => $this->t('Determines the sort order of the tags on the page.'),
    );
    $amounts = _cctags_get_select_list('amount_tags');
    $form['cctags_page']['page_amount'] = array(
      '#type' => 'select',
      '#options' => $amounts,
      '#title' => $this->t('Amount of tags on the per page'),
      '#default_value' => $item['page_amount'],
      '#description' => $this->t('The amount of tags that will show up in a cloud on the per pages. if value equal 0, then all tags, for this cctags item, will be visible in one page.'),
    );
    $op_mode = array('group' => $this->t('group by vocabulary'), 'mixed' => $this->t('mixed vocabulary'));
    $form['cctags_page']['page_mode'] = array(
      '#type' => 'radios',
      '#title' => $this->t('View page mode'),
      '#options' => $op_mode,
      '#default_value' => $item['page_mode'],
      '#description' => $this->t('Determines the view mode of the tags on the page.'),
    );
    $form['cctags_page']['page_vocname'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Cctags view page vocabulary name'),
      '#default_value' => $item['page_vocname'],
      '#description' => $this->t('Determines the view vocabulary name(s).'),
    );

    $form['cctags_page']['page_extra_class'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Page wrapper extra class'),
      '#maxlength' => 64,
      '#description' => $this->t('Extra class for page wrapper.'),
      '#default_value' => $item['page_extra_class'],
      '#suffix' => '</div>',
    );

    $form['cctags_select_block_maxlevel'] = array(
      '#type' => 'hidden',
      '#value' => $maxlevel,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save cctags item'),
    );

    $form['#attached']['library'][] = 'cctags/cctags_js';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, $cctid = NULL) {
    $conn = Database::getConnection();
    $id = $form_state->getValue('cctags_cctid');
    $conn->update('cctags')->fields(array(
      'name'=> $form_state->getValue('cctags_name'),
      'block' => $form_state->getValue('block'),
      'block_id' => 'cctagsblock_'. $id,
      'page' => $form_state->getValue('page'),
      'page_title' => $form_state->getValue('page_title'),
      'page_path' => $form_state->getValue('page_path'),
      'page_level' => $form_state->getValue('page_level'),
      'page_amount' => $form_state->getValue('page_amount'),
      'page_sort' => $form_state->getValue('page_sort'),
      'page_mode' => $form_state->getValue('page_mode'),
      'page_vocname' => $form_state->getValue('page_vocname'),
      'page_extra_class' => $form_state->getValue('page_extra_class'),
      'item_data' => serialize($form_state->getValue('vocabulary_table'))
    ))->condition('cctid',$id)
      ->execute();

    if($form_state->getValue('block')) {
      $values = array(
        // A unique ID for the block instance.
        'id' => 'cctagsblock_'. $id,
        // The plugin block id as defined in the class.
        'plugin' => 'cctags_block',
        // The machine name of the theme region.
        'region' => 'content',
        'settings' => array(
          'label' => 'Cctags',
          'tags' => 40,
          'tags_more' => 1,
          'tags_sort' => 'title,asc',
          'level' => 6,
          'extra_class' => '',
        ),
        // The machine name of the theme.
        'theme' => $this->config('system.theme')->get('default'),
        'visibility' => array(),
        'weight' => 100,
      );

      if (!Block::load('cctagsblock_'. $id)) {
        $block = Block::create($values);
        $block->save();
      }

    }
    if ($form_state->getValue('page')) {
      $this->routeBuilder->rebuild();
    }
    $form_state->setRedirect('cctags.settings_form');

  }

}
