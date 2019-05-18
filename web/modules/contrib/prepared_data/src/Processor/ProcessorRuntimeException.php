<?php

namespace Drupal\prepared_data\Processor;

/**
 * Class ProcessorRuntimeException
 *
 * Error codes lower or equal than 1000 will remove the processor during
 * building or refreshing process to prevent further errors or damage.
 * Use an error code greater than 1000 to let the processor
 * continue on follow-up records.
 */
class ProcessorRuntimeException extends \RuntimeException {}
