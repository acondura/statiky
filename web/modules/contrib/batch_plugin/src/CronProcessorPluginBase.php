<?php

namespace Drupal\batch_plugin;

use Cron\CronExpression;

/**
 * Base class for processor plugins.
 */
abstract class CronProcessorPluginBase extends QueueProcessorPluginBase implements CronProcessorPluginInterface {

  const DEFAULT_CRON_EXPRESSION = '@daily';

  /**
   * {@inheritDoc}
   */
  public function isCronDue(): bool {
    $log = $this->logger->getLog($this->queueId);
    if (!empty($log)) {
      if ($log['running']) {
        return FALSE;
      }
    }
    if (empty($log)) {
      // Assume this is overdue.
      return TRUE;
    }
    $cron = new CronExpression($this->getCronExpression());
    // Set the DateTime to the last start this job was started.
    $dateTime = new \DateTime();
    $dateTime->setTimestamp($log['start_time']);
    $next_run = $cron->getNextRunDate($dateTime);
    return $next_run->getTimestamp() < time();
  }

  /**
   * {@inheritDoc}
   */
  public function getCronExpression(): string {
    if (empty($this->batchPlugin)) {
      return static::DEFAULT_CRON_EXPRESSION;
    }
    $plugin_config = $this->batchPlugin->getConfiguration();
    if (isset($plugin_config['cronexpression'])) {
      return $plugin_config['cronexpression'];
    }
    $expression = $this->batchPlugin->getPluginDefinition()['cronexpression'] ?? static::DEFAULT_CRON_EXPRESSION;
    return str_replace('\\', '', $expression);
  }

}
