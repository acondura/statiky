<?php

namespace Drupal\batch_plugin_example\Plugin\BatchPlugin;

use Drupal\batch_plugin\BatchPluginBase;
use Drupal\node\Entity\Node;
use Drush\Drush;

/**
 * Plugin implementation of the batch_plugin.
 *
 * @BatchPlugin(
 *   id = "example_drush_batch_plugin",
 *   label = @Translation("Example drush batch plugin"),
 *   description = @Translation("Example drush batch plugin."),
 *   processors = "drush"
 * )
 */
class ExampleDrushBatchPlugin extends BatchPluginBase {

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
    $context['message'] = $this->t('Node @id has a title of @title', [
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
    Drush::output()->writeln(dt('@count node title(s) displayed', $context));
  }

}
