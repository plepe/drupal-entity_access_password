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
    $fields = $entity->getFields();
    foreach ($fields as $field) {
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
      $field_definition = $field->getFieldDefinition();
      if ($field_definition->getType() == 'entity_access_password_password' && !$field->isEmpty()) {
        /** @var array $protection_enabled_view_modes */
        $protection_enabled_view_modes = $field_definition->getSetting('view_modes');

        if (!in_array($view_mode, $protection_enabled_view_modes)) {
          // Currently no support for multiple password fields on the same
          // entity. So return as soon as possible.
          return FALSE;
        }

        /** @var array $field_values */
        $field_values = $field->getValue();
        if ($field_values[0]['is_protected']) {
          return TRUE;
        }

        // Currently no support for multiple password fields on the same
        // entity. So return as soon as possible.
        return FALSE;
      }
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

    return $this->accessChecker->hasUserAccessToEntity($entity);
  }

}
