<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\entity_access_password\Form\SettingsForm;
use Drupal\entity_access_password\Plugin\Field\FieldType\EntityAccessPasswordItem;

/**
 * The default password validator.
 */
class PasswordValidator implements PasswordValidatorInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The password hashing service object.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected PasswordInterface $password;

  /**
   * The access storage service.
   *
   * @var \Drupal\entity_access_password\Service\AccessStorageInterface
   */
  protected AccessStorageInterface $accessStorage;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\Core\Password\PasswordInterface $password
   *   The password service.
   * @param \Drupal\entity_access_password\Service\AccessStorageInterface $accessStorage
   *   The access storage service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    PasswordInterface $password,
    AccessStorageInterface $accessStorage
  ) {
    $this->configFactory = $configFactory;
    $this->password = $password;
    $this->accessStorage = $accessStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePassword(string $password, EntityAccessPasswordItem $fieldItem) : bool {
    $field_instance_settings = $fieldItem->getFieldDefinition()->getSettings();

    // Entity password.
    if ($field_instance_settings['password_entity']) {
      /** @var array $values */
      $values = $fieldItem->getValue();
      if (!empty($values['password']) && $this->password->check($password, $values['password'])) {
        $entity = $fieldItem->getEntity();
        $this->accessStorage->storeEntityAccess($entity);
        return TRUE;
      }
    }

    // Bundle password.
    if ($field_instance_settings['password_bundle']) {
      if (!empty($field_instance_settings['password']) && $this->password->check($password, $field_instance_settings['password'])) {
        $entity = $fieldItem->getEntity();
        $this->accessStorage->storeEntityBundleAccess($entity);
        return TRUE;
      }
    }

    // Global password.
    if ($field_instance_settings['password_global']) {
      $config = $this->configFactory->get(SettingsForm::CONFIG_NAME);
      /** @var string $global_password */
      $global_password = $config->get('global_password');
      if (!empty($global_password) && $this->password->check($password, $global_password)) {
        $this->accessStorage->storeGlobalAccess();
        return TRUE;
      }
    }

    return FALSE;
  }

}
