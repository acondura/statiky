<?php

namespace Drupal\batch_plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Process Batch Interface.
 */
interface ProcessorPluginManagerInterface extends PluginManagerInterface {

  /**
   * Process a plugin.
   *
   * @param \Drupal\batch_plugin\BatchPluginInterface $batch_plugin
   *   The batch plugin.
   * @param \Drupal\batch_plugin\ProcessorPluginInterface|string|null $processor_plugin
   *   Optional parameter to override the processor plugin.
   * @param mixed $helpful_data
   *   Any data that the processor plugin could use.
   *
   * @return mixed
   *   The results.
   */
  public function processBatchPlugin(BatchPluginInterface $batch_plugin, ProcessorPluginInterface|string|null $processor_plugin = NULL, $helpful_data = NULL): mixed;

  /**
   * Add operations to a batch.
   *
   * @param \Drupal\batch_plugin\BatchPluginInterface $batch_plugin
   *   The batch plugin.
   * @param \Drupal\batch_plugin\ProcessorPluginInterface $processor
   *   The processor plugin.
   * @param array|\DrushBatchContext $previous_context
   *   Any existing context, e.g. from Batch API. Only used when appending.
   */
  public function addBatch(BatchPluginInterface $batch_plugin, ProcessorPluginInterface $processor, array|\DrushBatchContext $previous_context = []): void;

  /**
   * Get a list of plugin options, e.g. for Form API select elements.
   *
   * @param \Drupal\batch_plugin\BatchPluginInterface|null $batchPlugin
   *   The batch plugin to check against with processor annotations.
   *
   * @return array
   *   The options.
   */
  public function getProcessorOptions(BatchPluginInterface $batchPlugin = NULL): array;

}
