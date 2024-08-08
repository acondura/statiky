<?php

namespace Drupal\batch_plugin_entity\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for building the batch plugin instance add form.
 */
class BatchPluginEntityAddController extends ControllerBase {

  /**
   * Build the batch plugin instance add form.
   *
   * @param string $plugin_id
   *   The plugin ID for the batch plugin instance.
   *
   * @return array
   *   The batch plugin instance edit form.
   */
  public function batchPluginEntityAddConfigureForm($plugin_id): array {
    // Create a batch plugin entity.
    $entity = $this->entityTypeManager()->getStorage('batch_plugin_entity')->create(['plugin' => $plugin_id]);
    return $this->entityFormBuilder()->getForm($entity);
  }

}
