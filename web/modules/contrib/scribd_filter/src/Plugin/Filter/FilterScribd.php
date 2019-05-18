<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\Filter\FilterScribd.
 */

namespace Drupal\scribd_filter\Plugin\Filter;

use Drupal\filter\Annotation\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Component\Utility\Html;

/**
 * Provides a filter to limit allowed HTML tags.
 *
 * @Filter(
 *   id = "filter_scribd",
 *   title = @Translation("Substitute [scribd id=xx key=yy mode=zz] tags with the scribd document located at http://scribd.com/doc/xx."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "scribd_filter_display_method" = "embed",
 *     "scribd_filter_display_mode" = "scroll",
 *   },
 *   weight = -10
 * )
 */
class FilterScribd extends FilterBase {

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings['scribd_filter_display_method'] = array(
      '#title' => t('Scribd display method'),
      '#type' => 'select',
      '#options' => array(
        'embed' => 'Html5',
        'link' => 'Link'
      ),
      '#default_value' => $this->settings['scribd_filter_display_method'],
    );
    $settings['scribd_filter_display_mode'] = array(
      '#title' => t('Scribd display mode'),
      '#type' => 'select',
      '#options' => array(
        'scroll' => 'Scroll',
        'slideshow' => 'Slideshow',
        'book' => 'Book',
      ),
      '#default_value' => $this->settings['scribd_filter_display_mode'],
    );
    return $settings;
  }

  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    $pattern = '/\[scribd\sid=([^\s]+)\skey=([^\s]+)\s*(?:mode=(\S+))?]/';
    $processed = preg_replace_callback($pattern, array($this, 'embed'), $text);
    if ($processed) {
      $result->setProcessedText($processed);
    }
    
    return $result;
  }

  public function tips($long = FALSE) {
    $display = $this->settings['scribd_filter_display_method'];
    $action = $display == 'embed' ? t('embed the scribd document') : t('create a link to the scribd document');
    return t('Use [scribd id=xx key=yy mode=zz] where xx is you scribd document_id, yy is your scribd document access_key and zz is your optional mode (scroll or slideshow) to %action.', array('%action' => $action));
  }
  
  protected function embed($matches) {
    $data['scribd_doc_id'] = HTML::escape($matches[1]);
    $data['scribd_access_key'] = HTML::escape($matches[2]);
    
    // If mode isn't set, use the default!
    if (!empty($matches[3])) {
      $data['scribd_mode'] = HTML::escape($matches[3]);
    }
    else {
      $data['scribd_mode'] = empty($this->settings['scribd_filter_display_mode']);
    }
    
    
    if ($data['scribd_mode'] == 'link') {
      return $this->link($data['scribd_doc_id'], $data['scribd_access_key']);
    }

    switch ($this->settings['scribd_filter_display_method']) {
      case 'link':
        return $this->link($data['scribd_doc_id'], $data['scribd_access_key']);
        break;
      default:
        return $this->embed_html5($data['scribd_doc_id'], $data['scribd_access_key'], $data['scribd_mode']);
        break;
    }
  }

  protected function embed_html5($scribd_doc_id, $scribd_access_key, $scribd_mode = 'scroll') {
    $output = array('<iframe class="scribd_iframe_embed" src="//www.scribd.com/embeds/' . $scribd_doc_id . '/content?start_page=1&view_mode=' . $scribd_mode . '&access_key=' . $scribd_access_key . '" data-auto-height="true" data-aspect-ratio="1.44339622641509" scrolling="no" id="doc_' . $scribd_doc_id . '" width="100%" height="600" frameborder="0"></iframe>');
    return implode("\n", $output);
  }

  /**
   * Replace the text with a link.
   */
  function link($scribd_doc_id, $scribd_access_key) {
    $scribd_url = '//www.scribd.com/full/' . $scribd_doc_id . '?access_key=' . $scribd_access_key;
    return l($scribd_url, $scribd_url);
  }

}