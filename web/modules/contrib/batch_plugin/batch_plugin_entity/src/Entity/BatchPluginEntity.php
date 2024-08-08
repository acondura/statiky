<?php

namespace Drupal\batch_plugin_entity\Entity;

use Drupal\batch_plugin\BatchPluginInterface;
use Drupal\batch_plugin\CronProcessorPluginInterface;
use Drupal\batch_plugin\Plugin\Derivative\BatchPluginQueueWorker;
use Drupal\batch_plugin_entity\BatchPluginEntityCollection;
use Drupal\batch_plugin_entity\BatchPluginEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the batch plugin entity type.
 *
 * @ConfigEntityType(
 *   id = "batch_plugin_entity",
 *   label = @Translation("Batch plugin"),
 *   label_collection = @Translation("Batch plugins"),
 *   label_singular = @Translation("batch plugin"),
 *   label_plural = @Translation("batch plugins"),
 *   label_count = @PluralTranslation(
 *     singular = "@count batch plugin",
 *     plural = "@count batch plugins",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\batch_plugin_entity\BatchPluginEntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\batch_plugin_entity\Form\BatchPluginEntityForm",
 *       "edit" = "Drupal\batch_plugin_entity\Form\BatchPluginEntityForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "batch_plugin_entity",
 *   admin_permission = "administer batch_plugin_entity",
 *   links = {
 *     "collection" = "/admin/structure/batch-plugin",
 *     "edit-form" = "/admin/structure/batch-plugin/{batch_plugin_entity}/edit",
 *     "delete-form" = "/admin/structure/batch-plugin/{batch_plugin_entity}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "plugin",
 *     "settings"
 *   }
 * )
 */
class BatchPluginEntity extends ConfigEntityBase implements BatchPluginEntityInterface {

  /**
   * The batch plugin ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The plugin instance settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * The batch plugin label.
   *
   * @var string
   */
  protected $label;

  /**
   * The batch_plugin description.
   *
   * @var string
   */
  protected $description;

  /**
   * The plugin instance ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin collection that holds the batch plugin for this entity.
   *
   * @var \Drupal\batch_plugin_entity\BatchPluginEntityCollection
   */
  protected $pluginCollection;

  /**
   * Gets the plugin.
   *
   * @return \Drupal\batch_plugin\BatchPluginInterface
   *   The plugin.
   */
  public function getPlugin(): BatchPluginInterface {
    return $this->getPluginCollection()->get($this->plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections(): array {
    return [
      'settings' => $this->getPluginCollection(),
    ];
  }

  /**
   * Encapsulates the creation of the batch plugin's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The batch's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new BatchPluginEntityCollection(\Drupal::service('plugin.manager.batch_plugin'), $this->plugin, $this->get('settings'), $this->id());
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId(): string {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update) {
      $settings = $this->get('settings');
      if (isset($settings['processor_plugin_id'])) {
        /** @var \Drupal\batch_plugin\ProcessorPluginManager $manager */
        $manager = \Drupal::service('plugin.manager.batch_plugin_processor');
        $plugin = $manager->createInstance($settings['processor_plugin_id']);
        if (!$plugin instanceof CronProcessorPluginInterface) {
          $this->deleteQueue();
        }
      }
      // Clear the queue worker plugin cache so that derivatives will be found.
      \Drupal::service('plugin.manager.queue_worker')->clearCachedDefinitions();
    }
  }

  /**
   * {@inheritDoc}
   */
  public function delete() {
    $this->deleteQueue();
    parent::delete();
  }

  /**
   * Delete a queue, if one exists, for this batch plugin entity.
   */
  protected function deleteQueue() {
    try {
      \Drupal::queue(BatchPluginQueueWorker::QUEUE_NAME_ENTITY_PREFIX . $this->id())
        ->deleteQueue();
    }
    catch (\Throwable $ex) {
      $a = 1;
    }
  }

}
