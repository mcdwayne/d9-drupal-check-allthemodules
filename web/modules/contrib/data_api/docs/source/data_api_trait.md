# Use Inside a Class

* Whenever a class needs to use `data_api()`, you should use the trait `DataApiTrait`, instead.
* Then use DI to inject a Data object into the constructor.
* You should never call `data_api()` from inside a class (except for static methods, which are not fully tested yet, use at your own discretion.)
* Refer to the class documentation for implementation details.

        <?php
        class ClassThatNeedsADataObjectForItsMethods {
        
          use \Drupal\data_api\DataTrait;
        
          public function __construct(Data $data) {
            $this->setDataApiData($data);
          }
        }
        
        ...
        
        // Use in production code
        $data = data_api();
        $instance = new  ClassThatNeedsADataObjectForItsMethods($data);
        
        ... 
        
        // Use in testing
        $data = new \Drupal\data_api\DataMock();
        $instance = new  ClassThatNeedsADataObjectForItsMethods($data);
        
