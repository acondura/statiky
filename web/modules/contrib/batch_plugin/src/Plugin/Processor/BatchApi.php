<?php

namespace Drupal\batch_plugin\Plugin\Processor;

use Drupal\batch_plugin\BatchPluginInterface;
use Drupal\batch_plugin\ProcessorPluginBase;
use Drupal\batch_plugin\ProcessorPluginInterface;
use Drupal\Core\Batch\BatchBuilder;

/**
 * Plugin implementation of the processor.
 *
 * @Processor(
 *   id = "batch_api",
 *   label = @Translation("Batch API"),
 *   description = @Translation("Batch API.")
 * )
 */
class BatchApi extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function addOperations(BatchPluginInterface $batch_plugin, array|\DrushBatchContext $previous_context = []) {
    parent::addOperations($batch_plugin, $previous_context);
    $batch = $this->setupBatchBuilder($batch_plugin);
    batch_set($batch);
    return ProcessorPluginInterface::STATUS_OPERATIONS_ADDED;
  }

  /**
   * Setup the batch builder.
   *
   * @param \Drupal\batch_plugin\BatchPluginInterface $batch_plugin
   *   The batch plugin.
   *
   * @return array
   *   The batch builder array.
   */
  protected function setupBatchBuilder(BatchPluginInterface $batch_plugin) {
    // Remove array keys as they aren't supported by Batch API.
    foreach ($this->operations as &$operation) {
      $batch_operation = [];
      foreach ($operation as $datum) {
        $batch_operation[] = $datum;
      }
      $batch_operations[] = $batch_operation;
    }
    $batchBuilder = new BatchBuilder();
    $batchBuilder->setTitle($batch_plugin->getBatchTitle())
      ->setErrorMessage($batch_plugin->getBatchErrorMessage());
    $finishedCallback = $batch_plugin->getFinishedStaticCallback();
    if (!empty($finishedCallback)) {
      $batch_plugin->setFinishCallback($finishedCallback);
    }
    else {
      $batchBuilder->setFinishCallback([self::class, 'batchFinished']);
    }
    $callback = [self::class, 'processOperation'];
    foreach ($batch_operations as $item) {
      $batchBuilder->addOperation($callback, $item);
    }
    return $batchBuilder->toArray();
  }

  /**
   * Batch API callback.
   *
   * @param string $batch_plugin_id
   *   The plugin id.
   * @param array $batch_plugin_configuration
   *   The processor plugin.
   * @param string $operation_callback
   *   The plugin callback function name.
   * @param mixed $operation_payload
   *   The item.
   * @param array|\DrushBatchContext $context
   *   Any previous batch API context.
   * @param int $operations_count
   *   The total count of operations.
   * @param array|\DrushBatchContext $batch_api_context
   *   The batch API context.
   */
  public static function processOperation(string $batch_plugin_id, array $batch_plugin_configuration, $operation_callback, $operation_payload, array|\DrushBatchContext $context, $operations_count, array|\DrushBatchContext &$batch_api_context): void {
    if (!isset($batch_api_context['sandbox']['progress'])) {
      $batch_api_context['sandbox']['progress'] = 0;
      $batch_api_context['sandbox']['max'] = $operations_count;
    }
    $batch_plugin = static::createBatchPlugin($batch_plugin_id, $batch_plugin_configuration);
    $batch_plugin->setProcessorId('batch_api');
    if (!isset($batch_api_context['results']['plugin_id'])) {
      $batch_api_context['results']['plugin_id'] = $batch_plugin_id;
      $batch_api_context['results']['configuration'] = $batch_plugin_configuration;
    }
    if (empty($context)) {
      $batch_plugin->$operation_callback($operation_payload, $batch_api_context);
    }
    else {
      $batch_plugin->$operation_callback($operation_payload, $context, $batch_api_context);
    }
    $batch_api_context['sandbox']['progress']++;
    if ($batch_api_context['sandbox']['progress'] != $batch_api_context['sandbox']['max']) {
      $batch_api_context['finished'] = $batch_api_context['sandbox']['max'] / $batch_api_context['sandbox']['progress'];
    }
  }

  /**
   * Process files finished callback.
   *
   * @param bool $success
   *   Success state of the operation.
   * @param array $results
   *   Array of results for post-processing.
   * @param array $operations
   *   Operations array.
   */
  public static function batchFinished(bool $success, array $results, array $operations): void {
    $plugin = static::createBatchPlugin($results['plugin_id'], $results['configuration']);
    $plugin->finished($success, $results, $operations);
  }

}
