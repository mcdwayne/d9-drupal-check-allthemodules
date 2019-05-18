<?php

class DrupalQUnitTestCase extends DrupalWebTestCase {
  public function getDbPrefix() {
    return $this->databasePrefix;
  }

  protected function testPage($content) {
    variable_set('qunit_test_content_' . $this->databasePrefix, $content);
  }

  protected function testJS($js) {
    variable_set('qunit_test_js_' . $this->databasePrefix, $js);
  }

  protected function testLibrary($libs) {
    variable_set('qunit_test_library_' . $this->databasePrefix, $libs);
  }

  public function setUp() {
    parent::setUp('qunit');
  }

  public function tearDown() {
    variable_del('qunit_test_content_' . $this->databasePrefix);
    variable_del('qunit_test_js_' . $this->databasePrefix);
    variable_del('qunit_test_library_' . $this->databasePrefix);
  }

  /**
   * Override assert() so we never insert anything into the database.
   */
  public function assert() {
    /* Do nothing */
  }
}

