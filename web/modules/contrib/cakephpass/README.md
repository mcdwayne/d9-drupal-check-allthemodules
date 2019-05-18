# CakePHP Password

## Introduction

When migrating the users from CakePHP the password encryption differs from the
one in Drupal.
If resetting the passwords is not an option for you, then this module will make
an additional check for password hash using the CakePHP hashing method.

## Configuration

- First of all, to distinguish if the user's hashed password is a migrated one
from CakePHP, you must prepend the "$C$" to the password's hash.
For example:
  * Original one => d68e177bf00465b6718dbe9a3962392aa92719c7
  * Migrated one => $C$d68e177bf00465b6718dbe9a3962392aa92719c7

- Secondly, the configuration have to be added in the project's settings.php
For example:

<pre>
$settings['cakephpass'] = [
  'enabled' => TRUE,
  'salt' => 'your_salt_string',
  'type' => 'sha1_strict',
];
</pre>

Few special types are available:
  * sha1_strict   - Will use the "sha1()" function.
  * sha256_strict - Will use the "mhash()" function.
  * All others    - passed to the "hash()" function.