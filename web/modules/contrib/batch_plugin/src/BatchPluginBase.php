<?php

namespace Drupal\batch_plugin;

use Drupal\Component\Annotation\Doctrine\SimpleAnnotationReader;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Base class for batch_api_plugin plugins.
 *
 * IMPORTANT. Remember that most of these properties will be lost when the batch
 * is processing as Batch API creates things statically.
 *
 * It is up to you to add the values again if you are going to rely on them.
 */
abstract class BatchPluginBase extends PluginBase implements BatchPluginInterface {

  use StringTranslationTrait;
  use ContextAwarePluginTrait;
  use PluginCreationTrait;
  use HelpfulDataTrait;

  /**
   * Specify the plugin's config form is from the batch_plugin_config element.
   *
   * @var bool
   */
  protected $configFormFromElement = FALSE;

  /**
   * The processor.
   *
   * @var \Drupal\batch_plugin\ProcessorPluginInterface
   */
  protected ProcessorPluginInterface $processor;

  /**
   * The operations.
   *
   * @var array
   */
  protected array $operations = [];

  /**
   * The process batch service.
   *
   * @var \Drupal\batch_plugin\ProcessorPluginManagerInterface
   */
  protected ProcessorPluginManagerInterface $processorPluginManager;

  /**
   * Processor plugin ID.
   *
   * @var string|null
   */
  protected string|null $processorPluginId;

  /**
   * The operation callback function name.
   *
   * @var string
   */
  protected string $operationCallback = 'processOperation';

  /**
   * The finished callback.
   *
   * @var callable
   */
  protected string|array|null $finishedStaticCallback = '';

  /**
   * The batch API title.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  protected TranslatableMarkup|string $batchTitle;

  /**
   * The batch API error message.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  protected TranslatableMarkup|string $batchErrorMessage;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->processorPluginManager = \Drupal::service('plugin.manager.batch_plugin_processor');
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigFormFromElement(bool $configFormFromElement): BatchPluginInterface {
    $this->configFormFromElement = $configFormFromElement;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedProcessorIds(): array {
    if (empty($this->pluginDefinition['processors'])) {
      return array_keys($this->processorPluginManager->getProcessorOptions());
    }
    $processors_ids = explode(',', $this->pluginDefinition['processors']);
    foreach ($processors_ids as &$processors_id) {
      $processors_id = trim($processors_id);
    }
    return $processors_ids;
  }

  /**
   * {@inheritDoc}
   */
  public function getProcessorId(): string|null {
    // Try from a processor object.
    if (!empty($this->processor)) {
      $this->processorPluginId = $this->processor->getPluginId();
    }
    // If we don't have an ID, try from configuration.
    if (empty($this->processorPluginId)) {
      $this->processorPluginId = $this->configuration['processor_plugin_id'] ?? NULL;
    }
    // If we still don't have an ID try from the annotation, or default to
    // batch_api.
    if (empty($this->processorPluginId)) {
      if (empty($this->getAllowedProcessorIds())) {
        $this->processorPluginId = 'batch_api';
      }
      else {
        $ids = $this->getAllowedProcessorIds();
        $this->processorPluginId = reset($ids);
      }
    }
    return $this->processorPluginId;
  }

  /**
   * {@inheritDoc}
   */
  public function setProcessorId(string $processor_id): BatchPluginInterface {
    $this->processorPluginId = $processor_id;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getProcessor(): ProcessorPluginInterface|NULL {
    if (!empty($this->processor)) {
      return $this->processor;
    }
    if (!empty($this->processorPluginId)) {
      return $this->processorPluginManager->createInstance($this->processorPluginId);
    }
    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function setProcessor(ProcessorPluginInterface $processor): BatchPluginInterface {
    $this->processor = $processor;
    $this->processorPluginId = $processor->getPluginId();
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getBatchTitle(): string|TranslatableMarkup {
    if (empty($this->batchTitle)) {
      $this->setBatchTitle('Processing @count operations from @plugin');
    }
    return $this->batchTitle;
  }

  /**
   * {@inheritDoc}
   */
  public function setBatchTitle(string $message, array $context = []): BatchPluginInterface {
    $context = array_merge($context, [
      '@plugin' => $this->label(),
      '@count' => count($this->operations),
    ]);
    // @codingStandardsIgnoreStart
    $this->batchTitle = $this->t($message, $context);
    // @codingStandardsIgnoreEnd
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getBatchErrorMessage(): string|TranslatableMarkup {
    if (empty($this->batchErrorMessage)) {
      $this->setBatchErrorMessage('Batch has encountered an error while processing @plugin');
    }
    return $this->batchErrorMessage;
  }

  /**
   * {@inheritDoc}
   */
  public function setBatchErrorMessage(string $message): BatchPluginInterface {
    $context = [
      '@plugin' => $this->label(),
    ];
    // @codingStandardsIgnoreStart
    $this->batchErrorMessage = $this->t($message, $context);
    // @codingStandardsIgnoreEnd
    return $this;
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
    return [
      'processor_plugin_id' => 'batch_api',
      'processor_plugin_configuration' => [],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if (!$this->configFormFromElement) {
      $form['processor_plugin_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Processor'),
        '#options' => $this->processorPluginManager->getProcessorOptions($this),
        '#default_value' => $this->configuration['processor_plugin_id'] ?? 'processor_plugin_id',
        '#required' => TRUE,
        '#ajax' => [
          'callback' => [self::class, 'processorChangedAjaxCallback'],
          'wrapper' => 'process-configuration-wrapper',
        ],
      ];
      $values = $form_state->getValues();
      $processor_plugin_id = $values['processor_plugin_id'] ?? ($this->configuration['processor_plugin_id'] ?? '');
      $processor_plugin_configuration = $values['processor_plugin_configuration'] ?? ($this->configuration['processor_plugin_configuration'] ?? []);
      if (!empty($processor_plugin_id)) {
        $processor = $this->processorPluginManager->createInstance($processor_plugin_id, $processor_plugin_configuration);
        $form['processor_plugin_configuration'] = [];
        $subform_state = SubformState::createForSubform($form['processor_plugin_configuration'], $form, $form_state);
        $form['processor_plugin_configuration'] = [
          '#prefix' => '<div id="process-configuration-wrapper">',
          '#suffix' => '</div>',
        ] + static::getPluginForm($processor)->buildConfigurationForm($form['processor_plugin_configuration'], $subform_state);
      }
      else {
        $form['processor_plugin_configuration'] = [
          '#prefix' => '<div id="process-configuration-wrapper">',
          '#suffix' => '</div>',
          '#markup' => '',
        ];
      }
    }
    return $form;
  }

  /**
   * Ajax callback from processor_plugin_id change.
   */
  public static function processorChangedAjaxCallback(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $parents);
    return $element['processor_plugin_configuration'];
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($this->configFormFromElement) {
      return;
    }
    $values = $form_state->getValues();
    if (empty($values['processor_plugin_id'])) {
      return;
    }
    $processor = $this->processorPluginManager->createInstance($values['processor_plugin_id'], $values['processor_plugin_configuration'] ?? []);
    static::getPluginForm($processor)->validateConfigurationForm($form['processor_plugin_configuration'], SubformState::createForSubform($form['processor_plugin_configuration'], $form, $form_state));
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($this->configFormFromElement) {
      return;
    }
    $values = $form_state->getValues();
    $this->configuration['processor_plugin_id'] = $values['processor_plugin_id'];
    $sub_form_state = SubformState::createForSubform($form['processor_plugin_configuration'], $form, $form_state);
    /** @var \Drupal\batch_plugin\ProcessorPluginInterface $processor */
    $processor = $this->processorPluginManager->createInstance($this->configuration['processor_plugin_id'], $this->configuration['processor_plugin_configuration'] ?? []);
    static::getPluginForm($processor)->submitConfigurationForm($form, $sub_form_state);
    $this->configuration['processor_plugin_configuration'] = $processor->getConfiguration();
  }

  /**
   * {@inheritDoc}
   */
  public function process($processor_plugin = NULL): void {
    $this->processorPluginManager->processBatchPlugin($this, $processor_plugin, $this->helpfulData);
  }

  /**
   * {@inheritDoc}
   */
  public static function processStatic($helpful_data = NULL, $processor_plugin = NULL): void {
    // Check this hasn't been called on the base (or other abstract) class.
    $reflection = new \ReflectionClass(static::class);
    if ($reflection->isAbstract()) {
      throw new \Exception('Cannot call this method on an abstract class');
    }
    // Add Drupal core and batch plugin namespaces.
    $reader = new SimpleAnnotationReader();
    $reader->addNamespace('Drupal\Core\Annotation');
    $reader->addNamespace('Drupal\batch_plugin\Annotation');
    // Read the annotation from the statically called batch plugin.
    /** @var \Drupal\batch_plugin\Annotation\BatchPlugin $annotation */
    $annotation = $reader->getClassAnnotation($reflection, 'Drupal\batch_plugin\Annotation\BatchPlugin');
    // Check we can get to the annotation's plugin ID.
    if (empty($annotation)) {
      throw new \Exception('No annotation found on the static class');
    }
    $plugin_id = $plugin_id = $annotation->getId();
    if (empty($plugin_id)) {
      throw new \Exception('No plugin ID found on the static class');
    }
    // Create the annotation's batch plugin and process the plugin.
    $batchPlugin = static::createBatchPlugin($plugin_id, []);
    $batchPlugin->setHelpfulData($helpful_data);
    $batchPlugin->process($processor_plugin);
  }

  /**
   * {@inheritDoc}
   */
  public function processAppendedOperation($payload, array|\DrushBatchContext $previousContext, array|\DrushBatchContext &$context): void {
    // If you want nested operations, override this method or define a
    // custom callback with the same variable signature, and pass that to
    // the appendOperations function.
  }

  /**
   * {@inheritDoc}
   */
  public function getOperations(): array {
    return $this->operations;
  }

  /**
   * {@inheritDoc}
   */
  public function getOperationCallback(): string {
    return $this->operationCallback;
  }

  /**
   * {@inheritdoc}
   */
  public function getFinishedStaticCallback() : array|string|null {
    return $this->finishedStaticCallback;
  }

  /**
   * {@inheritdoc}
   */
  public function setFinishedStaticCallback(callable $callback) : BatchPluginInterface {
    $this->finishedStaticCallback = $callback;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function processOperation($payload, array|\DrushBatchContext &$context): void {
    // This should be overridden unless specifying a custom callback.
  }

  /**
   * {@inheritDoc}
   */
  public function appendOperations(array $operations, array|\DrushBatchContext $context, string $callback = ''): void {
    if (empty($callback)) {
      $callback = 'processAppendedOperation';
    }
    $this->operations = $operations;
    $this->operationCallback = $callback;
    $this->processorPluginManager->addBatch($this, $this->getProcessor(), $context);
  }

  /**
   * {@inheritDoc}
   */
  public function finished(bool $success, array $results, array $operations): void {
    // Do nothing.
  }

  /**
   * Get a list of queue worker derivatives.
   *
   * @return array
   *   The derivatives.
   */
  public function getQueueWorkerDerivatives() : array {
    return [];
  }

}
