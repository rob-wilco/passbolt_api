<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SARL (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SARL (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.0.0
 */
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::plugin('Passbolt/Tags', ['path' => '/tags'], function (RouteBuilder $routes) {
    $routes->setExtensions(['json']);

    $routes->connect('/', ['prefix' => 'tags', 'controller' => 'TagsIndex', 'action' => 'index'])
        ->setMethods(['GET']);

    $routes->connect('/resource/:id', ['prefix' => 'tags', 'controller' => 'ResourcesTagsAdd', 'action' => 'addPost'])
        ->setPass(['id'])
        ->setMethods(['POST']);

    /**
     * @since 2.11.0
     * @deprecated POST /tags/<resourceId>.json is deprecated in favor of POST /tags/resource/<resourceId>.json
     */
    $routes->connect('/:id', ['prefix' => 'tags', 'controller' => 'ResourcesTagsAdd', 'action' => 'addPost'])
        ->setPass(['id'])
        ->setMethods(['POST']);

    $routes->connect('/:id', ['prefix' => 'tags', 'controller' => 'TagsUpdate', 'action' => 'update'])
        ->setPass(['id'])
        ->setMethods(['PUT']);

    $routes->connect('/:id', ['prefix' => 'tags', 'controller' => 'TagsDelete', 'action' => 'delete'])
        ->setPass(['id'])
        ->setMethods(['DELETE']);
});