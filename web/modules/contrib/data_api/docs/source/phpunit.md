# Testing with PhpUnit

If you are injecting a `Drupal\data_api\Data` object into a class that you're writing for your module.  You can do it in such a way that it will be unit testable.

Here's how:

1. Place the following at the top of your PhpUnit test file, or test suite bootstrap, so that data_api's `autoload.php` file is included in your tests.  Adjust the displayed path as needed.

        require_once dirname(__FILE__) . '/...../data_api/tests/bootstrap.php';

1. Then use the `Drupal\data_api\DataMock` object as the injection class.  It has been decoupled from Drupal and works fine in most normal circumstances.  You may need to extend it for you specific situation, but there should be enough to go on by looking at that class alone as to how to do that.  Here's a possible solution:

        <?php
        ...
        
        use Drupal\data_api\DataMock;
        
        /**
         * Provides to a base test for module unit tests.
         */
        class TestBase extends \PHPUnit_Framework_TestCase
        {
            public function setUp()
            {
                $this->dataApi = new DataMock;
                this->myClass = new MyClass($this->dataApi);
            }
        }

## When Testing an Entity

Refer to the following test method:

      public function testFVariationsWork($control, $default, $column, $delta) {
        global $entity;
        $entity = (object) [
          'type' => 'page',
          'field_summary' => [
            'und' => [
              0 => ['value' => 'lorem'],
              1 => ['value' => 'ipsum'],
            ],
          ],
        ];
        
        ...
        
      }
      
You must do the following:

1. `global $entity`
2. Give `$entity->type' an arbitrary value.
