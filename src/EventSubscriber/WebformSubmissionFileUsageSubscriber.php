<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\EventSubscriber;

use Drupal\entity_access_password\Event\FileUsageEntityListEvent;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Get the webform submission parent entity if it exists.
 */
class WebformSubmissionFileUsageSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events['Drupal\entity_access_password\Event\FileUsageEntityListEvent'] = 'getWebformSubmissionParentEntities';
    return $events;
  }

  /**
   * Get the webform submission parent entity if it exists.
   *
   * @param \Drupal\entity_access_password\Event\FileUsageEntityListEvent $event
   *   The event containing the entities.
   */
  public function getWebformSubmissionParentEntities(FileUsageEntityListEvent $event) : void {
    $entities = $event->getEntities();
    foreach ($entities as $key => $entity) {
      if (!($entity instanceof WebformSubmissionInterface)) {
        continue;
      }

      $source_entity = $entity->getSourceEntity();

      // The webform submission is not from an entity, does not check.
      if ($source_entity == NULL) {
        unset($entities[$key]);
      }
      // The webform submission is from an entity, check access on this entity
      // instead of the webform submission.
      else {
        $entities[$key] = $source_entity;
      }
    }
    $event->setEntities($entities);
  }

}
