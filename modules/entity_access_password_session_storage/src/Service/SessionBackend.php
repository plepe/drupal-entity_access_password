<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_session_backend\Service;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_access_password\Service\AccessCheckerInterface;
use Drupal\entity_access_password\Service\AccessStorageInterface;
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
  public function storeEntityAccess(FieldableEntityInterface $entity) : void {
    /** @var array $session_data */
    $session_data = $this->session->get(self::SESSION_KEY, []);
    $session_data[$entity->getEntityTypeId()][$entity->bundle()][$entity->uuid()] = $entity->uuid();
    $this->session->set(self::SESSION_KEY, $session_data);
  }

  /**
   * {@inheritdoc}
   */
  public function storeEntityBundleAccess(FieldableEntityInterface $entity) : void {
    /** @var array $session_data */
    $session_data = $this->session->get(self::SESSION_KEY, []);
    $session_data[$entity->getEntityTypeId()][$entity->bundle()]['bundle_access'] = TRUE;
    $this->session->set(self::SESSION_KEY, $session_data);
  }

  /**
   * {@inheritdoc}
   */
  public function storeGlobalAccess() : void {
    /** @var array $session_data */
    $session_data = $this->session->get(self::SESSION_KEY, []);
    $session_data['global_access'] = TRUE;
    $this->session->set(self::SESSION_KEY, $session_data);
  }

  /**
   * {@inheritdoc}
   */
  public function hasUserAccessToEntity(FieldableEntityInterface $entity) : bool {
    // As there is currently no simple way to invalidate session's data
    // depending cache without creating too many variations, set a max-age at 0.
    // Also when there is a session cookie, the Internal Page Cache module does
    // not store cache, so only the Dynamic Page Cache is used and only this
    // part of the page will not be cacheable.
    $entity->mergeCacheMaxAge(0);

    /** @var array $session_data */
    $session_data = $this->session->get(self::SESSION_KEY, []);
    if (isset($session_data[$entity->getEntityTypeId()][$entity->bundle()][$entity->uuid()])) {
      return TRUE;
    }

    return FALSE;
  }

}
