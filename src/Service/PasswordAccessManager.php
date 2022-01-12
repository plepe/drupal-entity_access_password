<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Default password access manager service.
 */
class PasswordAccessManager implements PasswordAccessManagerInterface {

  /**
   * The access checker service.
   *
   * @var \Drupal\entity_access_password\Service\AccessCheckerInterface
   */
  protected AccessCheckerInterface $accessChecker;

  /**
   * Constructor.
   *
   * @param \Drupal\entity_access_password\Service\AccessCheckerInterface $accessChecker
   *   The access checker service.
   */
  public function __construct(
    AccessCheckerInterface $accessChecker
  ) {
    $this->accessChecker = $accessChecker;
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityViewModeProtected(string $view_mode, EntityInterface $entity) : bool {
    // Only act on fieldable entity.
    if (!$entity instanceof FieldableEntityInterface) {
      return FALSE;
    }

    // Search if there is a password field where the view mode is protected and
    // the entity is protected.
    $password_fields = $this->getPasswordFields($entity);
    foreach ($password_fields as $password_field) {
      $field_definition = $password_field->getFieldDefinition();
      /** @var array $protection_enabled_view_modes */
      $protection_enabled_view_modes = $field_definition->getSetting('view_modes');

      if (!in_array($view_mode, $protection_enabled_view_modes)) {
        // Currently no support for multiple password fields on the same
        // entity. So return as soon as possible.
        return FALSE;
      }

      /** @var array $field_values */
      $field_values = $password_field->getValue();
      if ($field_values[0]['is_protected']) {
        return TRUE;
      }

      // Currently no support for multiple password fields on the same
      // entity. So return as soon as possible.
      return FALSE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasUserAccessToEntity(EntityInterface $entity) : bool {
    // Only act on fieldable entity.
    if (!$entity instanceof FieldableEntityInterface) {
      return TRUE;
    }

    $password_fields = $this->getPasswordFields($entity);
    foreach ($password_fields as $password_field) {
      // To allow to use this method on a potentially not protected entity.
      /** @var array $field_values */
      $field_values = $password_field->getValue();
      if (!$field_values[0]['is_protected']) {
        return TRUE;
      }

      $field_instance_settings = $password_field->getFieldDefinition()->getSettings();

      // Entity password.
      if ($field_instance_settings['password_entity'] && $this->accessChecker->hasUserAccessToEntity($entity)) {
        return TRUE;
      }
      // Bundle password.
      elseif ($field_instance_settings['password_bundle'] && $this->accessChecker->hasUserAccessToBundle($entity)) {
        return TRUE;
      }
      // Global password.
      elseif ($field_instance_settings['password_global'] && $this->accessChecker->hasUserGlobalAccess()) {
        return TRUE;
      }

      // Currently no support for multiple password fields on the same
      // entity. So return as soon as possible.
      return FALSE;
    }

    // This should not happen, but in case this method is called on an entity
    // without password fields.
    return TRUE;
  }

  /**
   * Get the password fields.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to get fields.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface[]
   *   The list of non-empty password fields.
   */
  protected function getPasswordFields(FieldableEntityInterface $entity) : array {
    $password_fields = [];
    $fields = $entity->getFields();
    foreach ($fields as $field) {
      $field_definition = $field->getFieldDefinition();
      if ($field_definition->getType() == 'entity_access_password_password' && !$field->isEmpty()) {
        $password_fields[] = $field;
      }
    }

    return $password_fields;
  }

}
