<?php

/**
 * Plugin Name: Custom Landing Page URL for Fluentform
 * Plugin URI:  https://github.com/fluentform/fluentform/issues/372
 * Description: Allow customizing the landing page link.
 * Author: Rahat Baksh
 * Author URI:  #
 * Version: 1.0
 * Text Domain: ff-landing-custom-url
 */

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright 2022. All rights reserved.
 */


defined('ABSPATH') or die;
define('FF_LANDING_CUSTOM_URL_DIR', plugin_dir_path(__FILE__));
define('FF_LANDING_CUSTOM_URL_URL', plugin_dir_url(__FILE__));


class FluentFormLandingPageCustomURL
{

    public function boot()
    {
        // check if fluentform is istalled.
        if (!defined('FLUENTFORM')) {
            return $this->showFulentFormIntallationNotice();
        }

        $this->includeFiles();

        if (function_exists('wpFluentForm')) {
            return $this->registerHooks(wpFluentForm());
        }
    }

    protected function includeFiles()
    {
        include_once FF_LANDING_CUSTOM_URL_DIR . 'Integration/FluentFormCustomURL.php';
    }

    protected function registerHooks($fluentForm)
    {
        new \FluentFormLandingCustomURL\Integration\FluentFormCustomURL($fluentForm);
    }


    /**
     * Inject dependency if FluentForm is not loaded
     */
    protected function showFulentFormIntallationNotice()
    {
        add_action('admin_notices', function () {
            $pluginInfo = $this->getFluentFormInstallationDetails();

            $class = 'notice notice-error';

            $install_url_text = 'Click Here to Install the Plugin';

            if ($pluginInfo->action == 'activate') {
                $install_url_text = 'Click Here to Activate the Plugin';
            }

            $message = sprintf(
                "FluentForm Dropbox Add-On Requires Fluent Forms Add On Plugin, <b><a href=\"%s\">%s</a></b>",
                $pluginInfo->url,
                $install_url_text
            );

            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
        });
    }

    /**
     * Retrieves the installation details for the FluentForm plugin.
     *
     * This method checks if the FluentForm plugin is installed and activated.
     * If the plugin is installed, it generates the activation URL.
     * If the plugin is not installed, it generates the installation URL.
     *
     * @return object The installation details, including the action (install or activate) and the URL.
     */
    protected function getFluentFormInstallationDetails()
    {
        $activation = (object)[
            'action' => 'install',
            'url'    => ''
        ];
        $allPlugins = get_plugins();

        if (isset($allPlugins['fluentform/fluentform.php'])) {
            $url = wp_nonce_url(
                self_admin_url('plugins.php?action=activate&plugin=fluentform/fluentform.php'),
                'activate-plugin_fluentform/fluentform.php'
            );

            $activation->action = 'activate';
        } else {
            $api = (object)[
                'slug' => 'fluentform'
            ];

            $url = wp_nonce_url(
                self_admin_url('update.php?action=install-plugin&plugin=' . $api->slug),
                'install-plugin_' . $api->slug
            );
        }
        $activation->url = $url;
        return $activation;
    }
}


(new FluentFormLandingPageCustomURL())->boot();
