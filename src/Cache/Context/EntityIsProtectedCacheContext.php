<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_access_password\Service\PasswordAccessManagerInterface;

/**
 * Defines the EntityIsProtectedCacheContext service.
 *
 * Calculated cache context ID:
 * 'entity_access_password_entity_is_protected:%entity_type_id||%entity_id||%view_mode',
 * e.g. 'entity_access_password_entity_is_protected:node||42||teaser'.
 */
class EntityIsProtectedCacheContext implements CalculatedCacheContextInterface {

  /**
   * The context ID prefix.
   */
  public const CONTEXT_ID = 'entity_access_password_entity_is_protected';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The password access manager.
   *
   * @var \Drupal\entity_access_password\Service\PasswordAccessManagerInterface
   */
  protected PasswordAccessManagerInterface $passwordAccessManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\entity_access_password\Service\PasswordAccessManagerInterface $passwordAccessManager
   *   The password access manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    PasswordAccessManagerInterface $passwordAccessManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->passwordAccessManager = $passwordAccessManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Entity Access Password: Entity is protected.');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($entity_info = NULL) : string {
    // Impossible to determine the entity so do nothing.
    if (is_null($entity_info)) {
      return '0';
    }

    $entity = $this->loadEntity($entity_info);
    if ($entity == NULL) {
      return '0';
    }
    $entity_info = explode('||', $entity_info);
    $view_mode = $entity_info[2];

    // Entity view mode is not protected.
    if (!$this->passwordAccessManager->isEntityViewModeProtected($view_mode, $entity)) {
      return '0';
    }

    // User has access.
    if ($this->passwordAccessManager->hasUserAccessToEntity($entity)) {
      return '0';
    }
    else {
      return '1';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($entity_info = NULL) : CacheableMetadata {
    $cache_metadata = new CacheableMetadata();

    if (is_null($entity_info)) {
      return $cache_metadata;
    }
    $entity = $this->loadEntity($entity_info);
    if ($entity == NULL) {
      return $cache_metadata;
    }

    $cache_metadata->addCacheableDependency($entity);
    return $cache_metadata;
  }

  /**
   * Load the fieldable entity if possible.
   *
   * @param string $entity_info
   *   The cache context parameter.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface|null
   *   The fieldable entity if found. NULL otherwise.
   */
  protected function loadEntity(string $entity_info) {
    $entity_info = explode('||', $entity_info);
    if (count($entity_info) != 3) {
      return NULL;
    }
    $entity_type_id = $entity_info[0];
    $entity_id = $entity_info[1];

    try {
      $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    }
    catch (\Throwable $exception) {
      return NULL;
    }

    $entity = $entity_storage->load($entity_id);
    if ($entity == NULL) {
      return NULL;
    }
    elseif (!$entity instanceof FieldableEntityInterface) {
      return NULL;
    }
    else {
      return $entity;
    }
  }

}
