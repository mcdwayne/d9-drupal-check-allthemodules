<?php

/**
 * @file
 * Test runner example for QueueStorageGenericTest and InMemoryTestQueueStorage implementation.
 * Runner file should:
 *     - Setup and assembly required components for test component to work properly
 *     - Instantiate generic test instance and pass test component as dependency
 *     - Execute test method.
 */

namespace CleverReach\Tests\GenericTests;

use CleverReach\Tests\Common\TestComponents\TaskExecution\InMemoryTestQueueStorage;

$queueStorageTest = new QueueStorageGenericTest(new InMemoryTestQueueStorage());
$queueStorageTest->test();
