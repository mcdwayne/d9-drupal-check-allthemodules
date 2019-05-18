<?php
/**
 * @file
 * Formal description of transaction handling and Entity controller functions.
 *
 * N.B
 * The mcapi_transaction entity has a children property, which contains
 * transactions with a parent value. The parent and the children are saved side
 * by side in the database, with a 'parent xid' property. Of entities with the
 * same serial number, one should have a 'parent' property of 0, and all the
 * others should have that entities xid as their parent. The functions here all
 * assume the transaction is fully loaded, with children, unless otherwise
 * stated.
 *
 * When saving transactions you can ->validate() first and handle the ensuing
 * $violations, or just save() and hope nothing breaks.
 */

/**
 * HOOKS.
 * 
 * @todo
 */
