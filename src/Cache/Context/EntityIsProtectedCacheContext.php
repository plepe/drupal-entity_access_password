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
 * Or
 * 'entity_access_password_entity_is_protected:%entity_type_id||%entity_id',
 * e.g. 'entity_access_password_entity_is_protected:node||42'.
 */
class EntityIsProtectedCacheContext implements CalculatedCacheContextInterface {

  /**
   * The context ID prefix.
   */
  public const CONTEXT_ID = 'entity_access_password_entity_is_protected';

  /**
   * View mode position when parsing the view mode context.
   */
  public const VIEW_MODE_POSITION = 2;

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
   * The entries already processed.
   *
   * @var array
   */
  protected array $processedEntries = [];

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
    return \t('Entity Access Password: Entity is protected.');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($entity_info = NULL): string {
    if (isset($this->processedEntries[$entity_info]['context_value'])) {
      return $this->processedEntries[$entity_info]['context_value'];
    }

    // Impossible to determine the entity so do nothing.
    if ($entity_info === NULL) {
      $this->processedEntries[$entity_info]['context_value'] = '0';
      return $this->processedEntries[$entity_info]['context_value'];
    }

    // There should be at least 2 parts.
    $parsed_entity_info = \explode('||', $entity_info);
    switch (\count($parsed_entity_info)) {
      case (int) 2:
        $entity_type_id = $parsed_entity_info[0];
        $entity_id = $parsed_entity_info[1];
        $entity = $this->loadEntity($entity_type_id, $entity_id);
        if ($entity == NULL) {
          $this->processedEntries[$entity_info]['context_value'] = '0';
          return $this->processedEntries[$entity_info]['context_value'];
        }
        // Entity is not protected.
        if (!$this->passwordAccessManager->isEntityLabelProtected($entity)) {
          $this->processedEntries[$entity_info]['context_value'] = '0';
          return $this->processedEntries[$entity_info]['context_value'];
        }
        break;

      case (int) 3:
        $entity_type_id = $parsed_entity_info[0];
        $entity_id = $parsed_entity_info[1];
        $view_mode = $parsed_entity_info[self::VIEW_MODE_POSITION];
        $entity = $this->loadEntity($entity_type_id, $entity_id);
        if ($entity == NULL) {
          $this->processedEntries[$entity_info]['context_value'] = '0';
          return $this->processedEntries[$entity_info]['context_value'];
        }
        // Entity view mode is not protected.
        if (!$this->passwordAccessManager->isEntityViewModeProtected($view_mode, $entity)) {
          $this->processedEntries[$entity_info]['context_value'] = '0';
          return $this->processedEntries[$entity_info]['context_value'];
        }
        break;

      default:
        $this->processedEntries[$entity_info]['context_value'] = '0';
        return $this->processedEntries[$entity_info]['context_value'];
    }

    // User has access.
    if ($this->passwordAccessManager->hasUserAccessToEntity($entity)) {
      $this->processedEntries[$entity_info]['context_value'] = '0';
      return $this->processedEntries[$entity_info]['context_value'];
    }

    $this->processedEntries[$entity_info]['context_value'] = '1';
    return $this->processedEntries[$entity_info]['context_value'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($entity_info = NULL): CacheableMetadata {
    if (isset($this->processedEntries[$entity_info]['cacheable_metadata'])) {
      return $this->processedEntries[$entity_info]['cacheable_metadata'];
    }

    $this->processedEntries[$entity_info]['cacheable_metadata'] = new CacheableMetadata();

    if ($entity_info === NULL) {
      return $this->processedEntries[$entity_info]['cacheable_metadata'];
    }

    $parsed_entity_info = \explode('||', $entity_info);
    if (\count($parsed_entity_info) < (int) 2) {
      return $this->processedEntries[$entity_info]['cacheable_metadata'];
    }

    $entity_type_id = $parsed_entity_info[0];
    $entity_id = $parsed_entity_info[1];
    $entity = $this->loadEntity($entity_type_id, $entity_id);
    if ($entity == NULL) {
      return $this->processedEntries[$entity_info]['cacheable_metadata'];
    }

    $this->processedEntries[$entity_info]['cacheable_metadata']->addCacheableDependency($entity);
    return $this->processedEntries[$entity_info]['cacheable_metadata'];
  }

  /**
   * Load the fieldable entity if possible.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface|null
   *   The fieldable entity if found. NULL otherwise.
   */
  protected function loadEntity(string $entity_type_id, string $entity_id) {
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
    if (!$entity instanceof FieldableEntityInterface) {
      return NULL;
    }

    return $entity;
  }

}
