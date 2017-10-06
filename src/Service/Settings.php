<?php

namespace Drupal\settings\Service;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class Settings
 *
 * @package Drupal\settings\Service
 */
class Settings {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Settings constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $config_factory,
    EntityRepositoryInterface $entity_repository
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->config = $config_factory->getEditable('settings.settings');
    $this->entityRepository = $entity_repository;
  }

  public function getEntityType() {
    return 'settings';
  }

  /**
   * Shortcut to the config.
   */
  public function readKeys() {
    return $this->config->get('keys');
  }

  /**
   * Shortcut to the config.
   *
   * @param $keys
   */
  public function updateKeys($keys) {
    // Check our instances every time we do this.
    $this->config->set('keys', $keys)->save();
    $this->checkAndCreateEntities();
  }

  /**
   * Get the values of a single setting.
   *
   * @param $key
   *
   * @return bool
   */
  public function readKey($key) {
    $keys = $this->readKeys();

    if (!empty($keys[$key])) {
      return $keys[$key];
    }

    return FALSE;
  }

  /**
   * Deletes a key.
   *
   * @param $key
   */
  public function deleteKey($key) {
    $keys = $this->readKeys();

    if (array_key_exists($key, $keys)) {
      unset($keys[$key]);
    }

    $this->updateKeys($keys);
  }

  /**
   * Write a single setting.
   *
   * $values = [
   *     'key' => 'x',
   *     'label' => 'x',
   *     'bundle' => 'x',
   * ]
   *
   * @param $values
   */
  public function updateKey($values) {
    $keys = $this->readKeys();

    $keys[$values['key']] = [
      'key' => $values['key'],
      'label' => $values['label'],
      'bundle' => $values['bundle'],
      'desc' => $values['desc'],
    ];

    $this->updateKeys($keys);
  }

  /**
   * This functions checks that for each key there is a corresponding
   * entity in the given bundle, and creates one if it's not there.
   */
  public function checkAndCreateEntities() {
    foreach ((array) $this->readKeys() as $value) {
      // Create an entity query for our entity type.
      $query = $this->entityTypeManager->getStorage($this->getEntityType())->getQuery()
        ->condition('settings_key', $value['key'])
        ->condition('type', $value['bundle']);

      // Return the entities.
      $result = $query->execute();

      if (empty($result)) {
        $this
          ->entityTypeManager
          ->getStorage($this->getEntityType())
          ->create([
            'type' => $value['bundle'],
            'settings_key' => $value['key'],
          ])
          ->save();
      }
    }
  }

  /**
   * Get the all, or get them by key.
   *
   * @param null $key
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\EntityInterface[]|mixed
   */
  public function read($key = NULL) {
    // Create an entity query for our entity type.
    $query = $this->entityTypeManager->getStorage($this->getEntityType())->getQuery();

    if ($key != NULL) {
      $query = $query
        ->condition('settings_key', $key);
    }

    $ids = $query->execute();

    // Load them, and get them in the correct (=current) language.
    $entities = $this->entityTypeManager
      ->getStorage($this->getEntityType())
      ->loadMultiple($ids);

    foreach ((array) $entities as $k => $v) {
      $entities[$k] = $this->entityRepository->getTranslationFromContext($v);
    }

    if ($key != NULL) {
      return reset($entities);
    }
    return $entities;
  }

  /**
   * Shortcut to get data out of ordinary fields.
   *
   * @param $entity
   * @param $fields
   *
   * @return array
   */
  public function fill(Entity $entity, $fields) {
    $return = [];

    foreach ((array) $fields as $field_name => $type) {
      if ($entity->get($field_name) && !$entity->get($field_name)->isEmpty()) {
        switch ($type) {
          case 'textarea':
            $value = $entity->get($field_name)->first()->getValue();
            // TODO: Dep inject renderer here.
            $return[$field_name] = check_markup(
              $value['value'],
              $value['format']
            );
            break;

          case 'textfield':
            $return[$field_name] = $entity->get($field_name)->getString();
            break;

          case 'link':
            /** @var \Drupal\link\Plugin\Field\FieldType\LinkItem $linkItem */
            $linkItem = $entity->get($field_name)->get(0);
            $return[$field_name] = [
              'url' => $linkItem->getUrl()->toString(),
              'title' => $linkItem->title,
            ];
            break;

          default:
            $return[$field_name] = 'Unknown handler ' . $type . ' in Settings.php, line 229';
            break;
        }
      }
      else {
        $return[$field_name] = '';
      }
    }

    return $return;
  }

}
