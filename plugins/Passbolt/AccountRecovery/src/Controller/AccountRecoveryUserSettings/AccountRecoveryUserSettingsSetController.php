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
 * @since         3.6.0
 */

namespace Passbolt\AccountRecovery\Controller\AccountRecoveryUserSettings;

use App\Controller\AppController;
use Passbolt\AccountRecovery\Service\AccountRecoveryUserSettings\AccountRecoveryUserSettingsSetService;

// phpcs:ignore

class AccountRecoveryUserSettingsSetController extends AppController
{
    /**
     * @return void
     */
    public function set(): void
    {
        $service = new AccountRecoveryUserSettingsSetService($this->User->getAccessControl());
        $service->set($this->request->getData());
        $this->success(__('The operation was successful.'));
    }
}