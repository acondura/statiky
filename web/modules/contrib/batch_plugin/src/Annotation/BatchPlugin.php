<?php

namespace Drupal\batch_plugin\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines batch_plugin annotation object.
 *
 * @Annotation
 */
class BatchPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * An comma separated list of processors that can run the plugin.
   *
   * If empty, any can be used.
   *
   * The first one in the list will be the default if you process without
   * first specifying a processor.
   *
   * @var string
   */
  public $processors;

  /**
   * The permission required to edit or run the plugin from batch_plugin_entity.
   *
   * Defaults to administer batch_plugin_entity.
   *
   * NOT IMPLEMENTED YET!
   *
   * @var string
   */
  public $permission;

  /**
   * The CRON expression.
   *
   * A complex CRON expression, e.g \/5 * * * *
   * Or macros. See https://github.com/dragonmantank/cron-expression
   * '@yearly', '@annually' - Run once a year, midnight, Jan. 1 - 0 0 1 1 *
   * '@monthly' - Run once a month, midnight, first of month - 0 0 1 * *
   * '@weekly' - Run once a week, midnight on Sun - 0 0 * * 0
   * '@daily', '@midnight' - Run once a day, midnight - 0 0 * * *
   * '@hourly' - Run once an hour, first minute - 0 * * * *
   *
   * @var string
   */
  public $cronexpression;

  /**
   * Whether to hide from batch_plugin_entity.
   *
   * @var bool
   */
  public $hidden;

}
