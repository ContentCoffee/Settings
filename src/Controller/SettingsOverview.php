<?php

namespace Drupal\settings\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eck\Entity\EckEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\settings\Service\Settings;

/**
 * Provides a list of settings settings
 */
class SettingsOverview extends ControllerBase {

  /** @var Settings */
  protected $settings;

  /**
   * @param Settings $settings
   */
  public function __construct(Settings $settings) {
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('settings.settings')
    );
  }

  /**
   * Returns the admin screen with all keys.
   */
  public function overviewConfig() {
    $rows = [];

    foreach ((array) $this->settings->readKeys() as $key => $value) {
      $operations = [
        'data' => [
          '#type' => 'operations',
          '#links' => [
            'edit' => [
              'url' => Url::fromRoute(
                "settings.settings.add",
                [
                  'key' => $key,
                ],
                [
                  'query' => [
                    'destination' => Url::fromRoute(
                      "settings.settings"
                    )->toString(),
                  ],
                ]
              ),
              'title' => $this->t('Edit'),
            ],
            'delete' => [
              'url' => Url::fromRoute(
                "settings.settings.delete",
                [
                  'key' => $key,
                ],
                [
                  'query' => [
                    'destination' => Url::fromRoute(
                      "settings.settings"
                    )->toString(),
                  ],
                ]
              ),
              'title' => $this->t('Delete'),
            ],
          ],
        ],
      ];


      $rows[] = [
        $key,
        $value['label'],
        $value['desc'],
        $operations,
      ];
    }

    $build = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => [
        $this->t('Key'),
        $this->t('Label'),
        $this->t('Description'),
        $this->t('Operations'),
      ],
    ];

    return $build;
  }

  /**
   * Return the overview for editors.
   */
  public function overviewContent() {
    $keys = $this->settings->readKeys();

    $rows = [];

    // Get all content.
    foreach ((array) $this->settings->read() as $key => $value) {
      $operations = [
        'data' => [
          '#type' => 'operations',
          '#links' => [],
        ],
      ];

      $editOperation = [
        'url' => Url::fromRoute(
          'entity.' . $this->settings->getEntityType() . '.edit_form',
          [
            'settings' => $value->id(),
          ],
          [
            'query' => [
              'destination' => Url::fromRoute(
                "settings.content"
              )->toString(),
            ],
          ]
        ),
        'title' => $this->t('Edit'),
      ];
      $operations['data']['#links']['edit'] = $editOperation;

      if (\Drupal::moduleHandler()->moduleExists('content_translation')) {
        $translateOperation = [
          'url' => Url::fromRoute(
            'entity.' . $this->settings->getEntityType() . '.content_translation_overview',
            [
              'settings' => $value->id(),
            ],
            [
              'query' => [
                'destination' => Url::fromRoute(
                  "settings.content"
                )->toString(),
              ],
            ]
          ),
          'title' => $this->t('Translate'),
        ];
        $operations['data']['#links']['translate'] = $translateOperation;
      }


      if (isset($keys[$value->settings_key->value])) {
        $rows[] = [
          $keys[$value->settings_key->value]['label'],
          $keys[$value->settings_key->value]['desc'],
          $operations,
        ];
      }
    }

    $build = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#empty' => $this->t('No settings found'),
      '#header' => [
        $this->t('Label'),
        $this->t('Description'),
        $this->t('Operations'),
      ],
    ];

    return $build;
  }

  /**
   * Redirect to the correct setting (and tab).
   *
   * @param $key
   * @param $anchor
   * @param $destination
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function redirectSetting($key, $destination, $anchor) {
    $setting = $this->settings->readKey($key);

    // Redirect raw when we can't find the key.
    if (!$setting) {
      drupal_set_message(
        t('Unknown settings key: %key', ['%key' => $key]),
        'error'
      );

      return $this->redirect($destination);
    }

    /** @var EckEntity $settingData */
    $settingData = $this->settings->read($key);

    return $this->redirect(
      'entity.settings.edit_form',
      [
        'settings' => $settingData->id(),
      ],
      [
        'fragment' => $anchor,
        'query' => [
          'destination' => Url::fromRoute($destination)->toString(),
        ],
      ]
    );
  }
}
