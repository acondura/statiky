<?php

namespace Drupal\batch_plugin\Plugin\QueueWorker;

use Drupal\batch_plugin\PluginCreationTrait;
use Drupal\batch_plugin\ProcessorPluginManagerInterface;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Process files from jobs.
 *
 * @QueueWorker(
 *   id = "batch_plugin_queue_worker",
 *   title = @Translation("Batch plugin queue worker."),
 *   cron = {"time" = 90},
 *   deriver = "Drupal\batch_plugin\Plugin\Derivative\BatchPluginQueueWorker"
 * )
 */
class BatchPluginQueueWorker extends QueueWorkerBase {

  use PluginCreationTrait;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The processor plugin mananger service.
   *
   * @var \Drupal\batch_plugin\ProcessorPluginManagerInterface
   */
  protected ProcessorPluginManagerInterface $processorPluginManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->processorPluginManager = \Drupal::service('plugin.manager.batch_plugin_processor');
    $this->database = \Drupal::service('database');
  }

  /**
   * {@inheritDoc}
   */
  public function processItem($data): void {
    $batch_plugin = static::createBatchPlugin($data['batch_plugin_id'], $data['batch_plugin_configuration']);
    $function = $data['operation_callback'];
    $payload = $data['operation_payload'];
    $result = $this->database->select('batch_plugin_queue_context', 'b')
      ->fields('b')
      ->execute()
      ->fetchAll();
    if (empty($result)) {
      $insert = TRUE;
      $context = [];
    }
    else {
      $result = reset($result);
      if (empty($result->context)) {
        $context = [];
      }
      else {
        $context = \GuzzleHttp\json_decode($result->context, TRUE);
      }
    }
    if (!empty($data['finished_callback'])) {
      if ($insert) {
        $operations = [];
      }
      else {
        $operations = \GuzzleHttp\json_decode($result->operations, TRUE);
      }
      $batch_plugin->finished(TRUE, $context['results'], $operations);
    }
    $batch_plugin->$function($payload, $context);
    if ($insert) {
      $this->database->insert('batch_plugin_queue_context')
        ->fields([
          'queue_id' => $this->pluginId,
          'context' => json_encode($context),
        ])
        ->execute();
    }
    else {
      $this->database->update('batch_plugin_queue_context')
        ->fields([
          'context' => json_encode($context),
        ])
        ->condition('queue_id', $this->pluginId)
        ->execute();
    }
  }

}
