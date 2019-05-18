Note to self (because I forget and it's annoying to find the short instructions when I want them)

To run tests,
from docroot,

````
php core/scripts/run-tests.sh \
  --sqlite /tmp/test.sqlite \
  --module field_formatter_filter
````

or
````
php core/scripts/run-tests.sh \
  --sqlite /tmp/test.sqlite \
  --class "Drupal\Tests\field_formatter_filter\Kernel\NodeFilterTest"
````

However, using the run-tests wrapper does not seem to 
 allow us to analyse tests via xdebug, or get any error messages
 for debugging.

(It seems that some debug info does get dropped into the files /sites/default/files/simplerest/phpunit-*.xml  - though that was not apparent when you run things.)



Instead, we must use raw phpunit debugging, 
 which requires us to initializer a phpunit.xml file as described in 
 https://www.drupal.org/docs/8/phpunit/running-phpunit-tests
 and then run (from {docroot}/core )
````
../vendor/bin/phpunit --debug  ../modules/field_formatter_filter/tests/src/Kernel/NodeFilterTest.php

````
or
````
../vendor/bin/phpunit --debug --group field_formatter_filter

````
