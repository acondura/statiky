<?php

namespace Drupal\batch_plugin\Commands;

use Drupal\batch_plugin\BatchPluginManager;
use Drupal\batch_plugin\ProcessorPluginManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class ProcessBatchPluginCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The batch plugin manager.
   *
   * @var \Drupal\batch_plugin\BatchPluginManager
   */
  protected $pluginManager;

  /**
   * The batch processor plugin manager.
   *
   * @var \Drupal\batch_plugin\ProcessorPluginManager
   */
  protected $processorPluginManager;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $loggerChannelFactory;

  /**
   * Constructs a new command.
   *
   * @param \Drupal\batch_plugin\BatchPluginManager $plugin_manager
   *   The batch plugin manager.
   * @param \Drupal\batch_plugin\ProcessorPluginManager $processor_plugin_manager
   *   The batch processor plugin manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   A logger instance.
   */
  public function __construct(BatchPluginManager $plugin_manager, ProcessorPluginManager $processor_plugin_manager, LoggerChannelFactoryInterface $logger_channel_factory) {
    parent::__construct();
    $this->pluginManager = $plugin_manager;
    $this->processorPluginManager = $processor_plugin_manager;
    $this->loggerChannelFactory = $logger_channel_factory;
  }

  /**
   * Run a batch plugin.
   *
   * @param string $plugin_id
   *   The batch plugin ID.
   * @param string|null $processor_plugin_id
   *   Optional parameter to override the processor plugin ID.
   *
   * @command batch_plugin:process
   * @aliases bpp
   * @group
   *
   * @usage batch_plugin:process example_batch_plugin
   */
  public function processBatchPlugin(string $plugin_id, string $processor_plugin_id = 'drush') {
    if (!$this->pluginManager->hasDefinition($plugin_id)) {
      $this->output->writeln('<error>Batch plugin not found</error>');
      return;
    }
    if (!$this->processorPluginManager->hasDefinition($processor_plugin_id)) {
      $this->output->writeln('<error>Processor plugin not found</error>');
      return;
    }
    /** @var \Drupal\batch_plugin\BatchPluginInterface $plugin */
    $plugin = $this->pluginManager->createInstance($plugin_id);
    $plugin->process($processor_plugin_id);
    drush_backend_batch_process();
    $this->output()->writeln($this->t('Batch operations end.'));
  }

}
