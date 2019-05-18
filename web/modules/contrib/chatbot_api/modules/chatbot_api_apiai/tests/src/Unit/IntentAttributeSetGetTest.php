<?php

namespace Drupal\Tests\chatbot_api_apiai\Unit;

use Drupal\api_ai_webhook\ApiAi\Model\Webhook\Request;
use Drupal\api_ai_webhook\ApiAi\Model\Webhook\Response;
use Drupal\chatbot_api_apiai\IntentRequestApiAiProxy;
use Drupal\chatbot_api_apiai\IntentResponseApiAiProxy;
use Drupal\Tests\UnitTestCase;

/**
 * Tests set/get intent attributes for Api.AI proxy classes.
 *
 * @group chatbot_api
 */
class IntentAttributeSetGetTest extends UnitTestCase {

  /**
   * Tests getIntentAttribute() method.
   */
  public function testGetIntentAttribute() {
    $request_data = [
      'timestamp' => '2017-02-09T16:06:01.908Z',
      'result' => [
        'contexts' => [
          [
            'name' => 'weather',
            'lifespan' => 2,
            'parameters' => [
              'city' => 'Rome',
              'day' => 'Monday',
            ],
          ],
          [
            'name' => 'persona',
            'lifespan' => 2,
            'parameters' => [
              'name' => 'Marie',
              'gender' => 'female',
            ],
          ],
          [
            'name' => 'mycontextname',
            'lifespan' => 2,
            'parameters' => [
              'value' => 'hello world',
            ],
          ],
          [
            'name' => 'città',
            'lifespan' => 2,
            'parameters' => [
              'nome' => 'Roma',
            ],
          ],
        ],
      ],
    ];
    $original_request = new Request($request_data);
    $request = new IntentRequestApiAiProxy($original_request);

    $this->assertEquals('Rome', $request->getIntentAttribute('weather.city'));
    $this->assertEquals('Monday', $request->getIntentAttribute('weather.day'));
    $this->assertEquals('Marie', $request->getIntentAttribute('persona.name'));
    $this->assertEquals('female', $request->getIntentAttribute('persona.gender'));

    // Make sure accessing a context by name is case-insensitive.
    $this->assertEquals('hello world', $request->getIntentAttribute('MyContextName.value'));

    // Assert accessing context names with UTF-8 characters is case-insensitive
    // too.
    $this->assertEquals('Roma', $request->getIntentAttribute('Città.nome'));
    $this->assertEquals('Roma', $request->getIntentAttribute('CITTÀ.nome'));
  }

  /**
   * Tests setIntentAttribute() method.
   */
  public function testSetIntentAttribute() {

    $original_response = new Response();
    $response = new IntentResponseApiAiProxy($original_response);

    // Set some contexts.
    $response->addIntentAttribute('weather.city', 'Rome');
    $response->addIntentAttribute('weather.day', 'Monday');
    $response->addIntentAttribute('persona.name', 'Marie');
    $response->addIntentAttribute('persona.gender', 'female');
    $likes = ['sea', 'food', 'drupal'];
    $response->addIntentAttribute('persona.likes', $likes);

    // Make sure context name get/set is case insensitive.
    $response->addIntentAttribute('MyContextName.key', 'foo');
    $response->addIntentAttribute('MyContextName.value', 'bar');
    $response->addIntentAttribute('mycontextname.full', 'foo bar');
    $response->addIntentAttribute('CITTÀ.nome', 'Roma');
    $response->addIntentAttribute('Città.regione', 'Lazio');

    // Assert setter works.
    $data = $response->jsonSerialize();
    $this->assertArrayHasKey('contextOut', $data);
    $this->assertEquals($data['contextOut'][0]->getName(), 'weather');
    $this->assertEquals($data['contextOut'][0]->getParameters()['city'], 'Rome');
    $this->assertEquals($data['contextOut'][0]->getParameters()['day'], 'Monday');
    $this->assertEquals($data['contextOut'][1]->getName(), 'persona');
    $this->assertEquals($data['contextOut'][1]->getParameters()['name'], 'Marie');
    $this->assertEquals($data['contextOut'][1]->getParameters()['gender'], 'female');
    $this->assertEquals($data['contextOut'][1]->getParameters()['likes'], $likes);

    // Change some parameters. Change the value type too, to make sure
    // overriding the value type is allowed.
    $response->addIntentAttribute('weather.day', ['Monday', 'Thursday']);
    $response->addIntentAttribute('persona.likes', 'all the small things');

    // Assert setter works with changing existing values and their types.
    $data = $response->jsonSerialize();
    $this->assertEquals($data['contextOut'][0]->getParameters()['day'], ['Monday', 'Thursday']);
    $this->assertEquals($data['contextOut'][1]->getParameters()['likes'], 'all the small things');

    // Also make sure previous parameters are unaltered.
    $this->assertEquals($data['contextOut'][0]->getParameters()['city'], 'Rome');
    $this->assertEquals($data['contextOut'][1]->getParameters()['gender'], 'female');

    // Make sure the case-sensitivity of the context names is respected on
    // set().
    $this->assertEquals($data['contextOut'][2]->getName(), 'MyContextName');
    $this->assertEquals($data['contextOut'][2]->getParameters()['key'], 'foo');
    $this->assertEquals($data['contextOut'][2]->getParameters()['value'], 'bar');
    $this->assertEquals($data['contextOut'][2]->getParameters()['full'], 'foo bar');

    // Respect UTF-8 characters for case-insensitive context names.
    $this->assertEquals($data['contextOut'][3]->getName(), 'CITTÀ');
    $this->assertEquals($data['contextOut'][3]->getParameters()['nome'], 'Roma');
    $this->assertEquals($data['contextOut'][3]->getParameters()['regione'], 'Lazio');
  }

}
