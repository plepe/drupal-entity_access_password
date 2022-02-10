<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_access_password\Functional;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\entity_access_password\Form\SettingsForm;
use Drupal\entity_access_password\Service\PasswordAccessManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Provides helper methods for the EAP module's functional tests.
 */
abstract class EntityAccessPasswordFunctionalTestBase extends BrowserTestBase {

  /**
   * The global password used for tests.
   */
  public const TESTS_GLOBAL_PASSWORD = 'global';

  /**
   * The bundle password used for tests.
   */
  public const TESTS_BUNDLE_PASSWORD = 'bundle';

  /**
   * The entity password used for tests.
   */
  public const TESTS_ENTITY_PASSWORD = 'entity';

  /**
   * Test controller path.
   */
  public const TEST_CONTROLLER_PATH = '/entity_access_password_tests';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_access_password',
    'entity_access_password_tests',
    'node',
  ];

  /**
   * The field name.
   *
   * @var string
   */
  protected string $fieldName = 'field_eap';

  /**
   * The list of admin user permissions.
   *
   * @var array
   */
  protected array $adminUserPermissions = [
    'administer_entity_access_password',
    'bypass_password_protection',
    'bypass node access',
    'access content',
  ];

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * The list of user permissions.
   *
   * @var array
   */
  protected array $userPermissions = [
    'access content',
  ];

  /**
   * The test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $user;

  /**
   * The test nodes structure.
   *
   * @var array
   */
  protected array $protectedNodesStructure;

  /**
   * The test nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected array $protectedNodes;

  /**
   * The password hashing service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected PasswordInterface $password;

  /**
   * The display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected EntityDisplayRepositoryInterface $displayRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->password = $this->container->get('password');
    $this->displayRepository = $this->container->get('entity_display.repository');

    $this->setGlobalPassword();

    $this->drupalCreateContentType([
      'type' => 'eap_global',
      'name' => 'Global password',
    ]);
    $this->drupalCreateContentType([
      'type' => 'eap_bundle',
      'name' => 'Bundle password',
    ]);
    $this->drupalCreateContentType([
      'type' => 'eap_entity',
      'name' => 'Entity password',
    ]);
    $this->drupalCreateContentType([
      'type' => 'eap_all',
      'name' => 'All password levels',
    ]);

    $this->createFieldStorage();
    $this->createFieldsConfig();
    $this->configureViewModes();
    $this->setProtectedNodesStructure();
    $this->createTestContent();

    $this->user = $this->drupalCreateUser($this->userPermissions);
    $this->adminUser = $this->drupalCreateUser($this->adminUserPermissions);
  }

  /**
   * Create the field storage.
   */
  protected function createFieldStorage(): void {
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'entity_access_password_password',
      'settings' => [],
      'cardinality' => 1,
    ])->save();
  }

  /**
   * Create the field config.
   */
  protected function createFieldsConfig(): void {
    $field_config = FieldConfig::create([
      'field_name' => $this->fieldName,
      'label' => 'Entity access password',
      'entity_type' => 'node',
      'bundle' => 'eap_global',
      'required' => FALSE,
      'settings' => [
        'password_entity' => FALSE,
        'password_bundle' => FALSE,
        'password_global' => TRUE,
        'password' => '',
        'view_modes' => [
          'full' => 'full',
          'teaser' => 'teaser',
        ],
      ],
    ]);
    $field_config->save();

    $field_config = FieldConfig::create([
      'field_name' => $this->fieldName,
      'label' => 'Entity access password',
      'entity_type' => 'node',
      'bundle' => 'eap_bundle',
      'required' => FALSE,
      'settings' => [
        'password_entity' => FALSE,
        'password_bundle' => TRUE,
        'password_global' => FALSE,
        'password' => $this->password->hash(self::TESTS_BUNDLE_PASSWORD),
        'view_modes' => [
          'full' => 'full',
          'teaser' => 'teaser',
        ],
      ],
    ]);
    $field_config->save();

    $field_config = FieldConfig::create([
      'field_name' => $this->fieldName,
      'label' => 'Entity access password',
      'entity_type' => 'node',
      'bundle' => 'eap_entity',
      'required' => FALSE,
      'settings' => [
        'password_entity' => TRUE,
        'password_bundle' => FALSE,
        'password_global' => FALSE,
        'password' => '',
        'view_modes' => [
          'full' => 'full',
          'teaser' => 'teaser',
        ],
      ],
    ]);
    $field_config->save();

    $field_config = FieldConfig::create([
      'field_name' => $this->fieldName,
      'label' => 'Entity access password',
      'entity_type' => 'node',
      'bundle' => 'eap_all',
      'required' => FALSE,
      'settings' => [
        'password_entity' => TRUE,
        'password_bundle' => TRUE,
        'password_global' => TRUE,
        'password' => $this->password->hash(self::TESTS_BUNDLE_PASSWORD),
        'view_modes' => [
          'full' => 'full',
          'teaser' => 'teaser',
        ],
      ],
    ]);
    $field_config->save();
  }

  /**
   * Configure the view modes.
   *
   * Display the password form on the passwor dprotected view mode. And hide it
   * in the other view modes.
   */
  protected function configureViewModes(): void {
    $bundles = [
      'eap_global',
      'eap_bundle',
      'eap_entity',
      'eap_all',
    ];

    foreach ($bundles as $bundle) {
      $this->displayRepository->getViewDisplay('node', $bundle, PasswordAccessManagerInterface::PROTECTED_VIEW_MODE)
        ->setComponent($this->fieldName, [
          'type' => 'entity_access_password_form',
          'settings' => [
            'help_text' => 'Help text: ' . $bundle,
          ],
        ])
        ->save();

      $this->displayRepository->getViewDisplay('node', $bundle)
        ->removeComponent($this->fieldName)
        ->save();
      $this->displayRepository->getViewDisplay('node', $bundle, 'full')
        ->removeComponent($this->fieldName)
        ->save();
      $this->displayRepository->getViewDisplay('node', $bundle, 'teaser')
        ->removeComponent($this->fieldName)
        ->save();
    }
  }

  /**
   * Set the global password.
   */
  protected function setGlobalPassword(): void {
    $config = $this->config(SettingsForm::CONFIG_NAME);
    $config->set('global_password', $this->password->hash(self::TESTS_GLOBAL_PASSWORD));
    $config->save();
  }

  /**
   * Set protected nodes structure.
   */
  protected function setProtectedNodesStructure(): void {
    $this->protectedNodesStructure = [
      'global' => [
        'type' => 'eap_global',
        'title' => 'Node global',
        'is_protected' => TRUE,
        'show_title' => TRUE,
        'hint' => 'Hint global',
        'password' => '',
      ],
      'bundle' => [
        'type' => 'eap_bundle',
        'title' => 'Node bundle',
        'is_protected' => TRUE,
        'show_title' => TRUE,
        'hint' => 'Hint bundle',
        'password' => '',
      ],
      'entity' => [
        'type' => 'eap_entity',
        'title' => 'Node entity',
        'is_protected' => TRUE,
        'show_title' => TRUE,
        'hint' => 'Hint entity',
        'password' => self::TESTS_ENTITY_PASSWORD,
      ],
      'all' => [
        'type' => 'eap_all',
        'title' => 'Node all',
        'is_protected' => TRUE,
        'show_title' => TRUE,
        'hint' => 'Hint node all',
        'password' => self::TESTS_ENTITY_PASSWORD,
      ],
      'all_title_hidden' => [
        'type' => 'eap_all',
        'title' => 'Node title hidden all',
        'is_protected' => TRUE,
        'show_title' => FALSE,
        'hint' => 'Hint title hidden all',
        'password' => self::TESTS_ENTITY_PASSWORD,
      ],
    ];
  }

  /**
   * Create the test content.
   */
  protected function createTestContent(): void {
    foreach ($this->protectedNodesStructure as $key => $structure) {
      $this->protectedNodes[$key] = $this->drupalCreateNode([
        'type' => $structure['type'],
        'title' => $structure['title'],
        'status' => NodeInterface::PUBLISHED,
        $this->fieldName => [
          'is_protected' => $structure['is_protected'],
          'show_title' => $structure['show_title'],
          'hint' => $structure['hint'],
          'password' => $structure['password'],
        ],
      ]);
    }
  }

}
