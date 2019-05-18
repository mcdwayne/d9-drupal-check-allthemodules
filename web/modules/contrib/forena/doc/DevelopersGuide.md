# Developing Applications With Forena
Forena can be used together with Drupal as a rapid application development develop some impressive ajax enabled
applications.  This guide illustrates the basic approach to building a Drupal 8 forena enabled application.  Throughout
this documentation we will assume you are developing a drupal module that will becalled myexample.  Whereever you see 
this you should substitute a suitably unique module name (e.g. acme_reporting_module)

## Basic Drupal Module Creation 
Module creation with Forena is not substantially different than creating any other drupal module, and is really beyond 
the scope of this documentation. That being said, it is pretty simple to create a custom module to house your reports
and data query definitions.  Here is a brief list of the files required alogn with some comments about what they do.

These files would normally be found in your drupal site the folder modules/custom/myexample.  

### myexample.info.yml
This file advertises the module to Drupal.  Further information can be found at 
https://www.drupal.org/docs/8/creating-custom-modules/let-drupal-8-know-about-your-module-with-an-infoyml-file

```yaml
name: My Example Forena Module
description: Demonstrates a simple bundle of custom reports and queries using forena
type: module
core: 8.x
```

### myexample.forena.yml
Advertises reports and data sources that this plugin uses to forena. 

````yaml
# Specify that all .frx files should appear in the reports subdirectory of the folder containing myexample.forena.yml
report directory: reports
data:
  # The following data connection would allow sql queries and static data XML files to be stored in the "data" subdirectory
  # of the folder containing the myexample.forena.yml file.
  myexternaldata:
    # use a standard PDO Driver
    driver: FrxPDO
    title: My Example Data
    # Specifiy that sql queries live in the data subfolder
    source: data
    # Specify the PDO connection string connecting to a database named myexample
    uri: "pgsql:host=postgresserverhost;port=5432;dbname=myexample"
    user: "wapdc"
  mysitequeries: 
    # Use the drupal driver
    driver: FrxDrupal
    title: My Custom site queries
    # specify that custom drupal sql queries can be found in the site_sql subfolder 
    source: site_sql

````

### myexample.permissions.yml (optional)
Advertises any custom permissions that might be used in drupal to control who has access to data.  These permissions 
would then be used in the --ACCESS= comments of the sql queries used in your reports.  

````yaml
'access myexternal data':
  title: 'Provides general access to mysql data'
  description: 'A deeper description of what this access might mean'
````

### myexample.routing.yml (optional)
Define extra routes that respond to custom paths that you define within your drupal site. Controllers that you create 
should be in your modules src folder.  Forena provides a base controller class provides quick ways to ajax load reports, 
custom drupal forms, but any class can be used to generate custom content.  This example shows the definition of a page 
that provides access to system of reports via ajax. 

```yaml
myexample.report-dashboard:
  path: "myexampledashboard/{action}"
  defaults:
    _controller: '\Drupal\myexample\Controller\MyAjaxController::page'
    _title: "Ajax Controller Example"
    action: ""
  requirements:
    _permission: "access content"
```

### myexample.menu.links.yml (optional)
If you created a custom controller as described above, it is handy to be able to advertise that controllers implementation
to the drupal menu system so that it can be placed in Drupal's menu system in your site.  The following file illustrates the
advertising of the menu system/ 

## Developing Ajax Controllers
Forena provides a base class that can be extended to develop ajax enabled applications. It leverages the drupal jquery 
ajax libraries to provide loading of initial reports.  The simplest implementation of the ajax controller appears as follows:
 
````php
namespace \Drupal\myexample\Controller;

use \Drupal\forena\Controller\AjaxPageControllerBase; 

class MyAjaxController extends AjaxPageControllerBase {

// Override a class constant to specifiy which report will be used to provide aplication view structure
const LAYOUT='dashboard'

// Specify the default action when the user visits /myexampledashboard
const DEFAULT_ACTION='myreport'

public function route($action) {
  switch ($action) {
    case "myreport": 
      // render the forena report myreport into the section of the dashboard with an id of myexample-main
      $this->report('myexample-main', 'myreport'); 
      break; 
  }
}
````

### Ajax Controller Methods
The following methods are included in the AjaxPageControllerBase class: 

* **initLayout** - Intialize the layout of a dashboard on a non-ajax page load
* **report** - Render a forena report into a section of the dashboard
* **modalReport** - Place a forena report into a modal dialog
* **processForm** - Handle all actions that are covered by forms. 
* **getForm** - Render a drupal form into a section of the dashboard
* **getModalForm** - Render the drupal form into a modal dialog
* **preventAction** - Prevent the controller from processing the $action during form submission on a route. 
* **setUrl** - Alter the url of controller so that back buttons work. 
* **addCommand** - Add an ajax command to the stack so that custom ajax commands may be called. 

## Ajax Forms 
Forms implemented within the controller should extend the AjaxFromBase class provided by forena. The simplest 
implementation of the form should be as follows: 

````php
namespace Drupal\myexample\Form\MyExampleDemoForm; 

use Drupal\Core\Form\FormStateInterface;
use Drupal\forena\Form\AjaxFormBase;
use Drupal\myexample\Controller\MyAjaxController;


class MyExampleDemoForm extends AjaxFormBase {

  // You must implement a site unique form ID for drupal forms to function
  public function getFormID() {
    return 'myexample_my_example_demo_form'; 
  }
  
  // Build the form the drupal way
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the controller that is calling the form 
    $controller = MyAjaxController::service()
    
    // Create controls in the form
    $form['submit'] = ['#type' => 'submit', '#value' => 'Submit'];
    
    // Bind the controller to the form so that ajax submit behaviors get triggered properly. 
    $this->bindAjaxForm($controller, $form, $form_state); 
    return $form;  
  }
  
  public function submitForm(array $form, FormStateInterface $form_state) {
    // Your form submit handlers go here. 
  } 
  
}
````
