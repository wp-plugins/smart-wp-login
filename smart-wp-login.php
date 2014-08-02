<?php
/**
 * @package SWPL
 */
/**
 * Plugin Name: Smart WP Login
 * Version: 0.9
 * Author: Nishant Kumar
 * Description: Now with Smart WP Login, you can configure WordPress to login, register and reset password using e-mail only.
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

//No direct access allowed.
if(!function_exists('add_action')){
    echo 'Get Well Soon :)';
    exit;
}

register_activation_hook(__FILE__, 'swpl_activation');
function swpl_activation(){
    if(false === get_option('swpl_l')){
        add_option('swpl_l', '1');
    }
        
    if(false === get_option('swpl_r')){
        add_option('swpl_r', '1');
    }
    
    if(false === get_option('swpl_rp')){
        add_option('swpl_rp', '1');
    }
}

require 'swpl_engine.php';

new SWPL_Engine();