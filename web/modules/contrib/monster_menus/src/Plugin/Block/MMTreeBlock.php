<?php

namespace Drupal\monster_menus\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Controller\MMTreeViewController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a 'Monster Menus MM Tree' block.
 *
 * @Block(
 *   id = "mm_tree_block",
 *   admin_label = @Translation("MM Tree block"),
 * )
 */
class MMTreeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $thisMMTID, $mmtids, $title_override;

  /**
   * The route match.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Creates a MMTreeBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Request $request
   *   The request object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'admin_only' => FALSE,
      'allow_rss' => FALSE,
      'delta' => '',
      'help' => '',
      'info' => '',
      'show_node_contents' => FALSE,
      'title' => '',
      'title_is_cat' => FALSE,
    ];
  }

  private function getCurrentPage() {
    if (!isset($this->mmtids)) {
      // If this is a pseudo-path for user homepage alpha directories, treat it
      // specially.
      $path = $this->request->attributes->get('_route') == 'monster_menus.userlist' ? 'mm/' . $this->request->attributes->get('mmtid') : NULL;
      mm_parse_args($this->mmtids, $oarg_list, $this->thisMMTID, $path);
    }
    return $this->thisMMTID ? [$this->mmtids, $this->thisMMTID] : [NULL, NULL];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    list(, $this_mmtid) = $this->getCurrentPage();
    if ($this_mmtid) {
      $tags = [
        "mm_tree:$this_mmtid",
        'user:' . \Drupal::currentUser()->id(),
        'mm_block:' . $this->getConfiguration()['delta'],
      ];
      return Cache::mergeTags(parent::getCacheTags(), $tags);
    }
    return parent::getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheTags(), MMTreeViewController::getCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    if (isset($this->title_override)) {
      return $this->title_override;
    }
    return $this->getConfiguration()['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    list($mmtids, $this_mmtid) = $this->getCurrentPage();
    if (!$this_mmtid) {
      return [];
    }

    $show_arr = mm_module_invoke_all('mm_menus_block_shown', $this_mmtid, $this);
    if ($show_arr && array_product($show_arr) == 0) {
      return [];
    }

    $content = [];

    // Search up the path, looking for the bottom-most block with an
    // entry in mm_tree_block.
    $delta = $config['delta'];
    if ($starters = mm_content_get_blocks_at_mmtid($mmtids, $delta, TRUE)) {
      $starter = array_pop($starters);
      $start = $starter['mmtid'];

      if ($config['show_node_contents']) {
        $here = NULL;
      }
      else {
        $here = array($start);
        if (($i = array_search($start, $mmtids)) !== FALSE) {
          $here = array_slice($mmtids, $i);
        }
      }

      $params = array(
        Constants::MM_GET_TREE_ADD_TO_CACHE => TRUE,
        Constants::MM_GET_TREE_BLOCK => $delta,
        Constants::MM_GET_TREE_DEPTH => $starter['max_depth'],
        Constants::MM_GET_TREE_HERE => $here,
        Constants::MM_GET_TREE_PRUNE_PARENTS => TRUE,
        Constants::MM_GET_TREE_RETURN_NODE_COUNT => mm_get_setting('pages.hide_empty_pages_in_menu'),
        Constants::MM_GET_TREE_RETURN_PERMS => TRUE,
        Constants::MM_GET_TREE_SORT => TRUE,
      );

      $tree = mm_content_get_tree($start, $params);
      $can_edit = $tree[0]->perms[Constants::MM_PERMS_WRITE] || $tree[0]->perms[Constants::MM_PERMS_SUB] || $tree[0]->perms[Constants::MM_PERMS_APPLY];

      if ($config['title_is_cat']) {
        $this->title_override = $config['title'] = $tree[0]->name;
      }

      if ($config['show_node_contents']) {
        $prev = $tree[0]->level;

        foreach ($tree as $t) {
          if (!$t->perms[Constants::MM_PERMS_IS_RECYCLED]) {
            if ($t->level <= $prev) {
              array_splice($mmtids, $prev - $t->level - 1);
            }

            $mmtids[] = $t->mmtid;

            if ($contents = _mm_render_pages($mmtids, $config['title'], MMTreeViewController::getOargList($this->request), $err, TRUE, $config['allow_rss'], $delta)) {
              $content[] = $contents;
              $this->title_override = $config['title'];
            }
            $prev = $t->level;
          }
        }
      }
      else {
        $base = $config['title_is_cat'] ? 1 : 0;

        $parents = array('mm');
        if ($tree[$base]->parent != 1) {
          $parents[] = $tree[$base]->parent;
        }

        $rendered = \Drupal::service('monster_menus.tree_renderer')->create($tree, $base)->render();

        if ($rendered || $config['title_is_cat'] && $can_edit) {
          $content[] = $rendered;
        }
      }

      if ($config['title_is_cat'] || $config['show_node_contents']) {
        $edit_links = array();
        $contextual = mm_module_exists('contextual') && \Drupal::currentUser()->hasPermission('access contextual links');
        if ($can_edit) {
          if ($contextual) {
            // Unfortunately, we can't just use a Url object here, because then
            // serialization fails later on.
            $edit_links['monster_menus-0'] = array(
              'route_name' => 'entity.mm_tree.canonical',
              'route_parameters' => ['mm_tree' => $start],
              'title' => t('View this page')->render(),
            );
            $edit_links['monster_menus-1'] = array(
              'route_name' => 'entity.mm_tree.edit_form',
              'route_parameters' => ['mm_tree' => $start],
              'title' => t('Page settings')->render(),
            );
          }
          else {
            $edit_links[] = array(
              'title' => t('Edit'),
              'url' => Url::fromRoute($config['show_node_contents'] ? 'entity.mm_tree.canonical' : 'entity.mm_tree.edit_form', ['mm_tree' => $start]),
            );
          }
        }

        foreach ($starters as $other) {
          if (($perms = mm_content_user_can($other['mmtid'])) && ($perms[Constants::MM_PERMS_WRITE] || $perms[Constants::MM_PERMS_SUB] || $perms[Constants::MM_PERMS_APPLY])) {
            $route = $config['show_node_contents'] ? 'entity.mm_tree.canonical' : 'entity.mm_tree.edit_form';
            if ($contextual) {
              $edit_links['monster_menus-' . count($edit_links)] = array(
                'route_name' => $route,
                'route_parameters' => ['mm_tree' => $start],
                'title' => $config['show_node_contents'] ? t('View hidden page')->render() : t('Hidden page settings')->render(),
              );
            }
            else {
              $edit_links[] = array(
                'title' => t('Edit hidden'),
                'url' => Url::fromRoute($route, ['mm_tree' => $start]),
              );
            }
          }
        }

        if ($edit_links) {
          if ($contextual) {
            // Instead of using a group, which is not dynamic enough, set our
            // links in the metadata array and process further in
            // monster_menus_contextual_links_view_alter().
            $content['#contextual_links']['mm_block'] = [
              'metadata' => ['mm_links' => $edit_links],
              'route_parameters' => [],
            ];
          }
          else {
            array_unshift($content, array(
              '#theme' => 'links__mm_block_edit',
              '#prefix' => '<div class="link-wrapper">',
              '#suffix' => '</div>',
              '#attributes' => new Attribute(array('class' => array('links', 'inline'))),
              '#links' => $edit_links,
            ));
          }
        }
      }
    }

    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $block = $this->getConfiguration();

    // Signify to the form_alter hook that we care about this form.
    $form_state->setTemporaryValue('is_mm_block', TRUE);

    $x = mm_ui_strings(FALSE);
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public title'),
      '#default_value' => $block['title'],
      '#size' => 40,
      '#maxlength' => 256,
      '#description' => $this->t('An optional title appearing above the block'),
    ];
    $form['help'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Help text'),
      '#default_value' => $block['help'],
      '#rows' => 4,
      '#description' => $this->t('The text which appears in the tooltip, describing when to use this block'),
    ];
    $form['title_is_cat'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use @thing as title', $x),
      '#default_value' => $block['title_is_cat'],
      '#description' => $this->t('If checked, the title of the block\'s @thing is used as the block title instead of the Public Title field, above.', $x),
    ];
    $form['allow_rss'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow RSS feed'),
      '#default_value' => $block['allow_rss'],
      '#description' => $this->t('If checked, automatically generate an additional RSS feed <code>&lt;link&gt;</code> tag for this block.'),
    ];
    $form['show_node_contents'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show node contents'),
      '#default_value' => $block['show_node_contents'],
      '#description' => $this->t('If checked, show the contents of all nodes in this @thing instead of links.', $x),
    ];
    $form['admin_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Admin. only'),
      '#default_value' => $block['admin_only'],
      '#description' => $this->t('If checked, only users with the "administer all menus" permission can assign @things to this block.', $x),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    $values = $form_state->getValues();
    /** @var $form_state SubformStateInterface */
    $values['delta'] = str_replace('mmtreeblock_', '', $form_state->getCompleteFormState()->getValue('id'));
    $this->setConfiguration($values);
  }

}