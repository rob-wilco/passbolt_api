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

namespace Passbolt\AccountRecovery\Service\AccountRecoveryOrganizationPolicies;

use App\Utility\UserAccessControl;
use Passbolt\AccountRecovery\Model\Entity\AccountRecoveryOrganizationPolicy;

interface AccountRecoveryOrganizationPolicySetServiceInterface
{
    /**
     * AccountRecoveryOrganizationPolicySetServiceInterface constructor.
     *
     * @param \Passbolt\AccountRecovery\Service\AccountRecoveryOrganizationPolicies\AccountRecoveryOrganizationPolicyGetServiceInterface|null $getService bring your own getter // phpcs:ignore
     */
    public function __construct(?AccountRecoveryOrganizationPolicyGetServiceInterface $getService = null);

    /**
     * @param \App\Utility\UserAccessControl $uac user access control
     * @param array $data user provided data
     * @return \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryOrganizationPolicy
     */
    public function set(UserAccessControl $uac, array $data): AccountRecoveryOrganizationPolicy;
}