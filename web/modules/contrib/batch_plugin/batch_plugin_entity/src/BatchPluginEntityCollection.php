<?php

namespace Drupal\batch_plugin_entity;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a collection of batch plugins.
 */
class BatchPluginEntityCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The batch ID this plugin collection belongs to.
   *
   * @var string
   */
  protected $batchId;

  /**
   * Constructs a new BatchPluginEntityCollection.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param string $batch_id
   *   The unique ID of the plugin entity using this plugin.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, $batch_id) {
    parent::__construct($manager, $instance_id, $configuration);

    $this->batchId = $batch_id;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id): void {
    if (!$instance_id) {
      throw new PluginException("The batch entity '{$this->batchId}' did not specify a plugin.");
    }

    try {
      parent::initializePlugin($instance_id);
    }
    catch (PluginException $e) {
      $module = $this->configuration['provider'];
      // Ignore plugins belonging to uninstalled modules, but re-throw valid
      // exceptions when the module is installed and the plugin is
      // misconfigured.
      if (!$module || \Drupal::moduleHandler()->moduleExists($module)) {
        throw $e;
      }
    }
  }

}
