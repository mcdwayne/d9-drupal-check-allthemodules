# Frequently Asked Questions

## How do I alter data being sent to a web service before it is sent?

Implement a WSEncoder plugin and configure your WSCall to use it.

## How do I parse and restructure the data returned by a web service call?

Implement a WSDecoder plugin and configure your WSCall to use it.

## How do I make WSData support a new web service protocol like SOAP or XML-RPC?

Implement a WSConnector plugin and configure your WSServer to use it.
