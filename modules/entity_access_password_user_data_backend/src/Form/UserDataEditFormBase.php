<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_access_password_user_data_backend\Service\UserDataBackend;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base form class to remove access (stored in user data).
 */
abstract class UserDataEditFormBase extends FormBase {

  /**
   * The user data.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected UserDataInterface $userData;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : self {
    $instance = parent::create($container);
    $instance->userData = $container->get('user.data');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'entity_access_password_user_data_backend_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) : array {
    $name = $this->getUserDataName();

    // Not possible to know for which entity the form is built against.
    if (empty($name)) {
      return [];
    }
    $form_state->addBuildInfo('user_data_name', $name);

    $form['users'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Users with access'),
      '#description' => $this->t('Check users to remove their access.'),
      '#options' => $this->getUsersOptions($name),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) : void {
    $build_info = $form_state->getBuildInfo();
    /** @var array $users */
    $users = $form_state->getValue('users');
    foreach ($users as $user_id => $user_display_option) {
      if ($user_display_option === 0) {
        continue;
      }

      // User selected to have access revoked.
      $this->userData->delete(UserDataBackend::MODULE_NAME, $user_id, $build_info['user_data_name']);
    }
  }

  /**
   * Retrieve user data name.
   *
   * @return string
   *   The user data name. Empty string if not possible to determine one.
   */
  abstract protected function getUserDataName() : string;

  /**
   * Get the options.
   *
   * @param string $name
   *   The name of the user data entry.
   *
   * @return array
   *   The array of user with access in their user data.
   */
  protected function getUsersOptions(string $name) : array {
    /** @var array $entity_access */
    $entity_access = $this->userData->get(UserDataBackend::MODULE_NAME, NULL, $name);

    $uids = array_keys($entity_access);

    /** @var \Drupal\user\UserInterface[] $users */
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($uids);
    $options = [];
    foreach ($users as $user) {
      $options[$user->id()] = $this->formatUserOption($user);
    }
    return $options;
  }

  /**
   * Format a user option.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to format.
   *
   * @return string
   *   The formatted user option.
   */
  protected function formatUserOption(UserInterface $user) : string {
    $option = (string) $user->getDisplayName();
    $email = $user->getEmail();
    if ($email != NULL) {
      $option .= " ($email)";
    }
    return $option;
  }

}
