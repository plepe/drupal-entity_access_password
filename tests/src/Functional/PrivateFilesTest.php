<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_access_password\Functional;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\FileInterface;

/**
 * Private files tests.
 *
 * @group entity_access_password
 */
class PrivateFilesTest extends EntityAccessPasswordFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'entity_access_password_session_backend',
  ];

  /**
   * The private file field name.
   *
   * @var string
   */
  protected string $privateFileFieldName = 'field_private_file';

  /**
   * The private file URI.
   *
   * @var string
   */
  protected string $privateFileUri = 'private://sub-directory/test_private.txt';

  /**
   * The private file content.
   *
   * @var string
   */
  protected string $privateFileContent = 'Drupal';

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The private file.
   *
   * @var \Drupal\file\FileInterface
   */
  protected FileInterface $privateFile;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->streamWrapperManager = $this->container->get('stream_wrapper_manager');
    $this->fileSystem = $this->container->get('file_system');
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->preparePrivateFile();
  }

  /**
   * Test a private file attached to a protected entity.
   */
  public function testPrivateFileAccess(): void {
    $node1 = $this->protectedNodes['entity'];
    $node2 = $this->protectedNodes['not_protected_private_file'];

    // Add the private file on node 1.
    $node1->set($this->privateFileFieldName, [
      'target_id' => $this->privateFile->id(),
    ]);
    $node1->save();

    $this->drupalLogin($this->bypassPasswordUser);
    // The bypass user should be able to access the private file.
    $this->drupalGet($this->privateFile->createFileUrl());
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($this->user);
    // The standard user should not be able to access the private file.
    $this->drupalGet($this->privateFile->createFileUrl());
    $this->assertSession()->statusCodeEquals(403);

    // Add the private file on node 2.
    $node2->set($this->privateFileFieldName, [
      'target_id' => $this->privateFile->id(),
    ]);
    $node2->save();

    // Now that an unprotected content has the file attached, the standard user
    // should have access.
    $this->drupalGet($this->privateFile->createFileUrl());
    $this->assertSession()->statusCodeEquals(200);

    // Keep only one node with the file attached to, and then enter the password
    // the standard user should have access to the file.
    $node2->set($this->privateFileFieldName, []);
    $node2->save();

    $this->drupalGet($this->privateFile->createFileUrl());
    $this->assertSession()->statusCodeEquals(403);

    $this->enterNodePassword('entity');

    $this->drupalGet($this->privateFile->createFileUrl());
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * {@inheritdoc}
   */
  protected function createFieldStorage(): void {
    parent::createFieldStorage();
    FieldStorageConfig::create([
      'field_name' => $this->privateFileFieldName,
      'entity_type' => 'node',
      'type' => 'file',
      'settings' => [
        'uri_scheme' => 'private',
      ],
      'cardinality' => 1,
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function createFieldsConfig(): void {
    parent::createFieldsConfig();
    FieldConfig::create([
      'field_name' => $this->privateFileFieldName,
      'label' => 'Private file',
      'entity_type' => 'node',
      'bundle' => 'eap_entity',
      'required' => FALSE,
      'settings' => [
        'file_directory' => '',
        'file_extensions' => 'txt',
        'max_filesize' => '',
      ],
    ])->save();
  }

  /**
   * Prepare the private file.
   */
  protected function preparePrivateFile(): void {
    // Create the physical file.
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $stream_wrapper */
    $stream_wrapper = $this->streamWrapperManager->getViaUri($this->privateFileUri);
    $directory_uri = $stream_wrapper->dirname($this->privateFileUri);
    $this->fileSystem->prepareDirectory($directory_uri, FileSystemInterface::CREATE_DIRECTORY);
    \file_put_contents($this->privateFileUri, $this->privateFileContent);

    // Create the file entity.
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')->create([
      'filename' => 'test_private.txt',
      'filemime' => 'text/plain',
      'uri' => $this->privateFileUri,
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file->save();
    $this->privateFile = $file;
  }

  /**
   * {@inheritdoc}
   */
  protected function setProtectedNodesStructure(): void {
    parent::setProtectedNodesStructure();
    $this->protectedNodesStructure += [
      'not_protected_private_file' => [
        'type' => 'eap_entity',
        'title' => 'Node not protected private file',
        'is_protected' => FALSE,
        'show_title' => TRUE,
        'hint' => 'Hint not protected private file',
        'password' => '',
      ],
    ];
  }

}
