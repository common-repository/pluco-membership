<?php
/**
 * Plugin Name: Membership by PluginsCorner
 * Plugin URI: http://pluginscorner.com
 * Description: Memberships made easy
 * Author URI: http://pluginscorner.com
 * Version: 0.9.1
 * Author: <a href="http://pluginscorner.com">Plugins Corner</a>
 * Text Domain: pc-membership
 * Domain Path: /languages/
 * License:     GPL3 / Apache License, Version 2.0
 *
 * Copyright (C) 2018 Plugins Corner
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
 * The premium version of this product is licensed under the Apache License Version 2.0.
 * A premium version of the plugin is considered once a license has been bought and activated.
 * (and non free features have been unlocked).
 *
 * Copyright (C) 2018 Plugins Corner - Premium Version
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Also Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this software except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use PLCOMembership\classes\PLCOM_Initialize;
require('vendor/autoload.php');

spl_autoload_register(function ($class_name) {

	$namespaces = array(
		'PLCOMembership',
		'Stripe'
	);

	$namespace_exploded = explode('\\', $class_name);

	if(count($namespace_exploded) > 0) {
		$namespace = $namespace_exploded[0];
	} else {
		return;
	}

	if (!in_array($namespace, $namespaces)) {
		return;
	}

	$exploded = explode('\\', str_replace($namespace .'\\', '', str_replace('_', '-', $class_name)));
	$exploded[array_key_last($exploded)] = 'class-' . $exploded[array_key_last($exploded)] . '.php';
	$path = strtolower(implode('/', $exploded));

	include $path;
});

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if(!defined('PLCOM_PLUGIN')) {
	define('PLCOM_PLUGIN', 1);
}
//define('PLCO_DEBUG', true);
new PLCOM_Initialize();
