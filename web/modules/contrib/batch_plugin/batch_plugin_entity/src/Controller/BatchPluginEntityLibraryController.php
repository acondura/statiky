<?php

namespace Drupal\batch_plugin_entity\Controller;

use Drupal\batch_plugin\BatchPluginManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Menu\LocalActionManagerInterface;
use Drupal\Core\Plugin\Context\LazyContextRepository;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a list of batch plugins to be added to the layout.
 */
class BatchPluginEntityLibraryController extends ControllerBase {

  /**
   * The batch plugin manager.
   *
   * @var \Drupal\batch_plugin\BatchPluginManager
   */
  protected $batchPluginManager;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\LazyContextRepository
   */
  protected $contextRepository;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The local action manager.
   *
   * @var \Drupal\Core\Menu\LocalActionManagerInterface
   */
  protected $localActionManager;

  /**
   * Constructs a BatchPluginEntityLibraryController object.
   *
   * @param \Drupal\batch_plugin\BatchPluginManager $batch_plugin_mananger
   *   The batch plugin manager.
   * @param \Drupal\Core\Plugin\Context\LazyContextRepository $context_repository
   *   The context repository.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Menu\LocalActionManagerInterface $local_action_manager
   *   The local action manager.
   */
  public function __construct(BatchPluginManager $batch_plugin_mananger, LazyContextRepository $context_repository, RouteMatchInterface $route_match, LocalActionManagerInterface $local_action_manager) {
    $this->batchPluginManager = $batch_plugin_mananger;
    $this->routeMatch = $route_match;
    $this->localActionManager = $local_action_manager;
    $this->contextRepository = $context_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.batch_plugin'),
      $container->get('context.repository'),
      $container->get('current_route_match'),
      $container->get('plugin.manager.menu.local_action')
    );
  }

  /**
   * Shows a list of batch plugins that can be added.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function listPlugins(Request $request): array {
    // Since modals do not render any other part of the page, we need to render
    // them manually as part of this listing.
    if ($request->query->get(MainContentViewSubscriber::WRAPPER_FORMAT) === 'drupal_modal') {
      $build['local_actions'] = $this->buildLocalActions();
    }

    $headers = [
      ['data' => $this->t('Plugin')],
      ['data' => $this->t('Module')],
      ['data' => $this->t('Operations')],
    ];

    $definitions = $this->batchPluginManager->getDefinitions();

    $rows = [];
    foreach ($definitions as $plugin_id => $plugin_definition) {
      if (!empty($plugin_definition['hidden'])) {
        continue;
      }
      if (!empty($plugin_definition['permission'])) {
        $permission = $plugin_definition['permission'];
        if (!\Drupal::currentUser()->hasPermission($permission)) {
          continue;
        }
      }
      $row = [];
      $row['title']['data'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="batch-plugin-filter-text-source">{{ label }}</div>',
        '#context' => [
          'label' => $plugin_definition['label'],
        ],
      ];
      $row['category']['data'] = $plugin_definition['provider'];
      $links['add'] = [
        'title' => $this->t('Add plugin'),
        'url' => Url::fromRoute('batch_plugin_entity.admin_add', ['plugin_id' => $plugin_id]),
      ];
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
      $rows[] = $row;
    }

    $build['plugins'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No batch plugin available.'),
      '#attributes' => [
        'class' => ['batch-plugin-add-table'],
      ],
    ];

    return $build;
  }

  /**
   * Builds the local actions for this listing.
   *
   * @return array
   *   An array of local actions for this listing.
   */
  protected function buildLocalActions(): array {
    $build = $this->localActionManager->getActionsForRoute($this->routeMatch->getRouteName());
    // Without this workaround, the action links will be rendered as <li> with
    // no wrapping <ul> element.
    if (!empty($build)) {
      $build['#prefix'] = '<ul class="action-links">';
      $build['#suffix'] = '</ul>';
    }
    return $build;
  }

}
