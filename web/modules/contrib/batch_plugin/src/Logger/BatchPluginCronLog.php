<?php

namespace Drupal\batch_plugin\Logger;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\IntegrityConstraintViolationException;

/**
 * Batch plugin Cron logger.
 */
class BatchPluginCronLog {

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a BatchPluginLog object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->connection = $database;
  }

  /**
   * Log a batch plugin CRON execution.
   *
   * @param string $cron_id
   *   The Cron or Queue ID.
   * @param int $start_time
   *   The start time.
   * @param int $end_time
   *   The end time.
   * @param int $running
   *   The running status.
   *
   * @throws \Exception
   */
  public function log($cron_id, $start_time, $end_time, $running) {
    $entry = [
      'plugin_id' => $cron_id,
      'start_time' => $start_time,
      'end_time' => $end_time,
      'uid' => \Drupal::currentUser()->id(),
      'running' => $running,
    ];

    try {
      $this->connection->insert('batch_plugin_cron_log')
        ->fields($entry)
        ->execute();
    }
    catch (IntegrityConstraintViolationException $e) {
      $updated = $this->connection->update('batch_plugin_cron_log')
        ->fields($entry)
        ->condition('plugin_id', $entry['plugin_id'])
        ->execute();
    }
  }

  /**
   * Get the log for the given Cron ID.
   *
   * @param string $cron_id
   *   The CRON plugin ID.
   *
   * @return array
   *   The log data.
   */
  public function getLog($cron_id) {
    $results = $this->connection->select('batch_plugin_cron_log', 'b')
      ->fields('b')
      ->condition('plugin_id', $cron_id)
      ->execute()
      ->fetchAssoc();
    return $results ?? [];
  }

  /**
   * Break a lock for a given Cron ID.
   *
   * @param string $cron_id
   *   The CRON ID.
   */
  public function breakLock($cron_id) {
    try {
      $this->connection->update('batch_plugin_cron_log')
        ->fields(['running' => 0])
        ->execute();
    }
    catch (\Throwable $e) {
      // Do nothing - likely no record.
    }
  }

}
