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

namespace Passbolt\AccountRecovery\Model\Entity;

use Cake\ORM\Entity;

/**
 * AccountRecoveryOrganizationPolicy Entity
 *
 * @property string $id
 * @property string $policy
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property string $created_by
 * @property string $modified_by
 *
 * @property \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryOrganizationPublicKey $account_recovery_organization_public_key
 */
class AccountRecoveryOrganizationPolicy extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * For security purposes, it is advised to set '*' to false
     * and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'policy' => false,
        'created' => false,
        'modified' => false,
        'created_by' => false,
        'modified_by' => false,

        // associated data
        'account_recovery_organization_public_key_id' => false,
        'account_recovery_organization_public_key' => false,
    ];

    public const ACCOUNT_RECOVERY_ORGANIZATION_POLICY_OPT_IN = 'opt-in';
    public const ACCOUNT_RECOVERY_ORGANIZATION_POLICY_OPT_OUT = 'opt-out';
    public const ACCOUNT_RECOVERY_ORGANIZATION_POLICY_MANDATORY = 'mandatory';
    public const ACCOUNT_RECOVERY_ORGANIZATION_POLICY_DISABLED = 'disabled';

    public const SUPPORTED_POLICIES = [
        self::ACCOUNT_RECOVERY_ORGANIZATION_POLICY_OPT_IN,
        self::ACCOUNT_RECOVERY_ORGANIZATION_POLICY_OPT_OUT,
        self::ACCOUNT_RECOVERY_ORGANIZATION_POLICY_MANDATORY,
        self::ACCOUNT_RECOVERY_ORGANIZATION_POLICY_DISABLED,
    ];
}