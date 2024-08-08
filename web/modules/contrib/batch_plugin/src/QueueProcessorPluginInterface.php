<?php

namespace Drupal\batch_plugin;

/**
 * Interface for processor plugins.
 */
interface QueueProcessorPluginInterface extends ProcessorPluginInterface {

  const STATUS_QUEUE_ALREADY_BUILDING = 'queue_already_building';

  /**
   * Get the Cron plugin ID.
   *
   * @return string
   *   The Cron plugin ID.
   */
  public function getQueueId(): string;

  /**
   * Set the Cron Plugin ID.
   *
   * @param string $id
   *   The Cron plugin ID.
   *
   * @return $this
   */
  public function setQueueId($id): QueueProcessorPluginInterface;

  /**
   * Break a lock.
   */
  public function breakLock(): void;

  /**
   * Check if queue plugin is running.
   *
   * @return bool
   *   Whether the queue plugin is already running.
   */
  public function isQueueBuilding(): bool;

}
