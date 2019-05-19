<?php

namespace Drupal\visualn_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\visualn_iframe\Entity\VisualNIFrame;
//use Drupal\embed\DomHelperTrait;

// @todo: review "type" property
// @todo: review "description" property

/**
 * Provides a filter to display embedded VisualN drawings based on data attributes.
 *
 * @ingroup ckeditor_integration
 * @ingroup iframes_toolkit
 *
 * @Filter(
 *   id = "visualn_drawing_embed",
 *   title = @Translation("VisualN Drawing Embedded"),
 *   description = @Translation("Embeds VisualN drawings using data attributes: data-entity-type, data-entity-uuid, and data-view-mode."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class EmbedDrawingFilter extends FilterBase {

  // @todo: move to iframe content provider
  const IFRAME_HANDLER_KEY = 'visualn_embed_key';

  // @todo: implement tips() method
  //   see core/modules/filter/src/Plugin/Filter/FilterAlign.php

  //use DomHelperTrait;

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    // @todo: entity_embed uses attributes
    if (strpos($text, 'data-visualn-drawing-id')) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);

      //foreach ($xpath->query('//drupal-entity[@data-entity-type and (@data-entity-uuid or @data-entity-id) and (@data-entity-embed-display or @data-view-mode)]') as $node) {
      foreach ($xpath->query('//drupal-visualn-drawing[@data-visualn-drawing-id]') as $node) {
        $drawing_id = $node->getAttribute('data-visualn-drawing-id');

        // @todo: trim width and height if needed
        $width = $node->getAttribute('width');
        $height = $node->getAttribute('height');

        // @todo: make sure that it is array or empty
        $settings = $node->getAttribute('data-visualn-drawing-settings');
        // @todo: check if settings is an array
        $settings = json_decode($settings, TRUE);
        $shared = is_array($settings) && isset($settings['shared']) ? $settings['shared'] : FALSE;

        // @todo: maybe check if ::hasAttirbute() before
        $node->removeAttribute('width');
        $node->removeAttribute('height');
        $node->removeAttribute('data-visualn-drawing-id');
        $node->removeAttribute('data-visualn-drawing-settings');

        // @todo: review attribute name
        $hash = $node->getAttribute('data-visualn-drawing-hash');
        $node->removeAttribute('data-visualn-drawing-hash');


        // @todo: if additional classes set in attributes, they should be added to existing ones
        //   but not replace them. Also try to reuse existing ckeditor buttons
        //   instead of adding it to the properties form.

        $this->changeNodeName($node, 'div');

        $entity = \Drupal::entityTypeManager()->getStorage('visualn_drawing')->load($drawing_id);
        // @todo: add cachePerPermissions() to the drawing build
        if (!empty($entity) && $entity->access('view')) {
          // @todo: use only required parameters (width and height)
          //   implement getWindowParamtersFromSettings($settings)
          //   validate or clean values if needed
          //   @see \Drupal\visualn\Core\DrawerBase::setWindowParameters()
          $window_parameters = ['width' => $width, 'height' => $height];
          $window_parameters = array_filter($window_parameters);
          $entity->setWindowParameters($window_parameters);
          $drawing_markup = $entity->buildDrawing();

          // @todo: check allow_drawings_sharing setting or maybe add an additional setting
          //   to show/hide already exposed share links
          //   reset only required cache tags then (e.g. changing default link title
          //   should not reset the cache here)
          if (\Drupal::service('module_handler')->moduleExists('visualn_iframe')) {
            // @todo: convert into a service
            $share_link_builder = \Drupal::service('visualn_iframe.builder');
            if (!empty($shared)) {
              // @todo: also check first if hash is set in node attributes
              //   if hash not set or an id (iframe entry) doesn't exist for that hash,
              //   generate a new one (with default settings)
              //   and also (possibly) log an error (or warning) about empty hash used in ckeditor
              //   widget properties

              // @todo: get current path and possibly route, add column(s) to the
              //   visualn_iframe entity table structure
              //   see https://drupal.stackexchange.com/questions/202831/get-the-route-name-of-the-current-page

              $additional_config = \Drupal::config('visualn_embed.iframe.settings');
              if (empty($hash)) {
                // Ignore settings and get the 'default' iframe entry
                // since on cache clear it will generate yet another hash (because
                // it is impossible to add it to source html tag attributes at Filter
                // plugin level) level.

                // @todo: also don't override path and route (of page displayed)
                //   or leave even empty for default entry ?

                // get or create a new 'default' iframe entry based on drawing_id
                $create = $additional_config->get('implicit_entries_restore');
                // settings are not used for *default* iframe entries
                // even if set in editor since it would require to create
                // a new additional iframe *default* entry every time settings change
                $iframe_entity = \Drupal::service('visualn_iframe.builder')
                  ->getIFrameEntityByTargetId($drawing_id, $create);
                if ($iframe_entity == FALSE) {
                  // @todo: it also shows that drawing not found
                }
                else {
                  $hash = $iframe_entity->getHash();
                }
              }
              else {
                $iframe_entity = VisualNIFrame::getIFrameEntityByHash($hash);
                if (!$iframe_entity) {
                  // check if implicit_entries_restore is enabled for
                  //   visualn_embed iframe settings (see visualn_embed.module)
                  if ($additional_config->get('implicit_entries_restore')) {
                    $data = ['drawing_id' => $drawing_id];
                    $params = [
                      'drawing_id' => $drawing_id,
                      'hash' => $hash,
                      // @todo: is status required?
                      'status' => 1,
                      // @todo: check
                      'langcode' => 'en',
                      // @todo: use drawing name with possibly some other info for the
                      //   iframe name here
                      //   every other handler creating iframes, may create iframe names
                      //   of its own choice
                      'name' => $entity->label(),
                      // @todo: actually the user should be the one who creates the entry
                      //   but not the one who views page with the entry (e.g. anon users)
                      // @todo: at least log this
                      //   set to the current user ?
                      'user_id' => 1,
                      // @todo: check
                      'settings' => $settings,
                      'data' => $data,
                      'displayed' => TRUE,
                      'location' => \Drupal::service('path.current')->getPath(),
                      'viewed' => FALSE,
                      'handler_key' => static::IFRAME_HANDLER_KEY,
                      'implicit' => TRUE,
                    ];
                    $iframe_entity = \Drupal::service('visualn_iframe.builder')
                      ->createIFrameEntity($params);
                  }
                }
                else {
                  $update = FALSE;
                  // compare settings array, update if necessary
                  // @todo: maybe store (and compare for update) only settings required for iframe
                  // @todo: use getSettings() and json_encode to compare
                  //   or just update in any way
                  //   though it doesn't allow to track who changes the iframe_entity since done via
                  //   html filter
                  //   also better compare by keys as arrays
                  $stored_settings = $iframe_entity->getSettings();
                  //$stored_settings = $iframe_entity->get('settings')->value;

                  $location = $iframe_entity->getLocation();
                  if (empty($location)) {
                    $location = \Drupal::service('path.current')->getPath();
                    $iframe_entity->setLocation($location);
                    $update = TRUE;
                  }

                  // @todo: check visualn_iframe_stage table for a staged entry
                  //    usort and serialize arrays to compare
                  if ($stored_settings != $settings) {
                    // @todo: this would set inline settings without checking staged entry,
                    //   so if really needed a special options should be added to
                    //   Iframe Settings page or a permission
                    //$iframe_entity->setSettings($settings);
                    //$update = TRUE;

                    $staged_settings = $share_link_builder->getStagedIFrameSettings($hash);

                    // If staged settings entry exists, compare current
                    // inline settings the staged versions and if they are
                    // the same then allowed to use inline settings and update
                    // the visualn_iframe entry itself.
                    if (!empty($staged_settings)) {
                      if ($staged_settings == $settings) {
                        $iframe_entity->setSettings($settings);
                        $update = TRUE;
                      }
                    }

                    // Remove the staged version in any case.
                    // It was either used to updated the visualn_iframe entry or
                    // not used at all so no need to keep it. It may happen e.g. when
                    // user chaged settings via embedded drawing config form
                    // but didn't save the changes.
                    $share_link_builder->removeStagedIFrameSettings($hash);
                  }
                  else {
                    // remove staged entry (if any)
                    $share_link_builder->removeStagedIFrameSettings($hash);
                  }

                  if ($update) {
                    $iframe_entity->save();
                  }
                }
              }

              // do not create link if implicit_entries_restore is not allowed
              //   and thus there is no entry to use for users
              if ($iframe_entity) {
                $iframe_url = $share_link_builder->getIFrameUrl($hash);
                $share_link = $share_link_builder->buildLink($iframe_url);
              }
              else {
                $share_link = [];
              }

              // This shouldn't block staged iframe entries checking
              //   otherwise the if() could be moved to the upper level
              //   (see visualn_block VisualNBlock::build())
              if ($additional_config->get('allow_drawings_sharing')) {
                // @todo: use a template (possibly inline initially)
                $drawing_markup = [
                  'drawing_markup' => $drawing_markup,
                  'share_link' => $share_link,
                ];
              }

              // It will not work in case visualn_iframe was enabled, a drawing
              // configured to use sharing, then visualn_iframe disabled, drawing
              // properties changed (here the tag doesn't get attached) and then
              // visualn_iframe re-enabled and allow_drawings_sharing setting
              // submitted - it won't reset the cache, though it is an edge case.
              $drawing_markup['#cache']['tags'][] = 'visualn_embed_iframe_settings';
              // invalidate cache tag e.g. on entity delete, to restore it if allowed
              if ($iframe_entity) {
                $drawing_markup['#cache']['tags'][] = 'visualn_iframe:' . $iframe_entity->id();
              }
            }
          }



          // @todo: add cache tags for the cases when drawing entity was changed

          // @todo: doesn't show in ckeditor window due to js and css libraries not being attached,
          //   should be fixed in plugin.js
          $entity_output = \Drupal::service('renderer')->render($drawing_markup);
        }
        else {
          // this will also work if user has no permission to view the drawing

          // @todo: return some better markup or even empty output
          // @todo: add some background opaque image to empty drawings markup

          $img_path = '/' . drupal_get_path('module', 'visualn_embed') . '/images/paint-brush-solid.svg';

          if (!empty($entity) && !$entity->access('view')) {
            $text = $this->t('You don\'t have permission to view the drawing');
          }
          else {
            $text = $this->t('Drawing not found');
          }
          $empty_drawing_markup = [
            '#markup' => '<div class="text">' . $text . '</div><div class="bkg"><img src="' . $img_path . '" /></div>',
            '#attached' => [
              'library' => [
                'visualn_embed/visualn-drawing-embed-wrapper'
              ]
            ],
          ];
          $entity_output = \Drupal::service('renderer')->render($empty_drawing_markup);

          // Add 'drawing not found' wrapper class to apply background image
          // so that width and height properties would be respected (if set).
          // Position drawing not found markup at the wrapper div center.
          $classes = $node->getAttribute('class');
          $classes = (strlen($classes) > 0) ? explode(' ', $classes) : [];
          $classes[] = 'visualn-drawing-empty-drawing-wrapper';
          $node->setAttribute('class', implode(' ', $classes));



          // @todo: Depending on module settings, return a 'Drawing not found' markup
          //   or empty output. Also the the markup could be themable in the first case
          //   so that themers could adjust it according to the site design.
        }

        // The align and dimenstions (and any other) properties should apply to valid drawings
        // and empty drawing case as well.
        $style_properties = [];
        $style_attr_values = [
          // @todo: what if user wants to set units other than pixels
          'width' => !empty($width) ? "{$width}px" : '',
          'height' => !empty($height) ? "{$height}px" : '',
        ];
        foreach ($style_attr_values as $attr => $value) {
          if (!empty($value)) {
            $style_properties[] = implode(':', [$attr, $value]);
          }
        }


        // @todo: also check if style attribute is already set and concatinate in that case
        //   instead of overriding
        //   and also check if width and heigh properties are there - replace them in that case
        if ($style_properties) {
          $style = implode('; ', $style_properties) . ';';
          $node->setAttribute('style', $style);
        }



        $this->setNodeContent($node, $entity_output);
      }

      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

  /**
   * Rename a DOMNode tag.
   *
   * @note: This is a copy-paste of Drupal\embed\DomHelperTrait::changeNodeName()
   *   to avoid Embed contib module dependecy
   *
   * @param \DOMNode $node
   *   A DOMElement object.
   * @param string $name
   *   The new tag name.
   */
  protected function changeNodeName(\DOMNode &$node, $name = 'div') {
    if ($node->nodeName != $name) {
      /** @var \DOMElement $replacement_node */
      $replacement_node = $node->ownerDocument->createElement($name);

      // Copy all children of the original node to the new node.
      if ($node->childNodes->length) {
        foreach ($node->childNodes as $child) {
          $child = $replacement_node->ownerDocument->importNode($child, TRUE);
          $replacement_node->appendChild($child);
        }
      }

      // Copy all attributes of the original node to the new node.
      if ($node->attributes->length) {
        foreach ($node->attributes as $attribute) {
          $replacement_node->setAttribute($attribute->nodeName, $attribute->nodeValue);
        }
      }

      $node->parentNode->replaceChild($replacement_node, $node);
      $node = $replacement_node;
    }
  }

  /**
   * Set the contents of a DOMNode.
   *
   * @param \DOMNode $node
   *   A DOMNode object.
   * @param string $content
   *   The text or HTML that will replace the contents of $node.
   */
  protected function setNodeContent(\DOMNode $node, $content) {
    // Remove all children of the DOMNode.
    while ($node->hasChildNodes()) {
      $node->removeChild($node->firstChild);
    }

    if (strlen($content)) {
      // Load the contents into a new DOMDocument and retrieve the elements.
      $replacement_nodes = Html::load($content)->getElementsByTagName('body')->item(0);

      // Finally, import and append the contents to the original node.
      foreach ($replacement_nodes->childNodes as $replacement_node) {
        $replacement_node = $node->ownerDocument->importNode($replacement_node, TRUE);
        $node->appendChild($replacement_node);
      }
    }
  }

}
