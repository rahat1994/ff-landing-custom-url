<?php

namespace FluentFormLandingCustomURL\Integration;


class URLRewrite
{
    public $staticEndpoint = 'landing-page';

    public function __construct()
    {
        add_action('init', [$this, 'rewriteRules']);
        add_filter('wp', [$this, 'wp']);
        add_action('template_redirect', [$this, 'templateRedirect']);
    }

    public function rewriteRules()
    {
        add_rewrite_endpoint($this->staticEndpoint, EP_ALL);
    }

    public function wp()
    {
        global $wp_query;
        if (isset($wp_query->query_vars[$this->staticEndpoint])) {
            $formString = $wp_query->query_vars[$this->staticEndpoint];
            if (!$formString) {
                return;
            }
            $array = explode('/', $formString);

            $formId = $array[0];

            if (!$formId || !is_numeric($formId)) {
                return;
            }

            $secretKey = '';
            if (count($array) > 1) {
                $secretKey = $array[1];
            }

            $paramKey = apply_filters('fluentform/conversational_url_slug', 'fluent-form');

            $_GET[$paramKey] = $formId;
            $_REQUEST[$paramKey] = $formId;

            $request = wpFluentForm('request');
            $request->set($paramKey, $formId);
            $request->set('form', $secretKey);
        }
    }

    public function templateRedirect()
    {
        global $wp_query;
        if ($wp_query->get('ff_landing_page')) {
            $wp_query->is_404 = false;
            status_header(200);
            include get_template_directory() . '/page.php';
            exit();
        }
    }
}
