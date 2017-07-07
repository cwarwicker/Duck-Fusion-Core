<?php
/*

    This file is part of the DuckFusion Framework.

    This is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    DuckFusion Framework is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with DuckFusion Framework.  If not, see <http://www.gnu.org/licenses/>.

*/

/**
 *
 * Autoload
 * 
 * This file loads all the required Core files
 *
 * @copyright    Copyright (c) 2017 Conn Warwicker
 * @package      DuckFusion
 * @version      0.1
 * @author       Conn Warwicker <conn@cmrwarwicker.com>
 * @link         https://github.com/cwarwicker/Duck-Fusion-Core
 *
 **/

require_once df_SYS_CORE . 'System.php';

require_once df_SYS_CORE . 'App.php';
\DF\App::register();

require_once df_SYS_CORE . 'Controller.php';
require_once df_SYS_CORE . 'Renderer.php';
require_once df_SYS_CORE . 'Quack.php';
require_once df_SYS_CORE . 'Template.php';
require_once df_SYS_CORE . 'Router.php';
require_once df_SYS_CORE . 'Database.php';