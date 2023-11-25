<?php

namespace Drupal\eca_queue\Exception;

/**
 * Thrown when an enqueued task is not yet due for processing.
 */
class NotYetDueForProcessingException extends \Exception {}
