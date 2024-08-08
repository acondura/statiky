<?php

namespace Drupal\batch_plugin_entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Provides an interface defining a batch plugin entity type.
 */
interface BatchPluginEntityInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

}
