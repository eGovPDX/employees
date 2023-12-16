<?php

namespace Drupal\employees\Logger\Processor;

use Drupal\monolog\Logger\Processor\AbstractRequestProcessor;
use Monolog\LogRecord;

/**
 * Injects hostname into all records.
 * 
 * Adapted from https://github.com/Seldaek/monolog/blob/main/src/Monolog/Processor/HostnameProcessor.php
 */
class HostnameProcessor extends AbstractRequestProcessor {

  /**
   * @param array $record
   *
   * @return array
   */
  public function __invoke(LogRecord $record): LogRecord {
    if ($request = $this->getRequest()) {
      $record->extra['hostname'] = $request->getHost();
    }

    return $record;
  }

}
