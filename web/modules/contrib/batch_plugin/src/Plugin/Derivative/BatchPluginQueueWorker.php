<?php

namespace Drupal\batch_plugin\Plugin\Derivative;

use Drupal\batch_plugin\BatchPluginManager;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides separate queue workers for CRON batch plugins.
 */
class BatchPluginQueueWorker extends DeriverBase implements ContainerDeriverInterface {

  const QUEUE_NAME_PREFIX = 'batch_plugin_queue_worker:';

  use StringTranslationTrait;

  /**
   * The plugin manager.
   *
   * @var \Drupal\batch_plugin\BatchPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs an BatchPluginQueueWorker object.
   *
   * @param \Drupal\batch_plugin\BatchPluginManager $plugin_manager
   *   The plugin manager.
   */
  public function __construct(BatchPluginManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.batch_plugin'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = [];
    $plugins = $this->pluginManager->getDefinitionsByType(['cron', 'queue']);
    if (!empty($plugins)) {
      foreach ($plugins as $id => $plugin_definition) {
        /** @var \Drupal\batch_plugin\BatchPluginInterface $plugin */
        $plugin = $this->pluginManager->createInstance($plugin_definition['id'], $plugin_definition['configuration'] ?? []);
        $plugin_derivatives = $plugin->getQueueWorkerDerivatives();
        if (empty($plugin_derivatives)) {
          $derivatives[$id] = [
            'title' => $this->t('Batch plugin queue: @plugin_label', [
              '@plugin_label' => $plugin_definition['label'],
            ]),
          ] + $base_plugin_definition;
        }
        else {
          foreach ($plugin_derivatives as $plugin_derivative_id => $plugin_derivative) {
            $derivatives[$id] = $plugin_derivative + $base_plugin_definition;
          }
        }
        \Drupal::queue($plugin_definition['queue_name'])->createQueue();
      }
    }
    return $derivatives;
  }

}
