<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Password\PasswordInterface;
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
  protected $configFactory;

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
    $password_is_valid = FALSE;

    /** @var array $values */
    $values = $fieldItem->getValue();

    if (!empty($values['password'])) {
      $password_is_valid = $this->password->check($password, $values['password']);
    }

    // @todo password hierarchy.
    if ($password_is_valid) {
      $entity = $fieldItem->getEntity();
      $this->accessStorage->storeEntityAccess($entity);
    }

    return $password_is_valid;
  }

}
