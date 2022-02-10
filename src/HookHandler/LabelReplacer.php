<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\HookHandler;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_access_password\Cache\Context\EntityIsProtectedCacheContext;
use Drupal\entity_access_password\Service\PasswordAccessManagerInterface;
use Drupal\entity_access_password\Service\RouteParserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Replace entity labels where appropriate.
 *
 * "Show title" feature.
 */
class LabelReplacer implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The route parser.
   *
   * @var \Drupal\entity_access_password\Service\RouteParserInterface
   */
  protected RouteParserInterface $routeParser;

  /**
   * The password access manager.
   *
   * @var \Drupal\entity_access_password\Service\PasswordAccessManagerInterface
   */
  protected PasswordAccessManagerInterface $passwordAccessManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\entity_access_password\Service\RouteParserInterface $routeParser
   *   The route parser.
   * @param \Drupal\entity_access_password\Service\PasswordAccessManagerInterface $passwordAccessManager
   *   The password access manager.
   */
  public function __construct(
    ModuleHandlerInterface $moduleHandler,
    RouteParserInterface $routeParser,
    PasswordAccessManagerInterface $passwordAccessManager
  ) {
    $this->moduleHandler = $moduleHandler;
    $this->routeParser = $routeParser;
    $this->passwordAccessManager = $passwordAccessManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('module_handler'),
      $container->get('entity_access_password.route_parser'),
      $container->get('entity_access_password.password_access_manager')
    );
  }

  /**
   * Replace title if needed.
   *
   * @param array $variables
   *   The preprocess variables.
   */
  public function preprocessHtml(array &$variables): void {
    if (!isset($variables['head_title']['title'])) {
      return;
    }

    // If the Metatag module is enabled then admin can use the provided token
    // 'protected-label'.
    if ($this->moduleHandler->moduleExists('metatag')) {
      return;
    }

    $cacheableMetadata = new CacheableMetadata();
    $replacement = $this->getReplacement($cacheableMetadata);
    if ($replacement != NULL) {
      $variables['head_title']['title'] = $replacement;
    }

    $cacheableMetadata->applyTo($variables);
  }

  /**
   * Replace title if needed.
   *
   * @param array $variables
   *   The preprocess variables.
   */
  public function preprocessPageTitle(array &$variables): void {
    if (!isset($variables['title'])) {
      return;
    }

    $cacheableMetadata = new CacheableMetadata();
    $replacement = $this->getReplacement($cacheableMetadata);
    if ($replacement != NULL) {
      $variables['title'] = $replacement;
    }

    $cacheableMetadata->applyTo($variables);
  }

  /**
   * Replace label if needed.
   *
   * @param array $variables
   *   The preprocess variables.
   */
  public function preprocessNode(array &$variables): void {
    if (!isset($variables['label'][0]['#context']['value'])) {
      return;
    }

    /** @var \Drupal\node\NodeInterface $node */
    $node = $variables['node'];

    $cacheableMetadata = new CacheableMetadata();
    $replacement = $this->getReplacement($cacheableMetadata, $node);

    if ($replacement != NULL) {
      $variables['label'][0]['#context']['value'] = $replacement;
      // In case the label has already been rendered, ensure replacement is
      // taken into account.
      $variables['label']['#printed'] = FALSE;
    }

    $cacheableMetadata->applyTo($variables);
  }

  /**
   * Replace name if needed.
   *
   * @param array $variables
   *   The preprocess variables.
   */
  public function preprocessTaxonomyTerm(array &$variables): void {
    if (!isset($variables['name'][0]['#context']['value'])) {
      return;
    }

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $variables['term'];

    $cacheableMetadata = new CacheableMetadata();
    $replacement = $this->getReplacement($cacheableMetadata, $term);

    if ($replacement != NULL) {
      $variables['name'][0]['#context']['value'] = $replacement;
      // In case the name has already been rendered, ensure replacement is
      // taken into account.
      $variables['name']['#printed'] = FALSE;
    }

    $cacheableMetadata->applyTo($variables);
  }

  /**
   * Get the replacement if the entity label should be masked.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheableMetadata
   *   Cacheable metadata.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity if precised. NULL to use an entity from route context.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The replacement markup. NULL if nothing to change.
   */
  protected function getReplacement(CacheableMetadata $cacheableMetadata, ?EntityInterface $entity = NULL) {
    if ($entity == NULL) {
      $entity = $this->routeParser->getEntityFromCurrentRoute();
      if ($entity == NULL) {
        return NULL;
      }
    }

    if ($this->passwordAccessManager->isEntityLabelProtected($entity)) {
      $cacheableMetadata->addCacheContexts([EntityIsProtectedCacheContext::CONTEXT_ID . ':' . $entity->getEntityTypeId() . '||' . $entity->id()]);

      if (!$this->passwordAccessManager->hasUserAccessToEntity($entity)) {
        return $this->t('Protected entity');
      }
    }

    return NULL;
  }

}
