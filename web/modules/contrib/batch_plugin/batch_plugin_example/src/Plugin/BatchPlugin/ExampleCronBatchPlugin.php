<?php

namespace Drupal\batch_plugin_example\Plugin\BatchPlugin;

use Drupal\batch_plugin\BatchPluginBase;
use Drupal\node\Entity\Node;

/**
 * Plugin implementation of the batch_plugin.
 *
 * @BatchPlugin(
 *   id = "example_cron_batch_plugin",
 *   label = @Translation("Example CRON batch plugin"),
 *   description = @Translation("Example CRON batch plugin."),
 *   processors = "cron,queue",
 *   cronexpression = "*\/5 * * * *"
 * )
 */
class ExampleCronBatchPlugin extends BatchPluginBase {

  /**
   * {@inheritDoc}
   */
  public function setupOperations(): void {
    $this->operations = \Drupal::entityQuery('node')
      ->accessCheck(TRUE)
      ->execute();
  }

  /**
   * {@inheritDoc}
   */
  public function processOperation($payload, &$context): void {
    $node = Node::load($payload);
    \Drupal::logger('example_batch_plugin')->notice('Node @id has a title of @title', [
      '@id' => $node->id(),
      '@title' => $node->getTitle(),
    ]);
    $context['results']['nodes'][$node->id()] = $node->getTitle();
  }

  /**
   * {@inheritDoc}
   */
  public function finished(bool $success, array $results, array $operations): void {
    parent::finished($success, $results, $operations);
    $context = [
      '@count' => isset($results['nodes']) ? count($results['nodes']) : 0,
    ];
    \Drupal::messenger()->addMessage($this->t('@count node title(s) displayed', $context));
  }

}
