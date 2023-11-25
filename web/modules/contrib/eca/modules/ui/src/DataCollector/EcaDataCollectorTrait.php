<?php

namespace Drupal\eca_ui\DataCollector;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\eca\ConfigurableLoggerChannel;

trait EcaDataCollectorTrait {

  /**
   * The logger channel.
   *
   * @var \Drupal\eca\ConfigurableLoggerChannel
   */
  protected ConfigurableLoggerChannel $logger;

  /**
   * EcaDataCollector constructor.
   *
   * @param \Drupal\eca\ConfigurableLoggerChannel $logger
   *   The logger channel.
   */
  public function __construct(ConfigurableLoggerChannel $logger) {
    $this->logger = $logger;
  }

  /**
   * Gets the number of log entries.
   *
   * @return string
   *   The number of log entries.
   */
  public function getNumberOfLogEntries(): string {
    return count($this->data);
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'eca';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string {
    return $this->t('ECA');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): string {
    return 'iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAADv0lEQVRYR+1We0jTURT+5qMUH5SaJmlZWpLVEBMyk9R8oM00DQyKpGKRJkX2gJ6GQUWlaZYaZKQzzYZEZEZpOdHAZWkKim8zWkqUbysycZ17aWubGhVB/+zA/vide8/jfuec70xglJSkxH8UgT4BPQJ6BPQITIXAER8fWJuaqulJqVTiWm0tugYGuM5l9mycCQiAs5UV/x748gWp1dV42NHBv81nzMA2oRCZL15AIBD8kuYmEZEhGXw6fhzSpiYMf/3KjRlVXn3+HK19fTAxNERTfDwqurvxoL2dDpVwpoQS/fywLicHL3t7sXXFCtzcuBFL0tPRPTT0dwksy8hA548Xa3rwd3KCJCoK81NTKfZPFs+hgIrhYZwoL0dRdDTCXV1x6PFjpNfU/NsE1i9ejItBQViWmanlOC0kBOMTEzglk6Hn4EHca2mBg6UlAiSSP0vAyMAAo8eOQZiVhTaCXFdElEBycDDcCCHNLeZmY8OvLp0zB+cCAxFy6xYvlWNKCj5Sj0wnWj3A2iVLJMIODw9UvXmDiNu38enbN7Vt4KJFKNi0CbNMTFBHtRbl56NPx3leZCR6R0ZwuKwMr2JjcVkuR25Dw+8lwIIHOzsj6s4dpIeGcqOyzk61sXjlSu4wu64OdzdvhlyhwEmCXCUzqUEZ/GEFBaims0RfX7jb2SFKKuVXVjs4YI2jI5JpYlSiRoC9viEuDh39/Ygmg1PU1bs9PVGqSoAaLowaK7KwEDKagAxKUEDl2lNSona23sUFWRs2wIk1KGlZ8MqdO2GfnMyRXGJtDdn27ZDU1+Po06fcTqsEdmZmeBITgwkKNtfCAqFURwa1Sk77+0NM5XlFOp8FCxCUm4uanh71+XUK7rdwIcq7utS6GHd3lLS14f3oKNe5Uq+sJdukigqcqarSToBdsDc3R3dCAkLy8vhLdeWwtzf2eXnx4C0aTcr44x3BX9zaig+fP3MzA/qtItjZNNxtbuY6D3t7+NIoJzx6hAxGVLpMqCKi6XhgujFcR07zqUEdL13CuAY/+NJrpcQL86gMy6kkDOGzlZVIoT5gLPnHCQQQxDciIuCUlqYFzhXqCRMjI+wqLtbSswcpCJmtRUXop4kRUhJsKlQUPW0C4vv3+Tip5PXgIN8F5sbGaNm7l1P1Q6JixoaMii8QN4TRWD57+3ZS2bLDwzE6Nob9BLuuTEqATYNcLIaVxjJiRqy2B0pLub3Q1hbniQ3ZMmL32TJikBY2Nk65fBh9b6H9oIsO86X/V6xHQI+AHoH/jsB3PoCy0Nz5DDgAAAAASUVORK5CYII=';
  }

  protected function doCollect(): void {
    foreach ($this->logger->getDataCurrentRequest() as $item) {
      foreach ($item[2] as $key => $value) {
        $item[2][$key] = strip_tags((string) (new FormattableMarkup($value, $item[3])));
      }
      $this->data[] = [
        'message' => $item[1],
        'tokens' => implode('<br>', $item[2]),
      ];
    }
  }

}
