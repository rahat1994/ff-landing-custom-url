<?php

namespace FluentFormLandingCustomURL\Integration;

use FluentForm\App\Helpers\Helper;
use FluentForm\Framework\Helpers\ArrayHelper;
use FluentFormPro\classes\SharePage\SharePage;

class URLRewrite
{
    public $urlPrefix;
    public $metaKey = 'landing_page_custom_url_feed';
    public function __construct($urlPrefix)
    {
        $this->urlPrefix = $urlPrefix;
        add_action('init', function () use ($urlPrefix) {
            add_rewrite_endpoint($urlPrefix, EP_ALL);
        });
        add_action('wp', [$this, 'wp']);
    }

    public function rewriteEndpoint()
    {
        add_rewrite_endpoint($this->urlPrefix, EP_ALL);
    }

    public function wp()
    {
        global $wp_query, $wpdb;
        if (isset($wp_query->query_vars[$this->urlPrefix])) {
            $formString = $wp_query->query_vars[$this->urlPrefix];
            if (!$formString) {
                return;
            }
            $array = explode('/', $formString);

            $formSlug = $array[0];

            $tableName = $wpdb->prefix . 'fluentform_form_meta';
            $sql = 'SELECT * FROM ' . $tableName . ' WHERE meta_key = %s';
            $results = $wpdb->get_results($wpdb->prepare($sql, $this->metaKey));

            foreach ($results as $key => $result) {

                $landingPageCustomUrlFeed = json_decode($result->value, true);

                if ($landingPageCustomUrlFeed['slug'] == $formSlug) {
                    $formId = $result->form_id;
                    $this->renderForm($formId);
                }
            }
        }
    }

    public function renderForm($formId)
    {
        $hasConfirmation = false;
        if (isset($_REQUEST['entry_confirmation'])) {
            do_action_deprecated(
                'fluentformpro_entry_confirmation',
                [
                    $_REQUEST
                ],
                FLUENTFORM_FRAMEWORK_UPGRADE,
                'fluentform/entry_confirmation',
                'Use fluentform/entry_confirmation instead of fluentformpro_entry_confirmation.'
            );
            do_action('fluentform/entry_confirmation', $_REQUEST);
            $hasConfirmation = true;
        }

        $form = wpFluent()->table('fluentform_forms')->where('id', $formId)->first();

        if (!$form) {
            return;
        }

        $settings = $this->getSettings($formId);

        if (ArrayHelper::get($settings, 'status') != 'yes') {
            // return;
        }

        $pageTitle = $form->title;

        if ($settings['title']) {
            $pageTitle = $settings['title'];
        }

        add_action('wp_enqueue_scripts', function () use ($formId) {
            $theme = Helper::getFormMeta($formId, '_ff_selected_style');
            $styles = $theme ? [$theme] : [];

            do_action('fluentform/load_form_assets', $formId, $styles);
            wp_enqueue_style('fluent-form-styles');
            wp_enqueue_style('fluentform-public-default');
            wp_enqueue_script('fluent-form-submission');
        });

        $backgroundColor = ArrayHelper::get($settings, 'color_schema');

        if ($backgroundColor == 'custom') {
            $backgroundColor = ArrayHelper::get($settings, 'custom_color');
        }


        $landingContent = '[fluentform id="' . $formId . '"]';
        if (!$hasConfirmation) {
            $salt = ArrayHelper::get($settings, 'share_url_salt');
            if ($salt && $salt != ArrayHelper::get($_REQUEST, 'form')) {
                $landingContent = __('Sorry, You do not have access to this form', 'fluentformpro');
                $pageTitle = __('No Access', 'fluentformpro');
                $settings['title'] = '';
                $settings['description'] = '';
            }
        }

        $data = [
            'settings'        => $settings,
            'title'           => $pageTitle,
            'form_id'         => $formId,
            'form'            => $form,
            'bg_color'        => $backgroundColor,
            'landing_content' => $landingContent,
            'has_header'      => $settings['logo'] || $settings['title'] || $settings['description'],
            'isEmbeded' => !!ArrayHelper::get($_GET, 'embedded')
        ];

        $data = apply_filters_deprecated(
            'fluentform_landing_vars',
            [
                $data,
                $formId
            ],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/landing_vars',
            'Use fluentform/landing_vars instead of fluentform_landing_vars.'
        );

        $landingVars = apply_filters('fluentform/landing_vars', $data, $formId);

        $this->loadPublicView($landingVars);
    }

    public function loadPublicView($landingVars)
    {
        add_action('wp_enqueue_scripts', function () {
            wp_enqueue_style(
                'fluent-form-landing',
                FLUENTFORMPRO_DIR_URL . 'public/css/form_landing.css',
                [],
                FLUENTFORMPRO_VERSION
            );
        });

        add_filter('pre_get_document_title', function ($title) use ($landingVars) {
            $separator = apply_filters('document_title_separator', '-');
            return $landingVars['title'] . ' ' . $separator . ' ' . get_bloginfo('name', 'display');
        });

        // let's deregister all the style and scripts here
        add_action('wp_print_scripts', function () {
            global $wp_scripts;
            $contentUrl = content_url();
            if ($wp_scripts) {
                foreach ($wp_scripts->queue as $script) {

                    if (!isset($wp_scripts->registered[$script])) {
                        continue;
                    }

                    $src = $wp_scripts->registered[$script]->src;
                    $shouldLoad = strpos($src, $contentUrl) !== false && (
                        strpos($src, 'fluentform') !== false ||
                        strpos($src, 'AffiliateWP') !== false
                    );

                    if (!$shouldLoad) {
                        wp_dequeue_script($wp_scripts->registered[$script]->handle);
                        // wp_deregister_script($wp_scripts->registered[$script]->handle);
                    }
                }
            }
        }, 1);

        if (isset($_GET['embedded'])) {
            add_action('wp_print_styles', function () {
                global $wp_styles;
                if ($wp_styles) {
                    foreach ($wp_styles->queue as $style) {
                        $src = $wp_styles->registered[$style]->src;
                        if (!strpos($src, 'fluentform') !== false) {
                            wp_dequeue_style($wp_styles->registered[$style]->handle);
                        }
                    }
                }
            }, 1);
            if ($landingVars['settings']['design_style'] == 'modern') {
                $landingVars['settings']['design_style'] = 'classic';
                $landingVars['bg_color'] = '#fff';
            }
        }

        status_header(200);
        echo $this->loadView('landing_page_view', $landingVars);
        exit(200);
    }

    public function loadView($view, $data = [])
    {
        $file = FLUENTFORMPRO_DIR_PATH . 'src/views/' . $view . '.php';
        extract($data);
        ob_start();
        include($file);
        return ob_get_clean();
    }

    public function getSettings($formId)
    {
        $settings = Helper::getFormMeta($formId, $this->metaKey, []);

        $defaults = [
            'status'           => 'no',
            'logo'             => '',
            'title'            => '',
            'description'      => '',
            'color_schema'     => '#4286c4',
            'custom_color'     => '#4286c4',
            'design_style'     => 'modern',
            'featured_image'   => '',
            'background_image' => '',
            'layout'           => 'default',
            'media'            => fluentFormGetRandomPhoto(),
            'brightness'       => 0,
            'alt_text'         => '',
            'media_x_position' => 50,
            'media_y_position' => 50
        ];

        return wp_parse_args($settings, $defaults);
    }
}
