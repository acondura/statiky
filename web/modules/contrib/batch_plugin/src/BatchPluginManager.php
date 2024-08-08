<?php

namespace Drupal\batch_plugin;

use Drupal\batch_plugin\Plugin\Derivative\BatchPluginQueueWorker;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * BatchApiPlugin plugin manager.
 */
class BatchPluginManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * Constructs BatchPluginManager object.
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
      'Plugin/BatchPlugin',
      $namespaces,
      $module_handler,
      'Drupal\batch_plugin\BatchPluginInterface',
      'Drupal\batch_plugin\Annotation\BatchPlugin'
    );
    $this->alterInfo('batch_api_plugin_info');
    $this->setCacheBackend($cache_backend, 'batch_plugins');
  }

  /**
   * {@inheritDoc}
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    foreach ($definitions as $id => &$definition) {
      $definition['queue_name'] = BatchPluginQueueWorker::QUEUE_NAME_PREFIX . $id;
    }
    $this->setCachedDefinitions($definitions);
    return $definitions;
  }

  /**
   * Get any batch plugins defined by type.
   *
   * @param mixed $type
   *   The type of definition required.
   * @param bool $include_entities
   *   Whether to include definitiions from entities.
   *
   * @return array
   *   The plugins.
   */
  public function getDefinitionsByType($type, $include_entities = TRUE) : array {
    if (is_string($type)) {
      $type = [$type];
    }
    $typed_definitions = [];
    // First get all the standard plugins.
    $definitions = $this->getDefinitions();
    foreach ($definitions as $id => $definition) {
      if (!empty($definition['processors'])) {
        $processors = array_map('trim', explode(',', $definition['processors']));
        if (count(array_intersect($type, $processors)) > 0) {
          $typed_definitions[$id] = $definition;
        }
      }
    }
    // Check batch plugin entities.
    if ($include_entities && $this->moduleHandler->moduleExists('batch_plugin_entity')) {
      $entities = \Drupal::entityTypeManager()->getStorage('batch_plugin_entity')->loadMultiple();
      /** @var \Drupal\batch_plugin_entity\BatchPluginEntityInterface $entity */
      foreach ($entities as $id => $entity) {
        $settings = $entity->get('settings') ?? [];
        if (isset($settings['processor_plugin_id']) && in_array($settings['processor_plugin_id'], $type) && $entity->status()) {
          $definition = $entity->getPlugin()->getPluginDefinition();
          $definition['label'] = $this->t('Entity:') . ' ' . $entity->label();
          $definition['configuration'] = $settings;
          $definition['queue_name'] = BatchPluginQueueWorker::QUEUE_NAME_PREFIX . '_entity_' . $id;
          $typed_definitions['_entity_' . $id] = $definition;
        }
      }
    }
    \Drupal::moduleHandler()->alter('batch_plugin_definitions', $typed_definitions);
    return $typed_definitions;
  }

}
