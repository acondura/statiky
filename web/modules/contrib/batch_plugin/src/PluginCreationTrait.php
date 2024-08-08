<?php

namespace Drupal\batch_plugin;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;

/**
 * Plugin creation trait.
 */
trait PluginCreationTrait {

  /**
   * Create a batch plugin.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $configuration
   *   The configuration.
   *
   * @return \Drupal\batch_plugin\BatchPluginInterface
   *   The plugin.
   */
  public static function createBatchPlugin($plugin_id, array $configuration): BatchPluginInterface {
    return static::createPlugin($plugin_id, $configuration, 'plugin.manager.batch_plugin');
  }

  /**
   * Create a batch plugin.
   *
   * @param string $plugin_id
   *   The plugin ID.
   *
   * @return \Drupal\batch_plugin\ProcessorPluginInterface
   *   The plugin.
   */
  public static function createProcessorPlugin($plugin_id): ProcessorPluginInterface {
    return static::createPlugin($plugin_id, [], 'plugin.manager.batch_plugin_processor');
  }

  /**
   * Create a plugin.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $configuration
   *   The configuration.
   * @param string $service_id
   *   The plugin manager service ID.
   *
   * @return \Drupal\batch_plugin\BatchPluginInterface|\Drupal\batch_plugin\ProcessorPluginInterface
   *   The plugin.
   */
  protected static function createPlugin($plugin_id, array $configuration, $service_id): BatchPluginInterface|ProcessorPluginInterface {
    /** @var \Drupal\plugin\Plugin\DataType\PluginInspectionInterface $manager */
    $manager = \Drupal::service($service_id);
    /** @var \Drupal\batch_plugin\BatchPluginInterface $plugin */
    $plugin = $manager->createInstance($plugin_id, $configuration);
    return $plugin;
  }

  /**
   * Retrieves the plugin form for a given plugin..
   *
   * @param \Drupal\batch_plugin\PluginFormInterface $plugin
   *   The batch plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for the batch plugin.
   */
  protected static function getPluginForm(PluginFormInterface $plugin) {
    /** @var \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_factory */
    $plugin_form_factory = \Drupal::service('plugin_form.factory');
    if ($plugin instanceof PluginWithFormsInterface) {
      return $plugin_form_factory->createInstance($batch, 'configure');
    }
    return $plugin;
  }

}
