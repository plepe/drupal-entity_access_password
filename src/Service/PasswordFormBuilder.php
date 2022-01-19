<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\entity_access_password\Form\PasswordFormInterface;

/**
 * Provides a lazy builder for password form.
 */
class PasswordFormBuilder implements PasswordFormBuilderInterface, TrustedCallbackInterface {

  /**
   * The service machine name of this class.
   */
  public const SERVICE_ID = 'entity_access_password.password_form_builder';

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The password form.
   *
   * @var \Drupal\entity_access_password\Form\PasswordFormInterface
   */
  protected PasswordFormInterface $passwordForm;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\entity_access_password\Form\PasswordFormInterface $passwordForm
   *   The password form.
   */
  public function __construct(
    FormBuilderInterface $formBuilder,
    EntityTypeManagerInterface $entityTypeManager,
    PasswordFormInterface $passwordForm
  ) {
    $this->formBuilder = $formBuilder;
    $this->entityTypeManager = $entityTypeManager;
    $this->passwordForm = $passwordForm;
  }

  /**
   * {@inheritdoc}
   */
  public function build(string $helpText, string $hint, int $entityId, string $entityTypeId, string $fieldName): array {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $this->entityTypeManager->getStorage($entityTypeId)
      ->load($entityId);

    $itemsData = $entity->get($fieldName)->get(0);
    $this->passwordForm->setFormIdSuffix($entityTypeId . '_' . $entityId);

    return [
      '#theme' => 'entity_access_password_form',
      '#help_text' => new FormattableMarkup(Xss::filterAdmin($helpText), []),
      '#hint' => XSS::filter($hint),
      // @phpstan-ignore-next-line
      '#form' => $this->formBuilder->getForm($this->passwordForm, $itemsData),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['build'];
  }

}
