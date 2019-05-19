<?php

namespace Drupal\ueditor\Plugin\Editor;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\editor\Plugin\EditorBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a UEditor-based text editor for Drupal.
 *
 * @Editor(
 *   id = "ueditor",
 *   label = @Translation("UEditor"),
 *   supports_content_filtering = TRUE,
 *   supports_inline_editing = TRUE,
 *   is_xss_safe = FALSE,
 *   supported_element_types = {
 *     "textarea"
 *   }
 * )
 */
class UEditor extends EditorBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler to invoke hooks on.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks on.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
    $this->renderer = $renderer;
    $this->global_settings = \Drupal::config('ueditor.settings')->get('ueditor_global_settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    $settings['language'] = 'en';
    $settings['initial_content'] = '';
    $settings['zindex'] = 500;
    $settings['initialFrameHeight'] = 320;
    $settings['auto_height'] = TRUE;
    $settings['auto_float'] = TRUE;
    $settings['allowdivtop'] = FALSE;
    $settings['show_elementpath'] = TRUE;
    $settings['show_wordcount'] = TRUE;
    $settings['imagePathFormat'] = '/%b%f/ueditor/%u/upload/image/{yyyy}{mm}{dd}/{time}{rand:6}';
    $settings['scrawlPathFormat'] = '/%b%f/ueditor/%u/upload/scrawl/{yyyy}{mm}{dd}/{time}{rand:6}';
    $settings['filePathFormat'] = '/%b%f/ueditor/%u/upload/file/{yyyy}{mm}{dd}/{time}{rand:6}';
    $settings['fileManagerListPath'] = '/%b%f/ueditor/%u/upload/file/';
    $settings['catcherPathFormat'] = '/%b%f/ueditor/%u/upload/catcher/{yyyy}{mm}{dd}/{time}{rand:6}';
    $settings['imageManagerListPath'] = '/%b%f/ueditor/%u/upload/image/';
    $settings['snapscreenPathFormat'] = '/%b%f/ueditor/%u/upload/snapscreen/{yyyy}{mm}{dd}/{time}{rand:6}';
    $settings['videoPathFormat'] = '/%b%f/ueditor/upload/%u/video/{yyyy}{mm}{dd}/{time}{rand:6}';
    $settings['toolbars'] = 'fullscreen,source,|,undo,redo,|,bold,italic,underline,fontborder,strikethrough,superscript,subscript,removeformat,formatmatch,autotypeset,blockquote,pasteplain,|,forecolor,backcolor,insertorderedlist,insertunorderedlist,selectall,cleardoc,|,rowspacingtop,rowspacingbottom,lineheight,|,customstyle,paragraph,fontfamily,fontsize,|,directionalityltr,directionalityrtl,indent,|,justifyleft,justifycenter,justifyright,justifyjustify,|,touppercase,tolowercase,|,link,unlink,anchor,|,imagenone,imageleft,imageright,imagecenter,|,simpleupload,insertimage,emotion,scrawl,insertvideo,music,attachment,map,gmap,insertframe,insertcode,webapp,pagebreak,template,background,|,horizontal,date,time,spechars,snapscreen,wordimage,|,inserttable,deletetable,insertparagraphbeforetable,insertrow,deleterow,insertcol,deletecol,mergecells,mergeright,mergedown,splittocells,splittorows,splittocols,charts,|,print,preview,searchreplace,help,drafts';

    if($this->global_settings['ueditor_enable_formula_editor']){
      $settings['toolbars'] .= ',kityformula';
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();

    $form['basic'] = array(
      '#type' => 'fieldset',
      '#title' => t('Ueditor Basic Setting'),
      '#tree' => TRUE,
      '#attributes' => array('class' => array('edui-default')),
    );
    $form['basic']['language'] = array(
      '#type' => 'select',
      '#title' => 'Language',
      '#options' => array(
        'zh-cn' => 'Chinese',
        'en' => 'English',
      ),
      '#default_value' => isset($settings['basic']['language']) ? $settings['basic']['language'] : $settings['language'],
    );
    $form['basic']['zindex'] = array(
      '#type' => 'textfield',
      '#title' => t('Editor zindex'),
      '#description' => t('The official website of the default zindex 900,<br />
        and Drupal overlay module ( #overlay= page ) conflict,
        so default change from 900 to 90 or you can customize.'),
      '#default_value' => isset($settings['basic']['zindex']) ? $settings['basic']['zindex'] : $settings['zindex'],
      '#size' => 5,
      '#maxlength' => 4,
      '#required' => TRUE,
    );
    $form['basic']['initialFrameHeight'] = array(
      '#type' => 'textfield',
      '#title' => t('Editor Height'),
      '#description' => t('The default height is 320, you can change it.'),
      '#default_value' => isset($settings['basic']['initialFrameHeight']) ? $settings['basic']['initialFrameHeight'] : $settings['initialFrameHeight'],
      '#size' => 5,
      '#maxlength' => 4,
      '#required' => TRUE,
    );
    $form['basic']['initial_content'] = array(
      '#type' => 'textfield',
      '#title' => t('Editor initial content'),
      '#description' => t('Editor initial content, after editor loading in the textarea.'),
      '#default_value' => isset($settings['basic']['initial_content']) ? $settings['basic']['initial_content'] : $settings['initial_content'],
      '#maxlength' => 255,
    );
    $form['basic']['allowdivtop'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow Div Convert to P'),
      '#default_value' => isset($settings['basic']['allowdivtop']) ? $settings['basic']['allowdivtop'] : $settings['allowdivtop'],
      '#description' => t('If enable, the Div tags converted to P tag.'),
    );
    $form['basic']['auto_height'] = array(
      '#type' => 'checkbox',
      '#title' => t('Auto Height'),
      '#default_value' => isset($settings['basic']['auto_height']) ? $settings['basic']['auto_height'] : $settings['auto_height'],
      '#description' => t('If enable, the editor will auto height.'),
    );
    $form['basic']['auto_float'] = array(
      '#type' => 'checkbox',
      '#title' => t('Auto Float'),
      '#default_value' => isset($settings['basic']['auto_float']) ? $settings['basic']['auto_float'] : $settings['auto_float'],
      '#description' => t('If enable, the editor will auto float.'),
    );
    $form['basic']['show_elementpath'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show element path'),
      '#default_value' => isset($settings['basic']['show_elementpath']) ? $settings['basic']['show_elementpath'] : $settings['show_elementpath'],
      '#description' => t('If enable, It will show the element path under the editor.'),
    );
    $form['basic']['show_wordcount'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show word count'),
      '#default_value' => isset($settings['basic']['show_wordcount']) ? $settings['basic']['show_wordcount'] : $settings['show_wordcount'],
      '#description' => t('If enable, It will show the word count under the editor.'),
    );
    $form['basic']['appearance'] = array(
      '#type' => 'fieldset',
      '#title' => t('Appearance'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#attributes' => array('class' => array('edui-default')),
    );
    $form['basic']['appearance']['toolbars'] = array(
      '#type' => 'textarea',
      '#title' => t('Toolbars'),
      '#default_value' => isset($settings['basic']['appearance']['toolbars']) ? $settings['basic']['appearance']['toolbars'] : $settings['toolbars'],
      '#description' => t('Enter a comma separated list of toolbars.'),
    );

    $form['#attached']['library'][] = 'ueditor/drupal.ueditor.toolbars';

    if($this->global_settings['ueditor_enable_formula_editor']){
      $form['#attached']['library'][] = 'ueditor/ueditor.toolbar_formula';
    }

    // config the upload path.
    $form['basic']['uploadpath'] = array(
      '#type' => 'fieldset',
      '#title' => t('Custom Upload Path'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['basic']['uploadpath']['path_help'] = array(
      '#markup' => implode('<br>', array(
        '%b' => '<code>%b</code> - the base URL path of the Drupal installation (<code>'._ueditor_realpath('%b').'</code>)',
        '%m' => '<code>%m</code> - path where the UEditor module is stored (<code>'._ueditor_realpath('%m').'</code>)',
        '%l' => '<code>%l</code> - path to the libraries directory (<code>'._ueditor_realpath('%l').'</code>)',
        '%f' => '<code>%f</code> - the Drupal file system path where the files are stored (<code>'._ueditor_realpath('%f').'</code>)',
        '%d' => '<code>%d</code> - the server path to the document root (<code>'._ueditor_realpath('%d').'</code>)',
        '%u' => '<code>%u</code> - User ID (<code>'._ueditor_realpath('%u').'</code>)',
        '{yyyy}' => '<code>{yyyy}</code> - <a href="http://www.php.net/manual/en/function.date.php">The php date format</a>',
        '{mm}' => '<code>{mm}</code> - <a href="http://www.php.net/manual/en/function.date.php">The php date format</a>',
        '{dd}' => '<code>{dd}</code> - <a href="http://www.php.net/manual/en/function.date.php">The php date format</a>',
        '{time}' => '<code>{time}</code> - A timestamp',
        '{rand:6}' => '<code>{rand:6}</code> - A random number',
        '{transliteration_filename}' => '<code>{transliteration_filename}</code> - you need install <a href="https://www.drupal.org/project/transliteration">Transliteration</a> module',
        '<br>',
      )),
      '#prefix' => '<div class="region region-help"><div class="block block-system"><div class="content">',
      '#suffix' => '</div></div></div>',
    );

    $imagePathFormat = !empty($settings['basic']['uploadpath']['imagePathFormat']) ? $settings['basic']['uploadpath']['imagePathFormat'] : $settings['imagePathFormat'];
    $form['basic']['uploadpath']['imagePathFormat'] = array(
      '#type' => 'textfield',
      '#title' => t('imagePathFormat'),
      '#default_value' => $imagePathFormat,
      '#description' => 'Current path:<code>'._ueditor_realpath($imagePathFormat).'</code>',
    );
    $scrawlPathFormat = !empty($settings['basic']['uploadpath']['scrawlPathFormat']) ? $settings['basic']['uploadpath']['scrawlPathFormat'] : $settings['scrawlPathFormat'];
    $form['basic']['uploadpath']['scrawlPathFormat'] = array(
      '#type' => 'textfield',
      '#title' => t('scrawlPathFormat'),
      '#default_value' => $scrawlPathFormat,
      '#description' => 'Current path:<code>'._ueditor_realpath($scrawlPathFormat).'</code>',
    );
    $filePathFormat = !empty($settings['basic']['uploadpath']['filePathFormat']) ? $settings['basic']['uploadpath']['filePathFormat'] : $settings['filePathFormat'];
    $form['basic']['uploadpath']['filePathFormat'] = array(
      '#type' => 'textfield',
      '#title' => t('filePathFormat'),
      '#default_value' => $filePathFormat,
      '#description' => 'Current path:<code>'._ueditor_realpath($filePathFormat).'</code>',
    );
    $fileManagerListPath = !empty($settings['basic']['uploadpath']['fileManagerListPath']) ? $settings['basic']['uploadpath']['fileManagerListPath'] : $settings['fileManagerListPath'];
    $form['basic']['uploadpath']['fileManagerListPath'] = array(
      '#type' => 'textfield',
      '#title' => t('fileManagerListPath'),
      '#default_value' => $fileManagerListPath,
      '#description' => 'Current path:<code>'._ueditor_realpath($fileManagerListPath).'</code>',
    );
    $catcherPathFormat = !empty($settings['basic']['uploadpath']['catcherPathFormat']) ? $settings['basic']['uploadpath']['catcherPathFormat'] : $settings['catcherPathFormat'];
    $form['basic']['uploadpath']['catcherPathFormat'] = array(
      '#type' => 'textfield',
      '#title' => t('catcherPathFormat'),
      '#default_value' => $catcherPathFormat,
      '#description' => 'Current path:<code>'._ueditor_realpath($catcherPathFormat).'</code>',
    );
    $imageManagerListPath = !empty($settings['basic']['uploadpath']['imageManagerListPath']) ? $settings['basic']['uploadpath']['imageManagerListPath'] : $settings['imageManagerListPath'];
    $form['basic']['uploadpath']['imageManagerListPath'] = array(
      '#type' => 'textfield',
      '#title' => t('imageManagerListPath'),
      '#default_value' => $imageManagerListPath,
      '#description' => 'Current path:<code>'._ueditor_realpath($imageManagerListPath).'</code>',
    );
    $snapscreenPathFormat = !empty($settings['basic']['uploadpath']['snapscreenPathFormat']) ? $settings['basic']['uploadpath']['snapscreenPathFormat'] : $settings['snapscreenPathFormat'];
    $form['basic']['uploadpath']['snapscreenPathFormat'] = array(
      '#type' => 'textfield',
      '#title' => t('snapscreenPathFormat'),
      '#default_value' => $snapscreenPathFormat,
      '#description' => 'Current path:<code>'._ueditor_realpath($snapscreenPathFormat).'</code>',
    );
    $videoPathFormat = !empty($settings['basic']['uploadpath']['videoPathFormat']) ? $settings['basic']['uploadpath']['videoPathFormat'] : $settings['videoPathFormat'];
    $form['basic']['uploadpath']['videoPathFormat'] = array(
      '#type' => 'textfield',
      '#title' => t('videoPathFormat'),
      '#default_value' => $videoPathFormat,
      '#description' => 'Current path:<code>'._ueditor_realpath($videoPathFormat).'</code>',
    );

    // Build a fake Editor object, which we'll use to generate JavaScript
    // settings for this fake Editor instance.
    $fake_editor = Editor::create(array(
      'format' => $editor->id(),
      'editor' => 'ueditor',
      'settings' => $settings,
    ));
    $config = $this->getJSSettings($fake_editor);
    // Remove the ACF configuration that is generated based on filter settings,
    // because otherwise we cannot retrieve per-feature metadata.
    unset($config['allowedContent']);
    $form['hidden_ueditor'] = array(
      '#markup' => '<div id="ueditor-hidden" class="hidden"></div>',
      '#attached' => array(
        'drupalSettings' => ['ueditor' => ['hiddenUEditorConfig' => $config]],
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getJSSettings(Editor $editor) {
    $settings = array();
    $settings = $editor->getSettings();

    if(isset($settings['basic'])){
      global $base_url;
      $settings['basic']['editorPath'] = $base_url.'/'.drupal_get_path('module', 'ueditor') . '/lib/';
      $settings['basic']['serverUrl'] = $base_url.'/ueditor/controller/upload';
      $settings['basic']['toolbars'][] = $this->ueditor_toolbars($settings['basic']['appearance']['toolbars']);
      $settings['basic']['enable_formula'] = $this->global_settings['ueditor_enable_formula_editor'];
      return $settings['basic'];
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    $libraries = array(
      'ueditor/drupal.ueditor',
    );

    if($this->global_settings['ueditor_enable_formula_editor']){
      array_push($libraries, 'ueditor/ueditor.formula');
    }

    return $libraries;
  }

  /**
   * Convert toolbars array of string.
   */
  public function ueditor_toolbars($toolbars) {
    if(!empty($toolbars)) {
      return explode(',', $toolbars);
    }
    return '';
  }

}
