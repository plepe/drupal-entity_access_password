<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Form;

/**
 * Provides form to remove access (stored in user data) to the bundle.
 */
class BundleUserDataEditForm extends UserDataEditFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getUserDataName(): string {
    $route = $this->getRouteMatch()->getRouteObject();
    if ($route == NULL) {
      return '';
    }

    /** @var string $entity_type_id */
    $entity_type_id = $route->getOption('_eapudb_entity_type_id');
    /** @var string $bundle_id */
    $bundle_id = $route->getOption('_eapudb_bundle_id');

    if ($entity_type_id == NULL || $bundle_id == NULL) {
      return '';
    }

    return $this->userDataBackend->getBundleName($entity_type_id, $bundle_id);
  }

}
