<?php

namespace Drupal\batch_plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Interface for batch_api_plugin plugins.
 *
 * IMPORTANT. Remember that most of these properties will be lost when the batch
 * is processing as Batch API can only create things statically.
 *
 * The processor plugin recreates the batch plugin with
 * configuration and any payload with any required context, e.g. batch API.
 */
interface BatchPluginInterface extends ConfigurableInterface, PluginFormInterface, PluginInspectionInterface, HelpfulDataInterface {

  /**
   * Flag if the config form is coming from the batch_plugin_config element.
   *
   * @param bool $configFormFromElement
   *   Whether the config form is coming from the element.
   *
   * @return $this
   */
  public function setConfigFormFromElement(bool $configFormFromElement): BatchPluginInterface;

  /**
   * Get a list of allowed processor, as defined by the annotation.
   *
   * Defaults to all if no annotation property is present.
   *
   * @return string[]
   *   A list or processor plugin IDs.
   */
  public function getAllowedProcessorIds(): array;

  /**
   * Get the processor ID.
   *
   * @return string
   *   The processor ID.
   */
  public function getProcessorId(): string|null;

  /**
   * Set the processor ID.
   *
   * @param string $processor_id
   *   The processor ID.
   *
   * @return $this
   */
  public function setProcessorId(string $processor_id): BatchPluginInterface;

  /**
   * Get the processor.
   *
   * @return \Drupal\batch_plugin\ProcessorPluginInterface
   *   The processor ID.
   */
  public function getProcessor(): ProcessorPluginInterface|NULL;

  /**
   * Sets the processing mode.
   *
   * @param \Drupal\batch_plugin\ProcessorPluginInterface $processor
   *   The processor plugin.
   *
   * @return $this
   */
  public function setProcessor(ProcessorPluginInterface $processor): BatchPluginInterface;

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label(): string;

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state);

  /**
   * Return the batch title.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The batch title.
   */
  public function getBatchTitle(): string|TranslatableMarkup;

  /**
   * Set the batch title.
   *
   * @param string $message
   *   The message to pass to Drupal's t function.
   * @param array $context
   *   Any additional drupal t context.
   *
   * @return $this
   */
  public function setBatchTitle(string $message, array $context = []): BatchPluginInterface;

  /**
   * Return the batch error message.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The error message.
   */
  public function getBatchErrorMessage(): string|TranslatableMarkup;

  /**
   * Set the batch API error message.
   *
   * @param string $message
   *   The message.
   *
   * @return $this
   */
  public function setBatchErrorMessage(string $message): BatchPluginInterface;

  /**
   * Process the plugin.
   *
   * @param \Drupal\batch_plugin\ProcessorPluginInterface|string|null $processor_plugin
   *   Override the processor plugin by ID or instance.
   */
  public function process($processor_plugin = NULL): void;

  /**
   * Process the plugin from a static instance.
   *
   * @param mixed $helpful_data
   *   Any helpful data.
   * @param \Drupal\batch_plugin\ProcessorPluginInterface|string|null $processor_plugin
   *   Override the processor plugin by ID or instance.
   */
  public static function processStatic($helpful_data = NULL, $processor_plugin = NULL): void;

  /**
   * Get the operations.
   *
   * @return array
   *   The operations.
   */
  public function getOperations(): array;

  /**
   * Set up the initial payloads.
   *
   * IMPORTANT - you must add the operations to $this->operations.
   */
  public function setupOperations(): void;

  /**
   * Process an appended operation, from a nested batch routine.
   *
   * @param mixed $payload
   *   The payload.
   * @param array|\DrushBatchContext $previousContext
   *   Any previous batch API context from the parent.
   * @param array|\DrushBatchContext $context
   *   The current batch API context.
   */
  public function processAppendedOperation($payload, array $previousContext, array|\DrushBatchContext &$context): void;

  /**
   * Get the callback string function name.
   *
   * Defaults to processOperation.
   *
   * @return string
   *   The function name in the plugin class.
   */
  public function getOperationCallback(): string;

  /**
   * Get the STATIC batch API finish callback.
   *
   * Defaults to the Batch API processors static finish callback, which gets
   * passed to the batch plugin's finished() method.
   *
   * @return array|string|null
   *   The callback.
   */
  public function getFinishedStaticCallback() : array|string|null;

  /**
   * {@inheritdoc}
   */
  public function setFinishedStaticCallback(callable $callback) : BatchPluginInterface;

  /**
   * Process the operation.
   *
   * @param mixed $payload
   *   An array of variables for processing.
   * @param array|\DrushBatchContext $context
   *   The batch API context.
   *
   * @return mixed
   *   The return.
   */
  public function processOperation($payload, array|\DrushBatchContext &$context): void;

  /**
   * Append additional nested operations.
   *
   * @param array $operations
   *   The operations to append.
   * @param array|\DrushBatchContext $context
   *   The batch API context. DO NOT put anything non-serializable in here.
   * @param string $callback
   *   The callback to use.
   *   IMPORTANT - any callback must have the same variable signature as
   *   the processAppendedOperation function in this interface.
   */
  public function appendOperations(array $operations, array|\DrushBatchContext $context, string $callback = ''): void;

  /**
   * Process batch finished callback.
   *
   * Only used by Batch API plugins.
   *
   * @param bool $success
   *   Success state of the operation.
   * @param array $results
   *   Array of results for post-processing.
   * @param array $operations
   *   Operations array.
   */
  public function finished(bool $success, array $results, array $operations): void;

  /**
   * Get a list of queue worker derivatives.
   *
   * @return array
   *   The derivatives.
   */
  public function getQueueWorkerDerivatives() : array;

}
