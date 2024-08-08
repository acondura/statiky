<?php

namespace Drupal\batch_plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Processor plugin manager.
 */
class ProcessorPluginManager extends DefaultPluginManager implements ProcessorPluginManagerInterface {

  use PluginCreationTrait;

  /**
   * Constructs ProcessorPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Processor',
      $namespaces,
      $module_handler,
      'Drupal\batch_plugin\ProcessorPluginInterface',
      'Drupal\batch_plugin\Annotation\Processor'
    );
    $this->alterInfo('processor_info');
    $this->setCacheBackend($cache_backend, 'processor_plugins');
  }

  /**
   * {@inheritDoc}
   */
  public function processBatchPlugin(BatchPluginInterface $batch_plugin, ProcessorPluginInterface|string|null $processor_plugin = NULL, $helpful_data = NULL): mixed {
    if (empty($processor_plugin)) {
      $processor_plugin = $batch_plugin->getProcessorId();
    }
    if (is_string($processor_plugin)) {
      /** @var \Drupal\batch_plugin\ProcessorPluginInterface $processor_plugin */
      $processor_plugin = $this->createInstance($processor_plugin);
    }
    $processor_plugin->setHelpfulData($helpful_data);
    if ($processor_plugin instanceof QueueProcessorPluginInterface) {
      if (empty($processor_plugin->getQueueId())) {
        $processor_plugin->setQueueId($batch_plugin->getPluginDefinition()['queue_name']);
      }
      if ($processor_plugin->isQueueBuilding()) {
        return QueueProcessorPluginInterface::STATUS_QUEUE_ALREADY_BUILDING;
      }
    }
    if ($processor_plugin instanceof CronProcessorPluginInterface) {
      if (!$processor_plugin->isCronDue()) {
        return CronProcessorPluginInterface::STATUS_CRON_NOT_DUE;
      }
    }
    $batch_plugin->setHelpfulData($helpful_data);
    $batch_plugin->setupOperations();
    if (empty($batch_plugin->getOperations())) {
      return ProcessorPluginInterface::STATUS_NO_OPERATIONS;
    }
    return $processor_plugin->addOperations($batch_plugin);
  }

  /**
   * {@inheritDoc}
   */
  public function addBatch(BatchPluginInterface $batch_plugin, ProcessorPluginInterface $processor, array|\DrushBatchContext $previous_context = []): void {
    $processor->addOperations($batch_plugin, $previous_context);
  }

  /**
   * {@inheritDoc}
   */
  public function getProcessorOptions(BatchPluginInterface $batchPlugin = NULL): array {
    if (!empty($batchPlugin)) {
      $allowed_processors = $batchPlugin->getAllowedProcessorIds();
    }
    $options = [];
    foreach ($this->getDefinitions() as $definition) {
      if (empty($allowed_processors) || in_array($definition['id'], $allowed_processors)) {
        $options[$definition['id']] = $definition['label'];
      }
    }
    return $options;
  }

}
