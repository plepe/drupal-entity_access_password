<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

use Drupal\entity_access_password\Plugin\Field\FieldType\EntityAccessPasswordItem;

/**
 * Provides a password validator manager.
 */
class PasswordValidatorManager implements ChainPasswordValidatorInterface {

  /**
   * Holds arrays of validators, keyed by priority.
   *
   * @var array
   */
  protected array $validators = [];

  /**
   * Holds the array of validators sorted by priority.
   *
   * Set to NULL if the array needs to be re-calculated.
   *
   * @var \Drupal\entity_access_password\Service\PasswordValidatorInterface[]|null
   */
  protected $sortedValidators;

  /**
   * {@inheritdoc}
   */
  public function addValidator(PasswordValidatorInterface $validator, $priority): void {
    $this->validators[$priority][] = $validator;
    // Force the validators to be re-sorted.
    $this->sortedValidators = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePassword(string $password, EntityAccessPasswordItem $fieldItem): bool {
    foreach ($this->getSortedValidators() as $validator) {
      if ($validator->validatePassword($password, $fieldItem)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Returns the sorted array of validators.
   *
   * @return \Drupal\entity_access_password\Service\PasswordValidatorInterface[]
   *   An array of validator objects.
   */
  protected function getSortedValidators() {
    if (!isset($this->sortedValidators)) {
      // Sort the validators according to priority.
      \krsort($this->validators);
      // Merge nested validators from $this->validators into
      // $this->sortedValidators.
      $this->sortedValidators = [];
      foreach ($this->validators as $validators) {
        $this->sortedValidators = \array_merge($this->sortedValidators, $validators);
      }
    }
    return $this->sortedValidators;
  }

}
