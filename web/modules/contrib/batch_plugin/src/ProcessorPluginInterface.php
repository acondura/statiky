<?php

namespace Drupal\batch_plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface for processor plugins.
 */
interface ProcessorPluginInterface extends ConfigurableInterface, PluginFormInterface, PluginInspectionInterface, HelpfulDataInterface {

  const STATUS_NO_OPERATIONS = 'no-operations';
  const STATUS_OPERATIONS_ADDED = 'operations_added';

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

  /**
   * Process a batch plugin.
   *
   * @param \Drupal\batch_plugin\BatchPluginInterface $plugin
   *   The plugin.
   */
  public function process(BatchPluginInterface $plugin) : void;

  /**
   * Add operation to the processor.
   *
   * @param \Drupal\batch_plugin\BatchPluginInterface $batch_plugin
   *   The batch plugin.
   * @param array|\DrushBatchContext $previous_context
   *   Any context needed for the batch plugin, e.g. batch API's context.
   *
   * @return mixed
   *   The return;
   */
  public function addOperations(BatchPluginInterface $batch_plugin, array|\DrushBatchContext $previous_context = []);

}
