<?php

namespace Drupal\tlh;

use Drupal\block\Entity\Block;
use Drupal\block\BlockViewBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\views\Views;

class TwigExtension extends \Twig_Extension implements \Twig_ExtensionInterface {

  public function getName() {
    return 'ThemersLittleHelper';
  }


  /**
   * The block builder
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $blockBuilder;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * TwigExtension constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager The
   *   entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory The
   *   configuration factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->blockBuilder = \Drupal::entityTypeManager()->getViewBuilder('block');
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Register tne current path as a global.
   */
  public function getGlobals() {
    global $request, $base_insecure_url, $base_secure_url;
    $globals = [];
    if (!is_null($request)) {
      $globals['base_url'] = $base_insecure_url;
      if ($request->isSecure()) {
        $globals['base_url'] = $base_secure_url;
      }
    }
    return $globals;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {

    $return = [
      new \Twig_SimpleFunction('drupal_view', [$this, 'drupalViewFunction']),
      new \Twig_SimpleFunction('drupal_block', [$this, 'drupalBlockFunction']),
    ];
    // hook in support for the Symfony vardumper
    if (\Drupal::moduleHandler()->moduleExists('vardumper')) {
      $return[] = new \Twig_SimpleFunction('vdp', 'vdp');
      $return[] = new \Twig_SimpleFunction('vdm', 'vdpm');
      $return[] = new \Twig_SimpleFunction('dump', 'dump');
    }
    if (function_exists('xdebug_break')) {
      $return[] = new \Twig_SimpleFunction('xdebug_break', [
        $this,
        'xdebugBreakFunction',
      ],
        [
          'needs_environment' => TRUE,
          'needs_context' => TRUE,
        ]);
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    $return = [
      new \Twig_SimpleFilter('wrap', [$this, 'wrapFilter'], [
        'is_safe' => ['html'],
      ]),
    ];
    if (function_exists('mb_get_info')) {
      $return[] = new \Twig_SimpleFilter('truncate', [
        $this,
        'truncateMultibyteFilter',
      ], [
        'needs_environment' => TRUE,
      ]);
    }
    else {
      $return[] = new \Twig_SimpleFilter('truncate', [
        $this,
        'truncateFilter',
      ], [
        'needs_environment' => TRUE,
      ]);
    }
    $return[] = new \Twig_SimpleFilter('shuffle', [
      $this,
      'shuffle_filter',
    ]);
    $return[] = new \Twig_SimpleFilter('value', [
      $this,
      'valueFilter',
    ]);
    return $return;
  }


  /**
   * Return the render array for a view
   *
   * @param string $view_name
   * @param string $display_id
   * @param array $args Contextual filter values
   *
   * @return Views
   */
  public function drupalViewFunction($view_name, $display_id = 'default', $args = []) {
    /*
     * yeah... really...
     */
    if (is_array($display_id)) {
      $args = $display_id;
      $display_id = 'default';
    }
    $view = Views::getView($view_name);
    if (is_object($view)) {
      $view->setArguments($args);
      $view->setDisplay($display_id);
      $view->preExecute();
      $view->execute();
      $content = $view->buildRenderable($display_id, $args);
      return $content;
    }
  }


  /**
   * Returns the render array for a block by $ID
   *
   * @param $id
   * @param $renderable bool
   *
   * @return array|void
   */
  public function drupalBlockFunction($id,$renderable = true) {
    $block = Block::load($id);
    if ($block) {
      return $renderable ? $this->entityTypeManager->getViewBuilder('block')
        ->view($block) : BlockViewBuilder::preRender(BlockViewBuilder::lazyBuilder($id,'full'))['content'];
    }
  }

  /**
   * Add an xdebug breakpoint
   */
  public function xdebugBreakFunction(\Twig_Environment $environment, $context) {
    $_xdebug_caller = xdebug_call_file() . ' line ' . xdebug_call_line();
    xdebug_break();
  }

  /**
   * Port of the Twig-extensions truncate Multibyte aware filter
   *
   * @param \Twig_Environment $env
   * @param $value string value to truncate
   * @param int $length int length to truncate
   * @param bool $preserve bool preserve words
   * @param string $separator string separator to add after truncating
   *
   * @return string
   */
  public function truncateMultibyteFilter(\Twig_Environment $env, $value, $length = 30, $preserve = FALSE, $separator = '...') {
    $value = $this->renderValueIfNeeded($value);
    if (mb_strlen($value, $env->getCharset()) > $length) {
      if ($preserve) {
        // If breakpoint is on the last word, return the value without separator.
        if (FALSE === ($breakpoint = mb_strpos($value, ' ', $length, $env->getCharset()))) {
          return $value;
        }
        $length = $breakpoint;
      }
      $value = trim(mb_substr($value, 0, $length, $env->getCharset())) . $separator;
    }
    return $value;
  }

  /**
   * Port of the Twig-extensions truncate filter
   *
   * @param \Twig_Environment $env
   * @param $value
   * @param int $length
   * @param bool $preserve
   * @param string $separator
   *
   * @return string
   */
  public function truncateFilter(\Twig_Environment $env, $value, $length = 30, $preserve = FALSE, $separator = '...') {
    $value = $this->renderValueIfNeeded($value);
    if (strlen($value) > $length) {
      if ($preserve) {
        if (FALSE !== ($breakpoint = strpos($value, ' ', $length))) {
          $length = $breakpoint;
        }
      }
      return trim(substr($value, 0, $length)) . $separator;
    }
    return $value;
  }

  /**
   * @param $value
   * @return string
   */
  public function valueFilter($value) {
    $value = $this->renderValueIfNeeded($value);
    return $value;
  }

  /**
   * Wraps the $context with the given tag
   *
   * @param $value
   * @param $tag
   *
   * @return mixed
   */
  public function wrapFilter($value, $tag) {
    $value = $this->renderValueIfNeeded($value);
    if (is_scalar($value)) {
      return sprintf('<%s>%s</%s>', $tag, trim($value), $tag);
    }
    return $value;
  }

  /**
   * Shuffle an array
   * Ported from the TwigExtensions library
   * @link https://github.com/twigphp/Twig-extensions/blob/master/lib/Twig/Extensions/Extension/Array.php
   * @param $array
   *
   * @return array
   */
  public function shuffle_filter($array) {
    if ($array instanceof Traversable) {
      $array = iterator_to_array($array, FALSE);
    }
    shuffle($array);
    return $array;
  }

  /**
   * Renders the given value to a string if needed
   *
   * Prevents filters and functions from failing on a render array or markup
   * object
   *
   */

  private function renderValueIfNeeded($inputValue) {
    $value = $inputValue;
    if ($inputValue instanceof Markup) {
      $value = $inputValue->__toString();
    }
    elseif (isset($inputValue['#theme']) && $inputValue['#theme'] == 'field') {
      // really quick through the bend version
      if (isset($inputValue[0]['#markup'])) {
        $value = $inputValue[0]['#markup'];
      }
      elseif (isset($inputValue['#items']) && $inputValue['#items'] instanceof \Drupal\Core\TypedData\TypedDataInterface) {
        $item_values = $inputValue['#items']->getValue();
        $value = [];
        if (empty($item_values)) {
          return NULL;
        }
        foreach ($item_values as $delta => $values) {
          $value[] = $values['value'];
        }
      }
      $value = is_array($value) ? reset($value):$value;
    }
    return $value;
  }
}
