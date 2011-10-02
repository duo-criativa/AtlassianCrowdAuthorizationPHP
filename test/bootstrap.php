<?php

/*
 * This file is part of AtlassianCrowdAuthorizationPHP.
 *
 * (c) 2011 Paulo Ribeiro
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/../lib/Duo/AtlassianCrowdAuthorization/ClassLoader.php';
require_once __DIR__.'/../../Buzz/lib/Buzz/ClassLoader.php';

Duo\AtlassianCrowdAuthorization\ClassLoader::register();
Buzz\ClassLoader::register();
