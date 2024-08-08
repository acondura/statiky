<?php

namespace Drupal\batch_plugin;

/**
 * Helpful data interface.
 */
interface HelpfulDataInterface {

  /**
   * Get any helpful data that that the processor or batch plugin could use.
   *
   * @return mixed
   *   The data.
   */
  public function getHelpfulData() : mixed;

  /**
   * Set any helpful data that the processor and batch plugin could use.
   *
   * @param mixed $data
   *   The data.
   *
   * @return $this
   */
  public function setHelpfulData($data) : ProcessorPluginInterface|BatchPluginInterface;

}
