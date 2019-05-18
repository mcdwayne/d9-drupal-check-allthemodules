<?php

namespace Drupal\pager\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\pager\PagerStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Pager block.
 *
 * @Block(
 *   id = "pager",
 *   admin_label = @Translation("Pager Block")
 * )
 */
class PagerBlock extends BlockBase implements ContainerFactoryPluginInterface {
  protected $dbh;
  protected $entityMgr;
  protected $imgFactory;
  protected $route;

  /**
   * Extended constructor for the PageBlock class.
   *
   * @param array $config
   *   The block configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param string $plugin_def
   *   The plugin definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route
   *   The current route service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityMgr
   *   The manager used to load a node.
   * @param \Drupal\pager\PagerStorage $dbh
   *   The storage interface.
   * @param \Drupal\Core\Image\ImageFactory $imgFactory
   *   Image property access.
   */
  public function __construct(array $config, $plugin_id, $plugin_def, CurrentRouteMatch $route, EntityTypeManager $entityMgr, PagerStorage $dbh, ImageFactory $imgFactory) {
    parent::__construct($config, $plugin_id, $plugin_def);
    $this->dbh = $dbh;
    $this->entityMgr = $entityMgr;
    $this->imgFactory = $imgFactory;
    $this->route = $route;
  }

  /**
   * BlockBase extended constructor.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container interface.
   * @param array $config
   *   The block configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param string $plugin_def
   *   The plugin definition.
   */
  public static function create(ContainerInterface $container, array $config, $plugin_id, $plugin_def) {
    return new static($config, $plugin_id, $plugin_def, $container->get('current_route_match'), $container->get('entity_type.manager'), $container->get('pager.storage'), $container->get('image.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['txt_prev'] = [
      '#title'         => $this->t('Previous Text'),
      '#type'          => 'textfield',
      '#default_value' => $this->configuration['txt_prev'],
      '#required'      => FALSE,
    ];
    $form['txt_next'] = [
      '#title'         => $this->t('Next Test'),
      '#type'          => 'textfield',
      '#default_value' => $this->configuration['txt_next'],
      '#required'      => FALSE,
    ];
    $form['field'] = [
      '#title'         => $this->t('Image Field'),
      '#description'   => $this->t('The image field used for this content type.'),
      '#type'          => 'select',
      '#options'       => $this->getImgFields(),
      '#default_value' => $this->configuration['field'],
      '#required'      => TRUE,
    ];
    $form['style'] = [
      '#title'         => $this->t('Image Style'),
      '#description'   => $this->t('The image style used for this content type.'),
      '#type'          => 'select',
      '#options'       => $this->getImgStyles(),
      '#default_value' => $this->configuration['style'],
      '#required'      => TRUE,
    ];
    $form['theme'] = [
      '#title'         => $this->t('Theme'),
      '#description'   => $this->t("The pager theme."),
      '#type'          => 'select',
      '#options'       => $this->getThemes(),
      '#default_value' => $this->configuration['theme'],
      '#required'      => TRUE,
    ];
    $form['types'] = [
      '#title'         => $this->t('Content Types'),
      '#description'   => $this->t("The content types that will be included in the pager's prev/next navigation"),
      '#type'          => 'checkboxes',
      '#options'       => $this->getContentTypes(),
      '#default_value' => $this->configuration['types'],
      '#required'      => TRUE,
    ];
    $form['terms'] = [
      '#title'         => $this->t('Taxonomy Terms'),
      '#description'   => $this->t('The taxonomy terms that the content is tagged with.'),
      '#type'          => 'checkboxes',
      '#options'       => $this->getTerms(),
      '#default_value' => $this->configuration['terms'],
      '#required'      => TRUE,
    ];
    $form['interm'] = [
      '#title'         => $this->t('Maintain Term'),
      '#description'   => $this->t("The prev/nex node will keep the current node's term."),
      '#type'          => 'checkbox',
      '#default_value' => !empty($this->configuration['interm']) ? TRUE : FALSE,
      '#required'      => FALSE,
    ];
    $form['direction'] = [
      '#title'         => $this->t('Direction'),
      '#description'   => $this->t("@direction means the next node creation time is cronologically greater than the current node's creation time.", ['@direction' => $this->getDirections('forward')]),
      '#type'          => 'radios',
      '#options'       => $this->getDirections(),
      '#default_value' => ($this->configuration['direction'] == 'backward') ? 'backward' : 'forward',
      '#required'      => TRUE,
    ];
    $form['behavior'] = [
      '#title'         => $this->t('End Behavior'),
      '#description'   => $this->t('The first/last content behavior.'),
      '#type'          => 'radios',
      '#options'       => $this->getBehaviors(),
      '#default_value' => $this->configuration['behavior'],
      '#required'      => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['behavior'] = $form_state->getValue('behavior');
    $this->configuration['direction'] = $form_state->getValue('direction');
    $this->configuration['field'] = $form_state->getValue('field');
    $this->configuration['interm'] = $form_state->getValue('interm');
    $this->configuration['style'] = $form_state->getValue('style');
    $this->configuration['terms'] = $this->getOptionValues($form_state->getValue('terms'));
    $this->configuration['theme'] = $form_state->getValue('theme');
    $this->configuration['txt_next'] = $form_state->getValue('txt_next');
    $this->configuration['txt_prev'] = $form_state->getValue('txt_prev');
    $this->configuration['types'] = $this->getOptionValues($form_state->getValue('types'));
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
  }

  /**
   * Build the pager block render array.
   *
   * @return array
   *   A Drupal render array.
   */
  public function build() {
    $data = [];
    $node = $this->route->getParameter('node');

    if (!$node) {
      return [];
    }
    $tid = $this->getTid($node);

    if (!$tid) {
      return [];
    }
    // Previous data.
    $prev = $this->getPrevious($node->id(), $node->get('created')->value, $tid);

    if ($prev) {
      $data[] = $prev;
    }
    // Next data.
    $next = $this->getNext($node->id(), $node->get('created')->value, $tid);

    if ($next) {
      $data[] = $next;
    }
    // Rewrite for backwards.
    if ($this->configuration['direction'] == 'backward' && count($data) == 2) {
      $data = array_reverse($data);
      $data[0]->class = 'pager-prev-node';
      $data[1]->class = 'pager-next-node';
    }
    elseif ($this->configuration['direction'] == 'backward') {
      $data[0]->class = ($data[0]->class == 'pager-prev-node') ? 'pager-next-node' : 'pager-prev-node';
    }
    // Return build array.
    if (!empty($data)) {
      return [
        '#theme'    => !empty($this->configuration['theme']) ? $this->configuration['theme'] : 'pager_block',
        '#data'     => $data,
        '#cache'    => ['max-age' => 0],
        '#attached' => ['library' => ['pager/drupal.pager-links']],
      ];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'behavior' => 'loop',
      'field'    => '',
      'style'    => 'large',
      'terms'    => [],
      'txt_next' => $this->t('Next'),
      'txt_prev' => $this->t('Previous'),
      'types'    => [],
    ];
  }

  /**
   * Process the node title for the image alt attribute.
   *
   * @param string $title
   *   The node title.
   *
   * @return string
   *   A filterd title.
   */
  protected function filterAlt($title) {
    $regx = [
      '/[\'\"]+/'    => '',
      '/[^\w\-\.]+/' => ' ',
      '/[_\s]+/'     => ' ',
      '/^\s+|\s+$/'  => '',
    ];
    return preg_replace(array_keys($regx), array_values($regx), $title);
  }

  /**
   * Build an array of end behavior options.
   *
   * @return array
   *   The behavior options.
   */
  protected function getBehaviors() {
    return [
      'loop'    => $this->t('Loop'),
      'single'  => $this->t('Single'),
      'current' => $this->t('Current'),
    ];
  }

  /**
   * Build a content type to label hash.
   *
   * @return array
   *   A content type to label hash.
   */
  protected function getContentTypes() {
    $types = [];

    foreach ($this->dbh->selectContentTypes() as $type) {
      if (preg_match('/^[a-z][a-z_]+[a-z]$/', $type)) {
        $types[$type] = ucwords($type);
      }
    }
    return $types;
  }

  /**
   * Build an array of end behavior options.
   *
   * @param string $direction
   *   The direction key.
   *
   * @return mixed
   *   The requested behavior label if requested otherwise the behavior options.
   */
  protected function getDirections($direction = '') {
    $directions = [
      'forward'  => $this->t('Forward'),
      'backward' => $this->t('Backwards'),
    ];
    if (!empty($directions[$direction])) {
      return $directions[$direction];
    }
    return $directions;
  }

  /**
   * Build a image field name to label hash.
   *
   * @return array
   *   An image field name to label hash.
   */
  protected function getImgFields() {
    $fields = [];

    foreach ($this->dbh->selectImgData() as $data) {
      $obj = (object) unserialize($data);

      if (empty($obj->field_type) || $obj->field_type != 'image') {
        continue;
      }
      if (empty($obj->entity_type) || $obj->entity_type != 'node') {
        continue;
      }
      $fields[$obj->field_name] = $obj->label;
    }
    return $fields;
  }

  /**
   * Select the previous and next node IDs.
   *
   * @param object $node
   *   The node object.
   * @param string $property
   *   The image property.
   *
   * @return int|string
   *   The requested image property value.
   */
  protected function getImgProperty($node, $property) {
    if (!$node->hasField($this->configuration['field'])) {
      return '';
    }
    $field = $node ? $node->get($this->configuration['field'])->getValue() : [['target_id' => 0]];
    $file = !empty($field[0]['target_id']) ? $this->entityMgr->getStorage('file')->load($field[0]['target_id']) : NULL;
    $uri = $file ? ImageStyle::load($this->configuration['style'])->buildUri($file->getFileUri()) : '';
    $img = $uri ? $this->imgFactory->get($uri) : NULL;

    if (!empty($img) && $img->isValid()) {
      switch ($property) {
        case 'height':
          return $img->getHeight();

        case 'width':
          return $img->getWidth();
      }
    }
    return '';
  }

  /**
   * Build a image style to label hash.
   *
   * @return array
   *   An image style to label hash.
   */
  protected function getImgStyles() {
    $styles = [];

    foreach (ImageStyle::loadMultiple() as $key => $style) {
      $styles[$key] = $style->get('label');
    }
    return $styles;
  }

  /**
   * Select the previous and next node IDs.
   *
   * @param object $node
   *   The node object.
   *
   * @return string
   *   The image URL.
   */
  protected function getImgUrl($node) {
    if (!$node->hasField($this->configuration['field'])) {
      return '';
    }
    $img = $node ? $node->get($this->configuration['field'])->getValue() : [['target_id' => 0]];
    $file = !empty($img[0]['target_id']) ? $this->entityMgr->getStorage('file')->load($img[0]['target_id']) : NULL;

    return $file ? ImageStyle::load($this->configuration['style'])->buildUrl($file->getFileUri()) : '';
  }

  /**
   * Build a nav item.
   *
   * @param int $nid
   *   The node ID.
   * @param string $label
   *   The prev/next text.
   * @param string $class
   *   The list item class.
   *
   * @return object
   *   A nav item object.
   */
  protected function getNavItem($nid, $label = '', $class = '') {
    $node = $this->entityMgr->getStorage('node')->load($nid);

    return (object) [
      'alt'    => $node ? $this->filterAlt($node->title->value) : '',
      'class'  => $class,
      'height' => $this->getImgProperty($node, 'height'),
      'href'   => $node ? $this->getNodeUrl($node->id()) : '',
      'label'  => Html::escape($label),
      'src'    => $node ? $this->getImgUrl($node) : '',
      'title'  => $node ? Html::escape($node->title->value) : '',
      'width'  => $this->getImgProperty($node, 'width'),
    ];
  }

  /**
   * Get the next nav item.
   *
   * @param int $nid
   *   The node ID.
   * @param int $created
   *   The current node creation time.
   * @param int $tid
   *   The current node term.
   *
   * @return object|bool
   *   A nav item object.
   */
  protected function getNext($nid, $created, $tid) {
    if ($this->configuration['interm']) {
      $next = $this->dbh->selectNext($created, [$tid], $this->configuration['types']);

      if (!$next && $this->configuration['behavior'] == 'loop') {
        $next = $this->dbh->selectFirst($created, [$tid], $this->configuration['types']);
      }
      elseif (!$next && $this->configuration['behavior'] == 'current') {
        $next = $nid;
      }
    }
    else {
      $next = $this->dbh->selectNext($created, $this->configuration['terms'], $this->configuration['types']);

      if (!$next && $this->configuration['behavior'] == 'loop') {
        $next = $this->dbh->selectFirst($created, $this->configuration['terms'], $this->configuration['types']);
      }
      elseif (!$next && $this->configuration['behavior'] == 'current') {
        $next = $nid;
      }
    }
    return $next ? $this->getNavItem($next, $this->configuration['txt_next'], 'pager-next-node') : FALSE;
  }

  /**
   * Select the previous and next node IDs.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return string
   *   The node URL.
   */
  protected function getNodeUrl($nid) {
    return Url::fromRoute('entity.node.canonical', ['node' => $nid], ['absolute' => TRUE])->toString();
  }

  /**
   * Get the option values.
   *
   * @param array $options
   *   And array of the submitted option values.
   *
   * @return array
   *   A cleaned and sorted array of option values.
   */
  protected function getOptionValues(array $options) {
    $options = array_flip($options);
    unset($options[0]);
    ksort($options);

    return $options;
  }

  /**
   * Get the previous nav item.
   *
   * @param int $nid
   *   The node ID.
   * @param int $created
   *   The current node creation time.
   * @param int $tid
   *   The current node term.
   *
   * @return object|bool
   *   A nav item object.
   */
  protected function getPrevious($nid, $created, $tid) {
    if ($this->configuration['interm']) {
      $prev = $this->dbh->selectPrev($created, [$tid], $this->configuration['types']);

      if (!$prev && $this->configuration['behavior'] == 'loop') {
        $prev = $this->dbh->selectLast($created, [$tid], $this->configuration['types']);
      }
      elseif (!$prev && $this->configuration['behavior'] == 'current') {
        $prev = $nid;
      }
    }
    else {
      $prev = $this->dbh->selectPrev($created, $this->configuration['terms'], $this->configuration['types']);

      if (!$prev && $this->configuration['behavior'] == 'loop') {
        $prev = $this->dbh->selectLast($created, $this->configuration['terms'], $this->configuration['types']);
      }
      elseif (!$prev && $this->configuration['behavior'] == 'current') {
        $prev = $nid;
      }
    }
    return $prev ? $this->getNavItem($prev, $this->configuration['txt_prev'], 'pager-prev-node') : FALSE;
  }

  /**
   * Build a image term name to label hash.
   *
   * @return array
   *   An image field name to label hash.
   */
  protected function getTerms() {
    $terms = [];

    foreach ($this->dbh->selectTerms() as $obj) {
      $terms[$obj->tid] = ucwords(preg_replace('/[^a-z]+/', ' ', $obj->vid)) . ': ' . $obj->name;
    }
    return $terms;
  }

  /**
   * Build an array of available themes.
   *
   * @return array
   *   An array of available themes.
   */
  protected function getThemes() {
    $themes = [];

    foreach (pager_theme() as $key => $theme) {
      $themes[$key] = $theme['template'] . '.html.twig';
    }
    return $themes;
  }

  /**
   * Get the term ID.
   *
   * @param object $node
   *   The node object.
   *
   * @return string
   *   The image URL.
   */
  protected function getTid($node) {
    if (!in_array($node->getType(), $this->configuration['types'])) {
      return 0;
    }
    return $this->dbh->selectTid($node->id(), $this->configuration['terms']);
  }

}
