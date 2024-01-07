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
            'landing-page-custom-url',                               // integration key
            '_fluentform_landing_page_custom_url',                         // option key
            'landing_page_custom_url_feed',                            // settings key
            11                                                // priority 
        );

        $this->description = '';                              // Integration details

        $this->logo = '/my-integration-image-file-path.png';  // Integration Logo
        $this->registerAdminHooks();
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
                    'apiKey' => [
                        'type'       => 'text',                                            // API key type
                        'label_tips' => __("Enter your Integration API Key", 'fluentform'), // Additional help text
                        'label'      => __('Integration API Key', 'fluentform'),           // Input Label
                    ]
                ],
                'hide_on_valid'    => true,                                                // Settings Input will be hidden on valid 
                'discard_settings' => [
                    'section_description' => 'Your AwesomeIntegration is Activated',       // Discard Settings Page Description
                    'button_text'         => 'Disconnect AwesomeIntegration',              // Discard Button Text
                    'data'                => [
                        'apiKey' => ''                                                     // Set API key to empty on discard
                    ],
                    'show_verify'         => true                                          // Show verification Option
                ]
            ];
    }

    public function pushIntegration($integrations, $formId)
    {
        $integrations[$this->integrationKey] = [
            'category'                => 'wp_core',
            'disable_global_settings' => 'yes',
            'logo'                    => $this->logo,
            'title'                   => $this->title . ' Integration',
            'is_active'               => $this->isConfigured()
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
                        'key'           => 'name',
                        'label'         => 'Name',
                        'required'      => true,
                        'placeholder'   => 'Your Feed Name',
                        'component'     => 'text'
                    ],

                    [
                        'key'            => 'additional_fields',
                        'label'          => 'Integration Fields',
                        'sub_title'      => 'Please specify the data ',
                        'required'       => true,
                        'component'      => 'map_fields',
                        'primary_fileds' => [
                            [
                                'key'           => 'fieldEmailAddress',
                                'label'         => 'Email Address',
                                'required'      => true,
                                'input_options' => 'emails'
                            ]
                        ],
                        'default_fields' => [
                            [
                                'name'     => 'first_name',
                                'label'    => esc_html__('First Name', 'fluentformpro'),
                                'required' => false
                            ],
                            [
                                'name'     => 'last_name',
                                'label'    => esc_html__('Last Name', 'fluentformpro'),
                                'required' => false
                            ],

                        ]
                    ],
                    [
                        'key'        => 'conditionals',
                        'label'      => 'Conditional Logics',
                        'tips'       => 'Push data to your Integration conditionally based on your submission values',
                        'component'  => 'conditional_block'
                    ],
                    [
                        'key'            => 'enabled',
                        'label'          => 'Status',
                        'component'      => 'checkbox-single',
                        'checkbox_label' => 'Enable This feed'
                    ]
                ],
                'integration_title' => $this->title
            ];
    }

    // This is an absttract method, so it's required.
    public function getMergeFields($list, $listId, $formId)
    {
        if (!$this->isConfigured()) {
            return false;
        }
    }
}
