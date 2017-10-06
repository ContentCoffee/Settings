<?php

namespace Drupal\settings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\settings\Service\Settings;

/**
 * Class AddForm
 *
 * @package Drupal\settings\Form
 */
class AddForm extends ConfigFormBase
{

    /**
    * The settings.
    */
    protected $settings;

    /** @var EntityTypeBundleInfo */
    protected $bundleInfo;

  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\settings\Service\Settings $settings
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $bundleInfo
   */
    public function __construct(Settings $settings, EntityTypeBundleInfo $bundleInfo)
    {
        $this->settings = $settings;
        $this->bundleInfo = $bundleInfo;
    }

    /**
    * {@inheritdoc}
    */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('settings.settings'),
            $container->get('entity_type.bundle.info')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'settings_settings_add';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return [
            'settings.settings.add',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $key = null)
    {
        $setting = $this->settings->readKey($key);

        $form['label'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Label'),
            '#default_value' => $setting['label'] ? $setting['label'] : '',
            '#size' => 30,
            '#required' => true,
            '#maxlength' => 64,
            '#description' => $this->t('The name for this setting.'),
        );

        $form['key'] = array(
            '#title' => $this->t('Key'),
            '#type' => 'machine_name',
            '#default_value' => $setting['key'] ? $setting['key'] : '',
            '#maxlength' => 64,
            '#description' => $this->t('A unique name for this setting. It must only contain lowercase letters, numbers, and underscores.'),
            '#machine_name' => [
                'exists' => [$this, 'exists'],
            ],
            '#disabled' => !empty($setting['key']),
        );

        $options = [];
        foreach ((array) $this->bundleInfo->getBundleInfo('settings') as $key => $bundle) {
            $options[$key] = $bundle['label'];
        }
        $form['bundle'] = array(
            '#type' => 'select',
            '#required' => true,
            '#title' => $this->t('Bundle'),
            '#options' => $options,
            '#default_value' => $setting['bundle'] ? $setting['bundle'] : '',
        );

        $form['desc'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Description'),
            '#default_value' => $setting['desc'] ? $setting['desc'] : '',
            '#size' => 128,
            '#required' => true,
            '#maxlength' => 255,
            '#description' => $this->t('The end-user description for this setting.'),
        );

        return parent::buildForm($form, $form_state);
    }

    /**
     * Checks for an existing ECK entity type.
     *
     * @param string $key
     *   The settings ID.
     * @param array $element
     *   The form element.
     * @param FormStateInterface $form_state
     *   The form state.
     *
     * @return bool
     *   TRUE if this format already exists, FALSE otherwise.
     */
    public function exists($key, array $element, FormStateInterface $form_state)
    {
        $setting = $this->settings->readKey($key);
        if (!empty($setting['key'])) {
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->settings->updateKey([
            'key' => $form_state->getValue('key'),
            'label' => $form_state->getValue('label'),
            'bundle' => $form_state->getValue('bundle'),
            'desc' => $form_state->getValue('desc'),
        ]);

        parent::submitForm($form, $form_state);
    }
}
