<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Form;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides form to remove global access (stored in user data).
 */
class GlobalUserDataEditForm extends UserDataEditFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getFormTitle(): TranslatableMarkup {
    return $this->t('Global password user data');
  }

  /**
   * {@inheritdoc}
   */
  protected function getUserDataName(): string {
    return $this->userDataBackend->getGlobalName();
  }

}
