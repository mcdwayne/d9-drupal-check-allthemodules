# Extending Healthcheck

Healthcheck provides many checks out of the box, but you can also write your
own easily. 

## Creating a custom check

Healthcheck uses the Drupal 8 plugin system and defines a `healthcheck` plugin
type. 

```php

use Drupal\healthcheck\Plugin\Healthcheck;

/**
 * @Healthcheck(
 *  id = "my_custom_check",
 *  label = @Translation("My Custom Check"),
 *  description = "My own custom check!",
 *  tags = {
 *   "list",
 *   "of",
 *   "tags",
 *  }
 * )
 */
class MyCustomCheck extends HealthcheckPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFindings() {
    $findings = [];
    
    // Do your check here.
    
    return $findings;
  }
}

```

## Findings

Each `healthcheck` plugin can return zero or more `Finding` objects. Each 
Finding has the following:

* **key**: a unique identifier similar to a machine name.
* **status**: a numeric status provided by `FindingStatus`.
* **label**: a brief name of the finding.
* **message**: a message describing the finding people to read.
* **data**: an arbitrary key-value store. 

## Finding messages

Sometimes you want to write a lot in a finding message. This can be cumbersome
in PHP. Instead, you can write a Finding message file:

1. In your module, create a `healthcheck_finding` subdirectory.
2. Create a `your_finding_key.yml` file in the format below.
3. Save the file, rebuild cache.

```yaml
id: your_finding_key
check: your_custom_check_name
finding_critical:
  label: Critical finding from my custom check!
  message: >
    Message goes here.
finding_action_requested:
  label: Action is requested from my custom check
  message: >
    Message goes here.
finding_needs_review:
  label: My custom check found something you should look at
  message: >
    Message goes here.
finding_no_action_required:
  label: Everything is good
  message: >
    Message goes here.
finding_not_performed:
  label: Could not perform check
  message: >
    Message goes here.
```

## Events

Healthcheck relies on the Drupal 8 event system to broadcast the following:

* `HealthcheckEvents::CHECK_RUN`: A new Healthcheck report was run.
* `HealthcheckEvents::CHECK_CRITICAL`: One or more critical findings were found.
* `HealthcheckEvents::CHECK_CRON`: A report was generated in the background.

For general notifications, it is recommended to use `CHECK_CRON`, as 
`CHECK_RUN` is called during ad hoc reports.