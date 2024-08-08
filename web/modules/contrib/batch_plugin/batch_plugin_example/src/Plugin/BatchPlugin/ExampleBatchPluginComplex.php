<?php

namespace Drupal\batch_plugin_example\Plugin\BatchPlugin;

use Drupal\batch_plugin\BatchPluginBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Plugin implementation of the batch_plugin.
 *
 * @BatchPlugin(
 *   id = "example_batch_plugin_from_config",
 *   label = @Translation("Example Batch Plugin with complexity"),
 *   description = @Translation("Example Batch Plugin with complexity.")
 * )
 */
class ExampleBatchPluginComplex extends BatchPluginBase {

  /**
   * The bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $bundleInfo;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->bundleInfo = \Drupal::service('entity_type.bundle.info');
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'node_bundles' => [],
      'term_bundles' => [],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $bundle_options = [];
    $bundles = $this->bundleInfo->getBundleInfo('node');
    foreach ($bundles as $bundle_id => $bundle) {
      $bundle_options[$bundle_id] = $bundle['label'];
    }
    $form['node_bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Bundles to display node IDs and titles.'),
      '#options' => $bundle_options,
      '#default_value' => $this->configuration['node_bundles'] ?? [],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    // Node bundles.
    $bundles = [];
    foreach ($values['node_bundles'] as $bundle) {
      if (!empty($bundle)) {
        $bundles[] = $bundle;
      }
    }
    $this->configuration['node_bundles'] = $bundles;
  }

  /**
   * {@inheritDoc}
   */
  public function setupOperations(): void {
    if (empty($context['results']['nodes'])) {
      $this->operationCallback = 'processNodeOperation';
      $this->operations = \Drupal::entityQuery('node')
        ->accessCheck(TRUE)
        ->condition('type', $this->configuration['node_bundles'], 'IN')
        ->execute();
      $this->setBatchTitle('Getting titles for @count nodes from @plugin');
    }
  }

  /**
   * Custom callback function for node operations.
   */
  public function processNodeOperation($payload, array &$context): void {
    $node = Node::load($payload);
    $vars = [
      '@id' => $node->id(),
      '@title' => $node->getTitle(),
    ];
    \Drupal::messenger()->addMessage($this->t('Node ID @id has a title of "@title"', $vars));

    // Load all terms for the node.
    $terms = [];
    foreach ($node->getFields() as $field) {
      if ($field->getFieldDefinition()->getType() == 'entity_reference') {
        $targetType = $field->getFieldDefinition()
          ->getItemDefinition()
          ->getSetting('target_type');
        if ($targetType == 'taxonomy_term') {
          $new_terms = array_map(function (TermInterface $term) {
            return $term->id();
          }, $field->referencedEntities());
          $terms = array_merge($terms, $new_terms);
        }
      }
    }
    if (!empty($terms)) {
      // Create an additional batch containing all the terms.
      // You can do this as many times as you need to, i.e. we could
      // repeat and do something on each term in the processTermOperation
      // function if we needed further batching.
      $context['node_title'] = $node->getTitle();
      $this->setBatchTitle('Getting labels for @count terms from @title from @plugin', $vars);
      $this->appendOperations($terms, $context, 'processTermOperation');
    }
    $context['results']['nodes'][$node->id()] = $node->getTitle();
  }

  /**
   * Custom callback function for term operations.
   */
  public function processTermOperation($payload, array $previousContext, array &$context): void {
    $term = Term::load($payload);
    $vars = [
      '@id' => $term->id(),
      '@label' => $term->label(),
      '@title' => $previousContext['node_title'],
    ];
    \Drupal::messenger()->addMessage($this->t('Term ID @id from node "@title" has a label of "@label"', $vars));
    $context['results']['terms'][$term->id()] = $term->label();
  }

  /**
   * {@inheritDoc}
   */
  public function finished(bool $success, array $results, array $operations): void {
    // This will be called once for each time batch builder is used.
    // i.e. if you append operations this will get called multiple times.
    parent::finished($success, $results, $operations);
    if (!empty($results['nodes'])) {
      $context = [
        '@count' => count($results['nodes']),
      ];
      \Drupal::messenger()->addMessage($this->t('@count node title(s) displayed', $context));
    }
    if (!empty($results['terms'])) {
      $context = [
        '@count' => count($results['terms']),
      ];
      \Drupal::messenger()->addMessage($this->t('@count term label(s) displayed', $context));
    }
  }

}
