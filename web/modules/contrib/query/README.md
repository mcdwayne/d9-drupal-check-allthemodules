Query
=====
A module which provides a service for creating generic `Condition` objects.

These objects may be passed to any number of functions capable of building
a system-specific query based on the passed Query object. _It is up to the
function consuming the Condition object(s) to build the actual query._

Possible Use Cases
------------------
* Use with a custom query interface for a remote API.
* Use with a custom query interface for Form API `entityreference` fields.

Examples
--------
An example of building the array of `Condition` objects & fetching results:

```php
/** @var \Drupal\query\Services\QueryInterface $q */
$q = \Drupal::service('query');
$result = example_get_events([
  $q->condition()
    ->key('type')
    ->isEqualTo('event'),
  $q->condition()
    ->key('published')
    ->is(),
  $q->condition()
    ->key('month')
    ->isBetween(2, 10)
    ->isNotIn([3,5,7]),
]);
```

An example of a function capable of building & executing a query based on
given `Condition` objects:
 
```php
/**
 * @param Condition[] $conditions
 * @return array
 */
function example_get_events(array $conditions = []) {
  $query = '';
  foreach ($conditions as $condition) {
    $groupConjunction = $condition->getGroupConjunction();
    $key = $condition->getKey();
    foreach ($condition->getRequirementGroups() as $group) {
      $conjunction = $group->getConjunction();
      foreach ($group->getRequirements() as $requirement) {
        $operator = $requirement->getOperator();
        switch ($operator) {
          case Operator::TYPE_EQUALS:
            $value = $requirement->getValue();
            // TODO: Add `$key == $value` condition to $query.
            break;

          case Operator::TYPE_NOT_EQUALS:
            $value = $requirement->getValue();
            // TODO: Add `$key != $value` condition to $query.
            break;

          case Operator::TYPE_IN:
            $values = $requirement->getValues();
            // TODO: Add `$key IN($values)` condition to $query.
            break;

          default:
            throw new \DomainException(vsprintf('Unsupported operator: %s', [
              $operator,
            ]));
        }
      }

      // TODO: Append $conjunction to $query if necessary.
    }

    // TODO: Append $groupConjunction to $query if necessary.
  }

  // TODO: Execute $query.
}
```
