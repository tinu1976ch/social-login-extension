<?php

/**
 * Copyright (C)  Vasyl Martyniuk <vasyl@vasyltech.com>
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * AAM Social Login extension
 *
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_SocialLogin extends AAM_Backend_Feature_Abstract {

    /**
     * Instance of itself
     * 
     * @var AAM_ECommerce 
     * 
     * @access private
     */
    protected static $instance = null;
    
    /**
     * Initialize the extension
     * 
     * @return void
     * 
     * @access protected
     */
    public function __construct() {
        //frontend render
        add_action('login_form', array($this, 'renderUI'));
        
        //register API endpoint
        add_action('wp', array($this, 'trigger'), 2);
    }
    
    /**
     * 
     * @param type $adapter
     * @return type
     */
    protected function getLink($adapter) {
        $link  = '/?action=aam-social-login&adapter=' . $adapter . '&redirect_to=';
        $link .= urlencode($_SERVER['REQUEST_URI']);
        
        return $link;
    }
    
    /**
     * 
     */
    public function renderUI() {
        require __DIR__ . '/phtml/social.phtml';
    }
    
    /**
     * 
     * @return type
     */
    public function trigger() {
        if (filter_input(INPUT_GET, 'action') == 'aam-social-login') {
            $adapter  = filter_input(INPUT_GET, 'adapter');
            $redirect = filter_input(INPUT_GET, 'redirect_to');
            
            $config = array(
                'callback'  => add_query_arg(
                    array(
                        'action'      => 'aam-social-login', 
                        'adapter'     => $adapter, 
                        'redirect_to' => urlencode($redirect)
                    ), 
                    home_url()
                ),
                'providers' => AAM_Core_Config::get('login.social.providers', array())
            );
            
            try {
                //Feed configuration array to Hybridauth
                $hybridauth = new Hybridauth\Hybridauth($config);

                //Attempt to authenticate users with a provider by name
                $provider = $hybridauth->authenticate(ucfirst($adapter)); 
                
                if ($provider->isConnected() && get_option('users_can_register')) {
                    $this->prepareProfile($provider->getUserProfile(), $adapter);
                    
                    // safely redirect to orriginal URL
                    wp_safe_redirect($redirect);
                    exit;
                }

                //Disconnect the adapter 
                $provider->disconnect();
            } catch(Exception $e){
                wp_die('[Error]: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * 
     * @param Hybridauth\User\Profile $profile
     * @param type $adapter
     */
    protected function prepareProfile(Hybridauth\User\Profile $profile, $adapter) {
        //prepare email
        $email = trim($profile->emailVerified ?: $profile->email);
        
        if (empty($email)) {
            $email = sanitize_user($profile->identifier) . '@' . $adapter . '.com';
        }
        
        if (!email_exists($email)) {
            $result = register_new_user($email, $email);
            if (!is_wp_error($result)) {
                $this->runSocialLogin($email);
            } else {
                wp_die($result->get_error_message());
            }
        } else {
            $this->runSocialLogin($email);
        }
    }
    
    /**
     * 
     * @param type $email
     */
    protected function runSocialLogin($email) {
        add_filter('authenticate', array($this, 'authenticationHook'), 10, 2);
        
        $result = wp_signon(array(
            'remember'      => true,
            'user_login'    => $email,
            'user_password' => time()
        ));
        
        remove_filter('authenticate', array($this, 'authenticationHook'));
        
        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }
        
        return true;
    }
    
    /**
     * 
     * @param type $username
     * @return type
     */
    public function authenticationHook($result, $username) {
        return get_user_by('email', $username);
    }
    
    /**
     * Bootstrap the extension
     * 
     * @return AAM_ECommerce
     * 
     * @access public
     */
    public static function bootstrap() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

}