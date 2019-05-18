== Installation ==
Apply the core patch bundled with this module.

@code@
%> composer drupal-update
@endcode@

== Configuration ==
None

== Example usage ==

=== Using the default database connection ===
@code@

$command = \Drupal::service('transactionalphp');

$command->onCommit(function () {
  // This code will be run immediately, because no transaction is in progress.
});

$tx1 = db_transaction();

$command->onCommit(function () {
  // This code will be run when the outer transaction is committed.
});

$tx2 = db_transaction();

$command->onCommit(function () {
  // This code will not be run, because the inner transaction is committed.
});

$tx2->rollback();
unset($tx1);

$command->onCommit(function () {
  // This code will be run immediately, because no transaction is in progress.
});

@endcode@

=== Using a specific database connection ===

@code@

$connection = Database::getConnection('my_target', 'my_key');
$command = \Drupal::service('transactionalphp.factory')->get($connection);

$command->onCommit(function () {
  // This code will be run immediately, because no transaction is in progress.
});

$tx1 = $connection->startTransaction();

$command->onCommit(function () {
  // This code will be run when the outer transaction is committed.
});

$tx2 = $connection->startTransaction();

$command->onCommit(function () {
  // This code will not be run, because the inner transaction is committed.
});

$tx2->rollback();
unset($tx1);

$command->onCommit(function () {
  // This code will be run immediately, because no transaction is in progress.
});

@endcode@
