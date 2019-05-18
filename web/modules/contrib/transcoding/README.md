# Transcoding API for Drupal

A fresh Drupal 8 implementation of a vendor-agnostic video transcoding
API, integrating with the core Media module.

The Drupal 7 version of Transcoding explicitly implemented with Codem,
which is used as the D8 reference implementation.

## Workflow overview

Transcoding jobs are created, referencing a plugin which provides the provider-
specific logic. It is the plugin's responsibility to arrange for the transcoding,
including setting/selecting any provider options such as pipelines or job types.
