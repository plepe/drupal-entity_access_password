<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Form;

/**
 * Provides form to remove global access (stored in user data).
 */
class GlobalUserDataEditForm extends UserDataEditFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getUserDataName() : string {
    return $this->userDataBackend->getGlobalName();
  }

}
