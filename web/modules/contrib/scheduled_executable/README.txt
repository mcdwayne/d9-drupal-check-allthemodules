Scheduled Executable
====================

This module allows actions and other executable plugins to be scheduled for
execution at a future time.

It uses a content entity type, scheduled_executable, to store the details of the
plugin to execute.

Scheduled executables (SE) can be queried, inspected, and deleted prior to
execution.

The group and key fields on the SE should be used to prevent duplicate items
being scheduled. For example, if scheduling a node to be unpublished, you might
set the values thus:

```
$scheduled_executable = ScheduledExecutable::create()
  ->setGroup('node:' . $node->id())
  ->setKey('unpublish')
```

You can then query for existing scheduled items with the same execution time,
group, and key to ensure a duplicate is not being created:

```
$scheduled_executable_storage = \Drupal::entityTypeManager()->getStorage('scheduled_executable');
if (!$scheduled_executable_storage->findDuplicateScheduledItems($execution_time, $group, $key)) {
  $scheduled_executable_storage->create([])
   ->setExecutionTime($execution_time);
   ->setGroup($group)
   ->setKey($key)
   // ... etc
}

```

Scheduled items with the same execution time and group are passed to a resolver
plugin. This may delete scheduled items or reorder them and can be used to
resolve conflicts. For example, one process might schedule a node to be
unpublished, and another for it to be published. Resolver plugins can use the
scheduled item's key to decide which scheduled items to keep and which to
delete.

Example:

```
// Create an instance of an executable plugin such as an action.
$action = $this->actionPluginManager->createInstance('my_action', ['config' => 'value']);

// Create a scheduled_executable.
$scheduled_executable = ScheduledExecutable::create()
  ->setExecutablePlugin('action', $action)
  ->setTargetEntity($my_entity)
  ->setExecutionTime(12345678);
$scheduled_executable->save();
```
