<?php
declare(strict_types=1);

/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         3.5.0
 */
namespace Passbolt\AccountRecovery;

use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\ORM\TableRegistry;
use Passbolt\AccountRecovery\Event\ContainAccountRecoveryUserSettings;
use Passbolt\AccountRecovery\Notification\AccountRecoveryEmailRedactorPool;
use Passbolt\AccountRecovery\ServiceProvider\AccountRecoveryOrganizationPolicyServiceProvider;
use Passbolt\AccountRecovery\ServiceProvider\AccountRecoverySetupServiceProvider;

class Plugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);
        $this->registerListeners($app);
        $this->addAssociations();
    }

    /**
     * @inheritDoc
     */
    public function services(ContainerInterface $container): void
    {
        $container->addServiceProvider(new AccountRecoverySetupServiceProvider());
        $container->addServiceProvider(new AccountRecoveryOrganizationPolicyServiceProvider());
    }

    /**
     * Register Account Recovery related listeners.
     *
     * @param \Cake\Core\PluginApplicationInterface $app App
     * @return void
     */
    public function registerListeners(PluginApplicationInterface $app): void
    {
        $app->getEventManager()
            ->on(new AccountRecoveryEmailRedactorPool())
            ->on(new ContainAccountRecoveryUserSettings());
    }

    /**
     * Defines additional associations related to the plugin
     *
     * @return void
     */
    public function addAssociations(): void
    {
        TableRegistry::getTableLocator()->get('Users')->hasOne('AccountRecoveryUserSettings');
    }
}