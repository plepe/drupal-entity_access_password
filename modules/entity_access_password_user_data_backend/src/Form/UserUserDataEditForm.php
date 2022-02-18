<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\entity_access_password\Service\EntityTypePasswordBundleInfoInterface;
use Drupal\entity_access_password_user_data_backend\Service\UserDataBackendInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form class to manage access (stored in user data) on a user.
 */
class UserUserDataEditForm extends FormBase {

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
   * The account switcher.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected AccountSwitcherInterface $accountSwitcher;

  /**
   * The bundle infos.
   *
   * @var array
   */
  protected array $bundleInfos;

  /**
   * The entity type password bundle info.
   *
   * @var \Drupal\entity_access_password\Service\EntityTypePasswordBundleInfoInterface
   */
  protected EntityTypePasswordBundleInfoInterface $entityTypePasswordBundleInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->userData = $container->get('user.data');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->userDataBackend = $container->get('entity_access_password_user_data_backend.user_data_backend');
    $instance->accountSwitcher = $container->get('account_switcher');
    $instance->bundleInfos = $container->get('entity_type.bundle.info')->getAllBundleInfo();
    $instance->entityTypePasswordBundleInfo = $container->get('entity_access_password.entity_type_password_bundle_info');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'entity_access_password_user_data_backend_user_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?UserInterface $user = NULL): array {
    if ($user == NULL) {
      return [];
    }

    $storage = $form_state->getStorage();
    $storage['user'] = $user;
    $form_state->setStorage($storage);

    $this->accountSwitcher->switchTo($user);

    $form['#title'] = $this->t('@username password user data', [
      '@username' => $user->getDisplayName(),
    ]);

    $this->buildEntityLevelSection($form, $form_state, $user);
    $this->buildBundleLevelSection($form, $form_state, $user);
    $this->buildGlobalLevelSection($form, $form_state);

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $this->accountSwitcher->switchBack();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $storage = $form_state->getStorage();
    /** @var \Drupal\user\UserInterface $user */
    $user = $storage['user'];
    $this->accountSwitcher->switchTo($user);

    $this->entityAccessSubmit($form, $form_state, $user);
    $this->bundleAccessSubmit($form, $form_state, $user);
    $this->globalAccessSubmit($form, $form_state, $user);

    $this->accountSwitcher->switchBack();
    $this->messenger()->addStatus($this->t('Password access data updates for the user @username.', [
      '@username' => $user->getDisplayName(),
    ]));
  }

  /**
   * Generate entity level access section.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\user\UserInterface $user
   *   The user to alter access.
   */
  protected function buildEntityLevelSection(array &$form, FormStateInterface $form_state, UserInterface $user): void {
    $form['entity_access'] = [
      '#type' => 'details',
      '#title' => $this->t('Entity accesses'),
      '#open' => TRUE,
    ];

    $form['entity_access']['entities'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Entities with access'),
      '#description' => $this->t("Check entities to remove the user's access to it."),
      '#options' => $this->getEntitiesOptions($user),
    ];

    $form['entity_access']['entity_revoke_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Revoke all'),
      '#description' => $this->t('Remove access to all the entities listed above.'),
      '#default_value' => FALSE,
    ];

    $form['entity_access']['entity_grant_area'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Grant access to'),
      '#description' => $this->t('List the entities for which you want to grant access to the user. One per line or comma separated list.<br>Format: <em>[ENTITY_TYPE_ID]:[ENTITY_ID]</em>. Example: node:42.'),
    ];
  }

  /**
   * Get the options.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to get entity access list.
   *
   * @return array
   *   The array of entities with access.
   */
  protected function getEntitiesOptions(UserInterface $user): array {
    $options = [];
    /** @var int $user_id */
    $user_id = $user->id();
    /** @var array $access */
    $access = $this->userData->get(UserDataBackendInterface::MODULE_NAME, $user_id);

    // Prepare queries.
    $entity_query_uuids = [];
    foreach (\array_keys($access) as $user_data_name) {
      $parsed_name = \explode('||', $user_data_name);

      // Avoid global access for example.
      if (\count($parsed_name) != (int) 2) {
        continue;
      }

      $entity_type_id = $parsed_name[0];
      $entity_uuid = $parsed_name[1];
      if (!isset($entity_query_uuids[$entity_type_id])) {
        $entity_query_uuids[$entity_type_id] = [];
      }
      $entity_query_uuids[$entity_type_id][] = $entity_uuid;
    }

    foreach ($entity_query_uuids as $entity_type_id => $entity_uuids) {
      $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
      /** @var array $entity_ids */
      $entity_ids = $entity_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uuid', $entity_uuids, 'IN')
        ->execute();

      /** @var \Drupal\Core\Entity\FieldableEntityInterface[] $entities */
      $entities = $entity_storage->loadMultiple($entity_ids);
      foreach ($entities as $entity) {
        $options[$this->userDataBackend->getEntityName($entity)] = $this->t('@entity_bundle: @entity_label', [
          '@entity_bundle' => $this->bundleInfos[$entity_type_id][$entity->bundle()]['label'],
          '@entity_label' => $entity->label(),
        ]);
      }
    }

    return $options;
  }

  /**
   * Process entity level access.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\user\UserInterface $user
   *   The user to alter access.
   */
  protected function entityAccessSubmit(array $form, FormStateInterface $form_state, UserInterface $user): void {
    $entity_revoke_all = $form_state->getValue('entity_revoke_all');
    /** @var int $user_id */
    $user_id = $user->id();

    // Access revocation.
    /** @var array $entities */
    $entities = $form_state->getValue('entities');
    foreach ($entities as $user_data_name => $entity_display_option) {
      if (!$entity_revoke_all && $entity_display_option === 0) {
        continue;
      }

      // Entity selected to have access revoked.
      $this->userData->delete(UserDataBackendInterface::MODULE_NAME, $user_id, $user_data_name);
    }

    // Access granting.
    /** @var string $entity_grant_area */
    $entity_grant_area = $form_state->getValue('entity_grant_area');
    $entity_grant_list = \explode(',', \str_replace(["\r", "\n"], ',', $entity_grant_area));
    foreach ($entity_grant_list as $entity_grant) {
      $entity_grant = \trim($entity_grant);
      if (empty($entity_grant)) {
        continue;
      }

      $parsed_entity_grant = \explode(':', $entity_grant);
      if (\count($parsed_entity_grant) != (int) 2) {
        continue;
      }

      $entity_type_id = $parsed_entity_grant[0];
      $entity_id = $parsed_entity_grant[1];
      $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);

      /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
      $entity = $entity_storage->load($entity_id);
      if ($entity == NULL) {
        $this->messenger()->addWarning($this->t('No entities found for the type @entity_type_id and the ID @entity_id.', [
          '@entity_type_id' => $entity_type_id,
          '@entity_id' => $entity_id,
        ]));
        continue;
      }

      $this->userData->set(UserDataBackendInterface::MODULE_NAME, $user_id, $this->userDataBackend->getEntityName($entity), TRUE);
    }
  }

  /**
   * Generate bundle level access section.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\user\UserInterface $user
   *   The user to alter access.
   */
  protected function buildBundleLevelSection(array &$form, FormStateInterface $form_state, UserInterface $user): void {
    /** @var int $user_id */
    $user_id = $user->id();

    $form['bundle_access'] = [
      '#type' => 'details',
      '#title' => $this->t('Bundle accesses'),
      '#open' => TRUE,
    ];

    $bundle_options = [];
    $bundle_default_value = [];
    $password_infos = $this->entityTypePasswordBundleInfo->getAllPasswordBundleInfo();
    foreach ($password_infos as $entity_type_id => $entity_infos) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      $entity_type = $entity_infos['entity_type'];
      foreach ($entity_infos['bundles'] as $bundle_id => $bundle_infos) {
        $bundle_user_data_name = $this->userDataBackend->getBundleName($entity_type_id, $bundle_id);
        $bundle_options[$bundle_user_data_name] = $this->t('@entity_type: @entity_bundle', [
          '@entity_type' => $entity_type->getLabel(),
          '@entity_bundle' => $bundle_infos['label'],
        ]);
        $bundle_default_value[$bundle_user_data_name] = $this->userData->get(UserDataBackendInterface::MODULE_NAME, $user_id, $bundle_user_data_name) ? $bundle_user_data_name : FALSE;
      }
    }

    $form['bundle_access']['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Entity bundles with a password field'),
      '#description' => $this->t('Check the bundles for which you want to grant access to the user.'),
      '#options' => $bundle_options,
      '#default_value' => $bundle_default_value,
    ];
  }

  /**
   * Process bundle level access.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\user\UserInterface $user
   *   The user to alter access.
   */
  protected function bundleAccessSubmit(array $form, FormStateInterface $form_state, UserInterface $user): void {
    /** @var array $bundle_access */
    $bundle_access = $form_state->getValue('bundles');
    /** @var int $user_id */
    $user_id = $user->id();

    foreach ($bundle_access as $user_data_name => $value) {
      if ($value) {
        $this->userData->set(UserDataBackendInterface::MODULE_NAME, $user_id, $user_data_name, TRUE);
      }
      else {
        $this->userData->delete(UserDataBackendInterface::MODULE_NAME, $user_id, $user_data_name);
      }
    }
  }

  /**
   * Generate global level access section.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function buildGlobalLevelSection(array &$form, FormStateInterface $form_state): void {
    $form['global_access'] = [
      '#type' => 'details',
      '#title' => $this->t('Global access'),
      '#open' => TRUE,
    ];

    $form['global_access']['user_has_global_access'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('The user has global access.'),
      '#default_value' => $this->userDataBackend->hasUserGlobalAccess(),
    ];
  }

  /**
   * Process global level access.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\user\UserInterface $user
   *   The user to alter access.
   */
  protected function globalAccessSubmit(array $form, FormStateInterface $form_state, UserInterface $user): void {
    $user_has_global_access = $form_state->getValue('user_has_global_access');
    if ($user_has_global_access) {
      $this->userDataBackend->storeGlobalAccess();
    }
    else {
      /** @var int $user_id */
      $user_id = $user->id();
      $this->userData->delete(UserDataBackendInterface::MODULE_NAME, $user_id, $this->userDataBackend->getGlobalName());
    }
  }

}
