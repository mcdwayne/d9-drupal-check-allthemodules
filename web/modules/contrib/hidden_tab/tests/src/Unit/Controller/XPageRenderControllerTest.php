<?php

namespace Drupal\Tests\hidden_tab\Unit\Controller;

use Drupal\hidden_tab\Controller\XPageRenderController;
use Drupal\hidden_tab\Plugable\Render\HiddenTabRenderPluginManager;
use Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager;
use Drupal\hidden_tab\Service\HiddenTabEntityHelper;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @coversDefaultClass \Drupal\hidden_tab\Controller\XPageRenderController
 * @group hidden_tab
 *
 * @see \Drupal\hidden_tab\Controller\XPageRenderController
 */
class XPageRenderControllerTest extends UnitTestCase {

  public function testDidplay() {
    $ctl = new XPageRenderController(
      '/node/10/a',
      new ParameterBag(),
      $this->createMock(HiddenTabTemplatePluginManager::class),
      $this->createMock(HiddenTabRenderPluginManager::class),
      $this->createMock(HiddenTabEntityHelper::class)
    );
  }

}