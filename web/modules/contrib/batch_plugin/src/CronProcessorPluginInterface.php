<?php

namespace Drupal\batch_plugin;

/**
 * Interface for processor plugins.
 */
interface CronProcessorPluginInterface extends QueueProcessorPluginInterface {

  const STATUS_CRON_NOT_DUE = 'cron_not_due';

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
   * Check if CRON is due.
   *
   * @return bool
   *   Whether CRON is due.
   */
  public function isCronDue(): bool;

  /**
   * Get the CRON expression for the batch plugin.
   *
   * @return string
   *   The CRON expression.
   */
  public function getCronExpression(): string;

}
