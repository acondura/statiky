<?php

namespace Drupal\batch_plugin\Element;

use Drupal\batch_plugin\PluginCreationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a batch_plugin_config form element.
 *
 * Can be used in any configuration forms where you want to include the batch
 * plugin configuration.
 *
 * Usage example:
 *
 * @code
 * $form['batch_plugin_config'] = [
 *   '#type' => 'batch_plugin_config',
 *   '#plugin_id' => 'example_batch_plugin_complex',
 *   '#plugin_configuration' => [],
 *   '#show_processor_element' => boolean,
 * ];
 * @endcode
 *
 * @FormElement("batch_plugin_config")
 */
class BatchPluginConfig extends FormElement {

  use PluginCreationTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#process' => [
        [$class, 'renderConfig'],
      ],
      '#element_validate' => [
        [$class, 'validateConfig'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Callback when building the element.
   *
   * @param array $element
   *   The Form API element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete Form API form.
   *
   * @return array
   *   The built form API element.
   */
  public static function renderConfig(array $element, FormStateInterface $form_state, array &$complete_form) {
    if (empty($element['#plugin_id'])) {
      return $element;
    }
    $plugin = static::getBatchPlugin($element);

    $element['plugin_configuration'] = [
      '#parents' => $element['#parents'],
      '#tree' => TRUE,
    ];
    $subform_state = SubformState::createForSubform($element['plugin_configuration'], $complete_form, $form_state);
    $element['plugin_configuration'] = static::getPluginForm($plugin)->buildConfigurationForm($element['plugin_configuration'], $subform_state);
    return $element;
  }

  /**
   * Create a plugin from the form element properties.
   *
   * @param array $element
   *   The Form API element.
   *
   * @return \Drupal\batch_plugin\BatchPluginInterface|null
   *   The batch plugin.
   */
  protected static function getBatchPlugin(array $element) {
    if (empty($element['#plugin_id'])) {
      return NULL;
    }
    $plugin_id = $element['#plugin_id'];
    $plugin_configuration = $element['#plugin_configuration'] ?? [];
    $plugin = static::createBatchPlugin($plugin_id, $plugin_configuration);
    $plugin->setConfigFormFromElement(empty($element['#show_processor_element']));
    return $plugin;
  }

  /**
   * Form element validation handler for #type 'batch_plugin_config'.
   */
  public static function validateConfig(&$element, FormStateInterface $form_state, &$complete_form) {
    $plugin = static::getBatchPlugin($element);
    $sub_form_state = SubformState::createForSubform($element['plugin_configuration'], $complete_form, $form_state);
    static::getPluginForm($plugin)->validateConfigurationForm($element['plugin_configuration'], $sub_form_state);
  }

  /**
   * {@inheritDoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if (empty($input)) {
      return [];
    }
    // We have to create a pseudo sub-form-state and populate the values from
    // the input, as they will not be there at this stage.
    // There is probably and better way to do this - please patch or fork!
    $dummy['plugin_configuration'] = [
      '#parents' => $element['#parents'],
    ];
    $sub_form_state = SubformState::createForSubform($dummy['plugin_configuration'], $element, $form_state);
    $sub_form_state->setValues($input);
    // Call the plugin submit handler.
    $plugin = static::getBatchPlugin($element);
    static::getPluginForm($plugin)->submitConfigurationForm($dummy['plugin_configuration'], $sub_form_state);
    return $plugin->getConfiguration();
  }

}
