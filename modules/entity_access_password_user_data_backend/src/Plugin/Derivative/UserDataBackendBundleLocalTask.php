<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Plugin\Derivative;

use Drupal\entity_access_password_user_data_backend\Routing\BundleFormRoutes;

/**
 * Provides local task definitions for bundles.
 */
class UserDataBackendBundleLocalTask extends UserDataBackendDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) : array {
    $this->derivatives = [];

    $password_infos = $this->entityTypePasswordBundleInfo->getAllPasswordBundleInfo();
    foreach ($password_infos as $entity_type_id => $entity_infos) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      $entity_type = $entity_infos['entity_type'];
      foreach ($entity_infos['bundles'] as $bundle_id => $bundle_infos) {
        $route_name = sprintf(BundleFormRoutes::ROUTE_NAME, $entity_type_id, $bundle_id);
        $this->derivatives[$route_name] = [
          'title' => $this->t('@entity_type: @bundle', [
            '@entity_type' => $entity_type->getLabel(),
            '@bundle' => $bundle_infos['label'],
          ]),
          'route_name' => $route_name,
          'parent_id' => 'entity_access_password_user_data_backend.admin_tab',
        ];
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
