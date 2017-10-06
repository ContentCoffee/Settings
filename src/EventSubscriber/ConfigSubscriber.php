<?php

namespace Drupal\settings\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Config\ConfigImporterEvent;

use Drupal\settings\Service\Settings;

/**
 * Event subscriber to act on config imports.
 *
 */
class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * The settings.
     */
    protected $settings;

    /**
     * Constructs the ConfigSnapshotSubscriber object.
     *
     * @param \Drupal\settings\Service\Settings $settings
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
    * Registers the methods in this class that should be listeners.
    *
    * @return array
    *   An array of event listener definitions.
    */
    public static function getSubscribedEvents()
    {
        $events[ConfigEvents::IMPORT][] = array('onConfigImporterImport', 40);
        return $events;
    }

    /**
     * Creates missing entities based on config.
     *
     * @param \Drupal\Core\Config\ConfigImporterEvent $event
     *   The Event to process.
     */
    public function onConfigImporterImport(ConfigImporterEvent $event)
    {
        $this->settings->checkAndCreateEntities();
    }
}
