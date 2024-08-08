<?php

namespace Drupal\batch_plugin\Plugin\Processor;

use Cron\CronExpression;
use Drupal\batch_plugin\BatchPluginInterface;
use Drupal\batch_plugin\CronProcessorPluginBase;
use Drupal\batch_plugin\CronProcessorPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the processor.
 *
 * @Processor(
 *   id = "cron",
 *   label = @Translation("CRON"),
 *   description = @Translation("CRON.")
 * )
 */
class Cron extends CronProcessorPluginBase implements CronProcessorPluginInterface {

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
      'cronexpression' => '@midnight',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['cronexpression'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cron Expression'),
      '#description' => $this->t('A Cron expression, e.g. */5 * * * * or a macro, e.g. @daily'),
      '#default_value' => $this->configuration['cronexpression'] ?? Cron::DEFAULT_CRON_EXPRESSION,
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $exp = $form_state->getValue('cronexpression');
    if (isset($exp)) {
      try {
        $cron = new CronExpression($exp);
      }
      catch (\Throwable $ex) {
        $form_state->setError($form['cronexpression'], $this->t('Not a valid CRON expression'));
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['cronexpression'] = $values['cronexpression'] ?? $this->defaultConfiguration()['cronexpression'];
  }

  /**
   * {@inheritDoc}
   */
  public function addOperations(BatchPluginInterface $batch_plugin, \DrushBatchContext|array $previous_context = []) {
    if (!isset($this->queueId)) {
      $this->queueId = $batch_plugin->getPluginDefinition()['queue_name'];
    }
    if (!isset($this->queueId)) {
      throw new \Exception('No CRON Plugin ID specified');
    }
    $this->batchPlugin = $batch_plugin;
    if ($this->isQueueBuilding()) {
      throw new \Exception('The plugin is already running');
    }
    if ($this->isCronDue()) {
      return parent::addOperations($batch_plugin, $previous_context);
    }
    else {
      return CronProcessorPluginInterface::STATUS_CRON_NOT_DUE;
    }
  }

}
