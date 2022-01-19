<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_access_password_user_data_backend\Service\UserDataBackend;
use Drupal\entity_access_password_user_data_backend\Service\UserDataBackendInterface;
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
   * The user data backend.
   *
   * @var \Drupal\entity_access_password_user_data_backend\Service\UserDataBackendInterface
   */
  protected UserDataBackendInterface $userDataBackend;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : self {
    $instance = parent::create($container);
    $instance->userData = $container->get('user.data');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->userDataBackend = $container->get('entity_access_password_user_data_backend.user_data_backend');
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

    $form['revoke_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Revoke all'),
      '#description' => $this->t('Remove access to the users listed above.'),
      '#default_value' => FALSE,
    ];

    $form['grant_area'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Grant access to'),
      '#description' => $this->t('List the usernames or email addresses of the users you want to grant access to. One per line or comma separated list.'),
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
    $revoke_all = $form_state->getValue('revoke_all');

    // Access revocation.
    /** @var array $users */
    $users = $form_state->getValue('users');
    foreach ($users as $user_id => $user_display_option) {
      if (!$revoke_all && $user_display_option === 0) {
        continue;
      }

      // User selected to have access revoked.
      $this->userData->delete(UserDataBackend::MODULE_NAME, $user_id, $build_info['user_data_name']);
    }

    // Access granting.
    $grant_area = $form_state->getValue('grant_area');
    $grant_list = explode(',', str_replace(["\r", "\n"], ',', $grant_area));
    $user_storage = $this->entityTypeManager->getStorage('user');
    foreach ($grant_list as $user_name_or_email) {
      $user_name_or_email = trim($user_name_or_email);
      if (empty($user_name_or_email)) {
        continue;
      }

      $grant_user_ids = $user_storage->getQuery('OR')
        ->condition('name', $user_name_or_email)
        ->condition('mail', $user_name_or_email)
        ->execute();

      if (empty($grant_user_ids)) {
        $this->messenger()->addWarning($this->t('No user found for the username or email address: @text.', [
          '@text' => $user_name_or_email,
        ]));
        continue;
      }

      $grant_user_id = array_shift($grant_user_ids);
      $this->userData->set(UserDataBackend::MODULE_NAME, $grant_user_id, $build_info['user_data_name'], TRUE);
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
