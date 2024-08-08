<?php

namespace Drupal\batch_plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for processor plugins.
 */
abstract class ProcessorPluginBase extends PluginBase implements ProcessorPluginInterface {

  use PluginCreationTrait;
  use StringTranslationTrait;
  use HelpfulDataTrait;

  /**
   * The batch plugin.
   *
   * IMPORTANT - if using batch API this will be created statically,
   *  therefore this may not be available when processing.
   *
   * @var \Drupal\batch_plugin\BatchPluginInterface
   */
  protected BatchPluginInterface $batchPlugin;

  /**
   * The operations, which comes from the batch plugin.
   *
   * IMPORTANT - if using batch API this will be created statically,
   *   therefore this may not be available when processing.
   *
   * @var array
   */
  protected array $operations;

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritDoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritDoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

  /**
   * {@inheritDoc}
   */
  public function process(BatchPluginInterface $plugin) : void {
    $plugin->process($this);
  }

  /**
   * {@inheritdoc}
   */
  public function addOperations(BatchPluginInterface $batch_plugin, array|\DrushBatchContext $previous_context = []) {
    $this->batchPlugin = $batch_plugin;
    $operations = $batch_plugin->getOperations();
    foreach ($operations as $operation) {
      $this->operations[] = [
        'batch_plugin_id' => $batch_plugin->getPluginId(),
        'batch_plugin_configuration' => $batch_plugin->getConfiguration(),
        'operation_callback' => $batch_plugin->getOperationCallback(),
        'operation_payload' => $operation,
        'context' => $previous_context,
        'operations_count' => count($operations),
      ];
    }
    return ProcessorPluginInterface::STATUS_OPERATIONS_ADDED;
  }

}
