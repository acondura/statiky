<?php

namespace Drupal\batch_plugin_entity;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of batch plugins.
 */
class BatchPluginEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['plugin_id'] = $this->t('Plugin ID');
    $header['provider'] = $this->t('Provider');
    $header['processor'] = $this->t('Processor');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\batch_plugin_entity\BatchPluginEntityInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['plugin_id'] = $entity->getPlugin()->getPluginId();
    $row['provider'] = $entity->getPlugin()->getPluginDefinition()['provider'];
    $row['processor'] = $entity->getPlugin()->getConfiguration()['processor_plugin_id'] ?? $this->t('Not set');
    $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritDoc}
   */
  public function ensureDestination(Url $url) {
    return $url;
  }

}
