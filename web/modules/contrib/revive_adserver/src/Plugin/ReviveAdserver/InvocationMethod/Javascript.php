<?php

namespace Drupal\revive_adserver\Plugin\ReviveAdserver\InvocationMethod;

use Drupal\revive_adserver\Annotation\InvocationMethodService;
use Drupal\revive_adserver\InvocationMethodServiceBase;
use Drupal\revive_adserver\InvocationMethodServiceInterface;
use Drupal\Core\Render\Markup;

/**
 * Provides the 'Javascript' invocation method service.
 *
 * @InvocationMethodService(
 *   id = "javascript",
 *   label = @Translation("Javascript Tag"),
 *   weight = 10,
 * )
 */
class Javascript extends InvocationMethodServiceBase implements InvocationMethodServiceInterface {

  /**
   * @inheritdoc
   */
  public function render() {
    $script = '<!--//<![CDATA[
   var m3_u = (location.protocol==\'https:\'?\'https:' . $this->getReviveDeliveryPath() . '/ajs.php\':\'http:' . $this->getReviveDeliveryPath() . '/ajs.php\');
   var m3_r = Math.floor(Math.random()*99999999999);
   if (!document.MAX_used) document.MAX_used = \',\';
   document.write ("<scr"+"ipt type=\'text/javascript\' src=\'"+m3_u);
   document.write ("?zoneid=' . $this->getZoneId() . '");
   document.write (\'&amp;cb=\' + m3_r);
   if (document.MAX_used != \',\') document.write ("&amp;exclude=" + document.MAX_used);
   document.write (document.charset ? \'&amp;charset=\'+document.charset : (document.characterSet ? \'&amp;charset=\'+document.characterSet : \'\'));
   document.write ("&amp;loc=" + escape(window.location));
   if (document.referrer) document.write ("&amp;referer=" + escape(document.referrer));
   if (document.context) document.write ("&context=" + escape(document.context));
   if (document.mmm_fo) document.write ("&amp;mmm_fo=1");
   document.write ("\'><\/scr"+"ipt>");
//]]>-->';
    $build['script'] = [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#value' => Markup::create($script),
      '#attributes' => [
        'type' => 'text/javascript',
        'scrolling' => 'no',
      ],
    ];

    $build['noscript'] = [
      '#type' => 'html_tag',
      '#tag' => 'noscript',
      [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#attributes' => [
          'href' => $this->getLinkHref(),
          'target' => '_blank',
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'img',
          '#attributes' => [
            'href' => $this->getImageSrc(),
            'border' => 0,
            'alt' => '',
          ],
        ],
      ],
    ];

    $build['#cache'] = ['tags' => ['config:revive_adserver.settings']];

    return $build;
  }

}
