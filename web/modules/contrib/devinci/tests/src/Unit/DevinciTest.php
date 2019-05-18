<?php

namespace Drupal\devinci\Tests;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Tests\UnitTestCase;

/*
 * @group Devinci
 */
class DevinciTest extends UnitTestCase{


    public function testEnvironmentSwitch(){

        // Taken from environment module's set environment function
        $config = \Drupal::configFactory();
        $config->getEditable('environment.settings')->set('environment', 'prod')->save();
        $this->assertEquals($config->getEditable('environment.settings')->get('environment'), 'prod');

        // Simulate a page load by sending a request
        $client = \Drupal::httpClient();
        $request = $client->request('GET', 'http://devinci.local');

        $client->send($request);

        // Check environment equals settings.local environment
        $this->assertEquals($config->getEditable('environment.settings')->get('environment'), 'local');
    }

}