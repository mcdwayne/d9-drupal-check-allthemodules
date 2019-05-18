<?php

namespace Drupal\couchbasedrupal;

class CouchbaseExceptionCodes {

  /**
   * Get a list of couchbase transient error codes.
   *
   * @return string[]
   */
  public static function getTransientErrors() {
    return [
        static::SERVER_BUSSY,
        static::SERVER_OUT_OF_MEMORY,
        static::TEMPORARY_FAILURE,
        static::MISSING_KEY,
        static::NETWORK_ERROR,
        static::TEMPORARY_CLIENT_FAILURE,
        static::MISSING_NODE_IN_CLUSTER,
        static::CLUSTER_CHANGED,
        static::INCOMPLETE_PACKET,
        static::CONNECTION_REFUSED,
        static::CONNECTION_CLOSED_GRACEFULLY,
        static::CONNECTION_CLOSED_FORCEFULLY,
        static::HOST_NOT_REACHABLE
      ];
  }

  /**
   * The value requested to be incremented is not stored as a number;
   */
  const INCREMENT_REQUESTED_ON_NON_NUMBER = '3';

  /**
   * The object requested is too big to store in the server.
   */
  const OBJECT_TOO_BIG = '4';

  /**
   * The server is busy. Try again later
   */
  const SERVER_BUSSY = '5';

  /**
   * The server is out of memory. Please try again later.
   */
  const SERVER_OUT_OF_MEMORY = '8';

  /**
   * Temporary failure received from server. Try again later
   */
  const TEMPORARY_FAILURE = '11';

  /**
   * The key already exists in the server. If you have supplied a CAS then
   * the key exists with a CAS value different than specified.
   */
  const KEY_ALREADY_EXISTS = '12';

  /**
   * The key does not exist on the server.
   */
  const KEY_DOES_NOT_EXIST = '13';

  /**
   * The server which received this command claims it is not hosting this key
   */
  const MISSING_KEY = '17';

  /**
   * Client-Side timeout exceeded for operation. Inspect network conditions
   * or increase the timeout.
   */
  const NETWORK_ERROR = '23';

  /**
   * Temporary failure on the client side. Did you call lcb_connect?
   */
  const TEMPORARY_CLIENT_FAILURE = '27';

  /**
   * The node the request was mapped to does not exist in the current cluster
   * map. This may be the result of a failover.
   */
  const MISSING_NODE_IN_CLUSTER = '35';

  /**
   * The cluster map has changed and this operation could not be completed
   * or retried internally. Try this operation again
   */
  const CLUSTER_CHANGED = '42';

  /**
   * Incomplete packet was passed to forward function.
   */
  const INCOMPLETE_PACKET = '43';

  /**
   * The remote host refused the connection. Is the service up?
   */
  const CONNECTION_REFUSED = '44';

  /**
   * The remote host closed the connection.
   */
  const CONNECTION_CLOSED_GRACEFULLY = '45';

  /**
   * The connection was forcibly reset by the remote host.
   */
  const CONNECTION_CLOSED_FORCEFULLY = '46';

  /**
   * The remote host was unreachable - is your network OK?
   */
  const HOST_NOT_REACHABLE = '49';

  /**
   * A badly formatted packet was sent to the server. Please report this in a bug (happens when cache items are extremely big such as > 40Mb)
   */
  const INVALID_PACKET = '75';

}