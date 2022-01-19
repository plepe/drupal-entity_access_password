<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Plugin\Derivative;

use Drupal\entity_access_password_user_data_backend\Routing\EntityFormRoutes;

/**
 * Provides local task definitions for entity bundles.
 */
class UserDataBackendEntityLocalTask extends UserDataBackendDeriverBase {

  /**
   * The task weight.
   */
  public const TASK_WEIGHT = 50;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $this->derivatives = [];

    $password_infos = $this->entityTypePasswordBundleInfo->getAllPasswordBundleInfo();
    foreach ($password_infos as $entity_type_id => $entity_infos) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      $entity_type = $entity_infos['entity_type'];
      $has_canonical_path = $entity_type->hasLinkTemplate('canonical');
      $has_edit_path = $entity_type->hasLinkTemplate('edit');

      if ($has_canonical_path || $has_edit_path) {
        foreach (\array_keys($entity_infos['bundles']) as $bundle_id) {
          $route_name = \sprintf(EntityFormRoutes::ROUTE_NAME, $entity_type_id, $bundle_id);
          $this->derivatives[$route_name] = [
            'title' => $this->t('Password access user data'),
            'route_name' => $route_name,
            'base_route' => "entity.{$entity_type_id}." . ($has_canonical_path ? 'canonical' : 'edit_form'),
            'weight' => self::TASK_WEIGHT,
          ];
        }
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
