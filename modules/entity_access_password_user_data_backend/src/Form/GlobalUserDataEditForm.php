<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Form;

use Drupal\entity_access_password_user_data_backend\Service\UserDataBackend;

/**
 * Provides form to remove global access (stored in user data).
 */
class GlobalUserDataEditForm extends UserDataEditFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getUserDataName() : string {
    return UserDataBackend::GLOBAL_NAME_KEY;
  }

}
