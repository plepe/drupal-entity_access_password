<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Default entity type password bundle info.
 */
class EntityTypePasswordBundleInfo implements EntityTypePasswordBundleInfoInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The password infos.
   *
   * @var array|null
   */
  protected $passwordInfos;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    EntityFieldManagerInterface $entityFieldManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllPasswordBundleInfo(): array {
    if (!isset($this->passwordInfos)) {
      $password_infos = [];

      $entity_types = $this->entityTypeManager->getDefinitions();
      foreach ($entity_types as $entity_type_id => $entity_type) {
        if (!$entity_type instanceof ContentEntityTypeInterface) {
          continue;
        }

        $bundles_infos = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
        foreach ($bundles_infos as $bundle_id => $bundle_infos) {
          $bundle_fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_id);
          foreach ($bundle_fields as $bundle_field) {
            if ($bundle_field->getType() == 'entity_access_password_password') {
              $password_infos[$entity_type_id]['entity_type'] = $entity_type;
              $password_infos[$entity_type_id]['bundles'][$bundle_id] = $bundle_infos;
              break;
            }
          }
        }
      }

      $this->passwordInfos = $password_infos;
    }

    return $this->passwordInfos;
  }

}
