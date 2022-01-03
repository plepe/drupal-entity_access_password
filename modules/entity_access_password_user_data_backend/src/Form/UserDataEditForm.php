<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\entity_access_password_user_data_backend\Service\UserDataBackend;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form to remove access (stored in user data) to the entity.
 */
class UserDataEditForm extends FormBase {

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
   * Constructor.
   *
   * @param \Drupal\user\UserDataInterface $userData
   *   The user data.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    UserDataInterface $userData,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->userData = $userData;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : self {
    return new self(
      $container->get('user.data'),
      $container->get('entity_type.manager')
    );
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
    $entity = $this->getEntityFromRouteMatch($this->getRouteMatch());

    // Not possible to know for which entity the form is built against.
    if (!$entity instanceof FieldableEntityInterface) {
      return [];
    }
    $name = sprintf(UserDataBackend::ENTITY_NAME_KEY, $entity->getEntityTypeId(), $entity->uuid());
    $form_state->addBuildInfo('user_data_name', $name);

    $form['users'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Users with access'),
      '#description' => $this->t('Check users to remove their access to this content.'),
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
    foreach ($users as $user_id => $user_email) {
      if ($user_email === 0) {
        continue;
      }

      // User selected to have access revoked.
      $this->userData->delete(UserDataBackend::MODULE_NAME, $user_id, $build_info['user_data_name']);
    }
  }

  /**
   * Retrieves entity from route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object as determined from the passed-in route match.
   */
  protected function getEntityFromRouteMatch(RouteMatchInterface $route_match) {
    $route = $route_match->getRouteObject();

    if ($route == NULL) {
      return NULL;
    }
    $parameter_name = $route->getOption('_eapudb_entity_type_id');
    // @phpstan-ignore-next-line
    return $route_match->getParameter($parameter_name);
  }

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
      $email = $user->getEmail();
      if ($email != NULL) {
        $options[$user->id()] = $email;
      }
    }
    return $options;
  }

}
