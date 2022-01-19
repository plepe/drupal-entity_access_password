<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

/**
 * Provides a lazy builder for password form.
 */
interface PasswordFormBuilderInterface {

  /**
   * Lazy builder callback for displaying a password form.
   *
   * @param string $helpText
   *   The help text to display.
   * @param string $hint
   *   The hint to display.
   * @param int $entityId
   *   The entity ID.
   * @param string $entityTypeId
   *   The entity type ID.
   * @param string $fieldName
   *   The field name.
   *
   * @return array
   *   A render array for the password form.
   */
  public function build(string $helpText, string $hint, int $entityId, string $entityTypeId, string $fieldName): array;

}
