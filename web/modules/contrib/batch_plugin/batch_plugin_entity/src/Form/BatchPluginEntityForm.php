<?php

namespace Drupal\batch_plugin_entity\Form;

use Drupal\batch_plugin\BatchPluginInterface;
use Drupal\batch_plugin\CronProcessorPluginInterface;
use Drupal\batch_plugin\Plugin\Derivative\BatchPluginQueueWorker;
use Drupal\batch_plugin\ProcessorPluginManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Batch plugin form.
 *
 * @property \Drupal\batch_plugin_entity\BatchPluginEntityInterface $entity
 */
class BatchPluginEntityForm extends EntityForm {

  /**
   * The batch plugin entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The plugin form manager.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * Constructs a BatchPluginEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   The plugin form manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PluginFormFactoryInterface $plugin_form_manager) {
    $this->storage = $entity_type_manager->getStorage('batch_plugin_entity');
    $this->pluginFormFactory = $plugin_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin_form.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form['info'] = [
      '#markup' => $this->t('"@id" batch plugin from "@module"',
        [
          '@id' => $this->entity->getPlugin()->getPluginId(),
          '@module' => $this->entity->getPlugin()->getPluginDefinition()['provider'],
        ]),
    ];

    $form = parent::form($form, $form_state);

    $form['#tree'] = TRUE;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Label for the batch plugin.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\batch_plugin_entity\Entity\BatchPluginEntity::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->entity->get('description'),
      '#description' => $this->t('Description of the batch plugin.'),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
    ];

    $form['#parents'] = [];

    $form['settings'] = [
      '#parents' => [],
    ];
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->getPluginForm($this->entity->getPlugin())->buildConfigurationForm($form['settings'], $subform_state);

    if (!empty($this->entity->id())) {
      $form['process'] = [
        '#type' => 'submit',
        '#op' => 'process',
        '#value' => $this->t('Process'),
        '#submit' => ['::processPlugin'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $this->getPluginForm($this->entity->getPlugin())->validateConfigurationForm($form['settings'], SubformState::createForSubform($form['settings'], $form, $form_state));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $message_args = ['%label' => $this->entity->label()];
    $sub_form_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    // Call the plugin submit handler.
    $plugin = $this->entity->getPlugin();
    $this->getPluginForm($plugin)->submitConfigurationForm($form['settings'], $sub_form_state);
    $result = parent::save($form, $form_state);

    $message = $result == SAVED_NEW
      ? $this->t('Created new batch plugin %label.', $message_args)
      : $this->t('Updated batch plugin %label.', $message_args);
    $this->messenger()->addStatus($message);

    // If this was called from the Process button, then do the processing.
    $op = $form_state->getTriggeringElement();
    if (isset($op['#op']) && $op['#op'] == 'process') {
      $processor_id = $this->entity->get('settings')['processor_plugin_id'];
      $processor_plugin = ProcessorPluginManager::createProcessorPlugin($processor_id);
      if ($processor_plugin instanceof CronProcessorPluginInterface) {
        $processor_plugin->setQueueId(BatchPluginQueueWorker::QUEUE_NAME_PREFIX . '_entity_' . $this->entity->id());
      }
      $processor_plugin->process($plugin);
      $this->messenger()->addStatus($this->t('Batch plugin %label processed.', $message_args));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('edit-form'));
    return $result;
  }

  /**
   * Process the plugin.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function processPlugin(array $form, FormStateInterface $form_state) {
    $this->save($form, $form_state);
  }

  /**
   * Retrieves the plugin form for a given plugin and operation.
   *
   * @param \Drupal\batch_plugin\BatchPluginInterface $batch
   *   The batch plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for the batch plugin.
   */
  protected function getPluginForm(BatchPluginInterface $batch) {
    if ($batch instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($batch, 'configure');
    }
    return $batch;
  }

}
