<?php

namespace Drupal\batch_plugin;

use Drupal\batch_plugin\Logger\BatchPluginCronLog;

/**
 * Base class for processor plugins.
 */
abstract class QueueProcessorPluginBase extends ProcessorPluginBase implements QueueProcessorPluginInterface {

  const DEFAULT_CRON_EXPRESSION = '@daily';

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Queue ID.
   *
   * @var string
   */
  protected $queueId = '';

  /**
   * The logger.
   *
   * @var \Drupal\batch_plugin\Logger\BatchPluginCronLog
   */
  protected BatchPluginCronLog $logger;

  /**
   * {@inheritDoc}
   */
  public function getQueueId(): string {
    return $this->queueId;
  }

  /**
   * {@inheritDoc}
   */
  public function setQueueId($id): QueueProcessorPluginInterface {
    $this->queueId = $id;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = \Drupal::service('logger.batch_plugin_cron');
    $this->database = \Drupal::service('database');
  }

  /**
   * {@inheritDoc}
   */
  public function breakLock(): void {
    $this->logger->breakLock($this->queueId);
  }

  /**
   * {@inheritDoc}
   */
  public function isQueueBuilding(): bool {
    $log = $this->logger->getLog($this->getQueueId());
    if (!empty($log)) {
      if ($log['running']) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function addOperations(BatchPluginInterface $batch_plugin, array|\DrushBatchContext $previous_context = []) {
    if (!isset($this->queueId)) {
      $this->queueId = $batch_plugin->getPluginDefinition()['queue_name'];
    }
    if (!isset($this->queueId)) {
      throw new \Exception('No Queue ID specified');
    }
    $this->batchPlugin = $batch_plugin;
    if ($this->isQueueBuilding()) {
      throw new \Exception('The plugin is already running');
    }
    try {
      $start_time = time();
      $this->logger->log($this->queueId, $start_time, 0, 1);
      parent::addOperations($batch_plugin, $previous_context);
      $this->setupQueueContext();
      $queue = \Drupal::queue($this->queueId);
      foreach ($this->operations as $item) {
        $queue->createItem($item);
      }
      $finsihed_item = [
        'batch_plugin_id' => $batch_plugin->getPluginId(),
        'batch_plugin_configuration' => $batch_plugin->getConfiguration(),
        'operation_callback' => 'finished',
        'operation_payload' => [],
        'context' => $previous_context,
        'operations_count' => count($this->operations),
        'finished_callback' => TRUE,
      ];
      $queue->createItem($finsihed_item);
      $end_time = time();
      $this->logger->log($this->queueId, $start_time, $end_time, 0);
      return ProcessorPluginInterface::STATUS_OPERATIONS_ADDED;
    }
    catch (\Throwable $ex) {
      $this->logger->breakLock($this->queueId);
      throw $ex;
    }
  }

  /**
   * Setup the queue context in the database.
   *
   * @throws \Exception
   */
  protected function setupQueueContext() {
    $this->database->delete('batch_plugin_queue_context')
      ->condition('queue_id', $this->queueId)
      ->execute();
    $this->database->insert('batch_plugin_queue_context')
      ->fields([
        'queue_id' => $this->queueId,
        'operations' => \GuzzleHttp\json_encode($this->operations),
      ])
      ->execute();
  }

}
