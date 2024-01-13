<?php

namespace FluentFormLandingCustomURL\Integration;

use FluentForm\App\Modules\Form\FormFieldsParser;
use FluentForm\App\Http\Controllers\IntegrationManagerController;
use FluentForm\Framework\Foundation\Application;
use FluentForm\Framework\Helpers\ArrayHelper;


class FluentFormCustomURL extends IntegrationManagerController
{

    public function __construct(Application $application)
    {
        parent::__construct(
            $application,
            'Landing Page Custom Url',                        // title
            'landing-page-custom-url',                        // integration key
            '_fluentform_landing_page_custom_url',            // option key
            'landing_page_custom_url_feed',                   // settings key
            11                                                // priority 
        );

        $this->description = '';                              // Integration details

        $this->logo = FF_LANDING_CUSTOM_URL_URL . 'landing-custom-url-integration-logo.png';  // Integration Logo
        $this->registerHooks();
        $this->registerAdminHooks();
    }

    public function registerHooks()
    {
    }

    public function getGlobalFields($fields)
    {
        return
            [
                'logo'             => $this->logo,                                         // Logo Path which was set in constructor
                'menu_title'       => __('Integration Settings', 'fluentform'),            // Integration Settings Title
                'menu_description' => __('Description', 'fluentform'),                     // Integration Settings Details
                'valid_message'    => __('Your API Key is valid', 'fluentform'),           // Valid API Message 
                'invalid_message'  => __('Your API Key is not valid', 'fluentform'),       // Invalid API Message
                'save_button_text' => __('Save Settings', 'fluentform'),                   // Settings Save Button tTxt
                'fields'           => [
                    'url_prefix' => [
                        'type'       => 'text',                                            // API key type
                        'label_tips' => __("Enter your URL prefix", 'fluentform'), // Additional help text
                        'label'      => __('URL prefix', 'fluentform'),           // Input Label
                    ]
                ],
                'hide_on_valid'    => true,                                                // Settings Input will be hidden on valid 
                'discard_settings' => [
                    'section_description' => 'Your AwesomeIntegration is Activated',       // Discard Settings Page Description
                    'button_text'         => 'Disconnect AwesomeIntegration',              // Discard Button Text
                    'data'                => [
                        'url_prefix' => ''                                                     // Set API key to empty on discard
                    ],
                    'show_verify'         => true                                          // Show verification Option
                ]
            ];
    }

    public function getGlobalSettings($settings = [])
    {
        $globalSettings = get_option($this->optionKey);
        if (!$globalSettings) {
            $globalSettings = [];
        }
        $defaults = [
            'url_prefix' => '',
            'status' => ''
        ];

        return wp_parse_args($globalSettings, $defaults);
    }

    public function saveGlobalSettings($settings)
    {

        if (empty($settings['url_prefix'])) {

            $integrationSettings = [
                'url_prefix' => '',
                'status' => false
            ];
            update_option($this->optionKey, $integrationSettings, 'no');


            wp_send_json_success([
                'message' => __('Your URL prefix has been updated.', 'ffdropbox'),
                'data' => [
                    'settings' => $settings
                ],
                'status' => false
            ], 200);
        }


        try {
            $oldSettings = $this->getGlobalSettings($settings);

            if (empty($settings['url_prefix'])) {
                throw new \Exception('Please Enter the URL Prefix you want to use.');
            }

            if (ArrayHelper::get($oldSettings, 'url_prefix') && ArrayHelper::isTrue($oldSettings, 'status')) {
                wp_send_json_success([
                    'message' => __('Your connection is up and running', 'ffdropbox'),
                    'status' => true
                ], 200);
            }
            $url_prefix = sanitize_textarea_field($settings['url_prefix']);


            $result['url_prefix'] = $url_prefix;
            $result['status'] = true;

            update_option($this->optionKey, $result, 'no');
            flush_rewrite_rules();
        } catch (\Exception $exception) {
            wp_send_json_error([
                'message' => $exception->getMessage()
            ], 400);
        }

        wp_send_json_success([
            'message' => __('Your landing page URL prefix has been set.', 'ffdropbox'),
            'status' => true
        ], 200);
    }

    public function pushIntegration($integrations, $formId)
    {
        // if (!$this->isConfigured()) {
        //     return $integrations;
        // }
        $integrations[$this->integrationKey] = [
            'category'                => 'wp_core',
            // 'disable_global_settings' => 'yes',
            'logo'                    => $this->logo,
            'title'                   => $this->title . ' Integration',
            'is_active'               => $this->isConfigured(),
            'configure_title'       => 'Configuration required!',
            'global_configure_url' => admin_url('admin.php?page=fluent_forms_settings#general-' . $this->integrationKey . '-settings'),
            'configure_message' => $this->title . ' is not configured yet! Please configure your ' . $this->title . ' first',
            'configure_button_text' => 'Configure ' . $this->title
        ];

        return $integrations;
    }

    public function getIntegrationDefaults($settings, $formId = null)
    {
        return
            [
                'name'                   => '',
                'id'                     => '',
                'fieldEmailAddress'      => '',
                'custom_field_mappings'  => (object)[],
                'default_fields'         => (object)[],
                'conditionals'           => [
                    'conditions' => [],
                    'status'     => false,
                    'type'       => 'all'
                ],
                'enabled' => true
            ];
    }

    public function getSettingsFields($settings, $formId = null)
    {
        return
            [
                'fields' => [
                    [
                        'key'           => 'slug',
                        'label'         => 'Slug',
                        'required'      => true,
                        'placeholder'   => 'my-amazing-form',
                        'component'     => 'text'
                    ]
                ],
                'integration_title' => $this->title
            ];
    }

    public function getMergeFields($list, $listId, $formId)
    {
        if (!$this->isConfigured()) {
            return false;
        }
    }
}
