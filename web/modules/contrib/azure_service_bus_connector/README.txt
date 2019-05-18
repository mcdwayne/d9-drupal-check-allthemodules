-- Summary --

The main purpose of the Azure Service Bus Connector is to handle all the connection details and provide a service that can be injected and utilized from within a custom module to gain a connection to the Azure Service Bus instance. Once the service bus instance is available, it can be acted upon to manage queues, send messages, create topics, etc.

-- REQUIREMENTS --

No additional modules are explicitly required for the module to function, but there is a requirement to the Azure API for PHP, which will be pulled in via composer during the installation step.

-- INSTALLATION --

* Install as usual, see https://www.drupal.org/documentation/install/modules-themes/modules-8
  for further information.
* The module should be added to the docroot via `composer require drupal/azure_service_bus_connector`. This will also pull in the Azure SDK for PHP library.

-- USAGE --

- Add Azure API credentials and endpoint values into the configuration form provided by the module, or store them outside the Drupal docroot.
-- https://docs.acquia.com/resource/secrets/
- Test the connection string which verifies that the service is ready for use
- Inject the AzureApi service class into your module.
- Make use of the service bus proxy instance via `$serviceBus = $this->azureApi->getServiceBus();`
- Perform service bus operations, such as listing the queues `$queues = $serviceBus->listQueues();`

More info:
https://github.com/Azure/azure-sdk-for-php

-- EXAMPLES --

To see an example of the service in action, see the module's configuration form and how the AzureApi service class is being injected into the form class.
