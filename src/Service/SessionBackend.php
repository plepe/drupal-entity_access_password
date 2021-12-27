<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Handle access data in session.
 */
class SessionBackend implements AccessStorageInterface, AccessCheckerInterface {

  /**
   * Root session key for all session data.
   */
  public const SESSION_KEY = 'entity_access_password';

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   */
  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public function storeEntityAccess(ContentEntityInterface $entity) : void {
    /** @var array $session_data */
    $session_data = $this->session->get(self::SESSION_KEY, []);
    $session_data[$entity->getEntityTypeId()][$entity->bundle()][$entity->uuid()] = $entity->uuid();
    $this->session->set(self::SESSION_KEY, $session_data);
  }

  /**
   * {@inheritdoc}
   */
  public function storeEntityBundleAccess(ContentEntityInterface $entity) : void {
    $session_data = $this->session->get(self::SESSION_KEY, []);
    $session_data[$entity->getEntityTypeId()][$entity->bundle()]['bundle_access'] = TRUE;
    $this->session->set(self::SESSION_KEY, $session_data);
  }

  /**
   * {@inheritdoc}
   */
  public function storeGlobalAccess() : void {
    $session_data = $this->session->get(self::SESSION_KEY, []);
    $session_data['global_access'] = TRUE;
    $this->session->set(self::SESSION_KEY, $session_data);
  }

}
