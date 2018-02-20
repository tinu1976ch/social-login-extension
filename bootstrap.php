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

if (defined('AAM_KEY') && !defined('AAM_SOCIAL_LOGIN')) {
    $config = require(dirname(__FILE__) . '/config.php');
    
    if (version_compare(PHP_VERSION, $config['require']['php']) >= 0) {
        //define extension constant as it's version #
        define('AAM_SOCIAL_LOGIN', $config['version']);

        //register activate and extension classes
        AAM_Autoloader::add('AAM_SocialLogin', __DIR__ . '/SocialLogin.php');
        
        //register vendor autoloader
        require __DIR__ . '/vendor/autoload.php';

        AAM_SocialLogin::bootstrap();
    } else {
        AAM_Core_Console::add(
            "[Social Login] extension requires PHP {$config['require']['php']} or higher.", 'b'
        );
    }
}