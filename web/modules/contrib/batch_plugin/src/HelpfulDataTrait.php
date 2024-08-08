<?php

namespace Drupal\batch_plugin;

/**
 * Helpful data trait.
 *
 * This data is useful for plugins to use when setting up the operations.
 *
 * Helpful data will not passed to the processing operations unless it is added
 * to the operation payload in your own plugin's setupOperations() function.
 *
 * If you do add helpful data to the payload, ensure it can be serialized.
 */
trait HelpfulDataTrait {

  /**
   * Any data to help the processor or batch plugin.
   *
   * @var mixed
   */
  protected $helpfulData;

  /**
   * {@inheritdoc}
   */
  public function getHelpfulData() : mixed {
    return $this->helpfulData;
  }

  /**
   * {@inheritdoc}
   */
  public function setHelpfulData($data) : ProcessorPluginInterface|BatchPluginInterface {
    $this->helpfulData = $data;
    return $this;
  }

}
