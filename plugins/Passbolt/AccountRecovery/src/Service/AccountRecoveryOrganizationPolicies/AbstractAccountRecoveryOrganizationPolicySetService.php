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

use App\Error\Exception\CustomValidationException;
use App\Error\Exception\ValidationException;
use App\Service\OpenPGP\PublicKeyValidationService;
use App\Utility\UserAccessControl;
use Cake\Chronos\Chronos;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\ModelAwareTrait;
use Cake\Utility\Hash;
use Passbolt\AccountRecovery\Model\Entity\AccountRecoveryOrganizationPolicy;
use Passbolt\AccountRecovery\Model\Entity\AccountRecoveryOrganizationPublicKey;

/**
 * @property \Passbolt\AccountRecovery\Model\Table\AccountRecoveryOrganizationPoliciesTable $AccountRecoveryOrganizationPolicies
 * @property \Passbolt\AccountRecovery\Model\Table\AccountRecoveryOrganizationPublicKeysTable $AccountRecoveryOrganizationPublicKeys
 */
class AbstractAccountRecoveryOrganizationPolicySetService
{
    use ModelAwareTrait;

    public const AFTER_ENABLE_POLICY_EVENT = 'accountRecovery.policy.enable';
    public const AFTER_DISABLE_POLICY_EVENT = 'accountRecovery.policy.disable';
    public const AFTER_UPDATE_POLICY_EVENT = 'accountRecovery.policy.update';

    /**
     * @var array $data user provider data
     */
    protected $data;

    /**
     * @var \Passbolt\AccountRecovery\Service\AccountRecoveryOrganizationPolicies\AccountRecoveryOrganizationPolicyGetServiceInterface $getService
     */
    protected $getService;

    /**
     * @var \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryOrganizationPolicy|null $currentPolicy
     */
    protected $currentPolicy = null;

    /**
     * AbstractCompleteService constructor
     *
     * @param \Passbolt\AccountRecovery\Service\AccountRecoveryOrganizationPolicies\AccountRecoveryOrganizationPolicyGetServiceInterface|null $getService bring your own
     */
    public function __construct(?AccountRecoveryOrganizationPolicyGetServiceInterface $getService = null)
    {
        $this->loadModel('Passbolt/AccountRecovery.AccountRecoveryOrganizationPolicies');
        $this->loadModel('Passbolt/AccountRecovery.AccountRecoveryOrganizationPublicKeys');
        $this->getService = $getService ?? new AccountRecoveryOrganizationPolicyGetService();
    }

    // METHODS USED TO GET/SET USER REQUEST DATA

    /**
     * @param bool $useCache default true, will not re-fetch policy from DB
     * @return \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryOrganizationPolicy
     */
    protected function getCurrentPolicyEntity(bool $useCache = true): AccountRecoveryOrganizationPolicy
    {
        if (!isset($this->currentPolicy) || !$useCache) {
            return $this->getService->get();
        }

        return $this->currentPolicy;
    }

    /**
     * @return string current policy name
     */
    protected function getCurrentPolicy(): string
    {
        return $this->getCurrentPolicyEntity()->policy;
    }

    /**
     * @param array $data user provided data
     * @return void
     */
    protected function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @param string|null $name Dot separated name of the value to read. Or null to read all data.
     * @param mixed $default The default data.
     * @return mixed The value being read.
     */
    protected function getData(?string $name = null, $default = null)
    {
        if ($name === null) {
            return $this->data;
        }
        if (!is_array($this->data) && $name) {
            return $default;
        }

        /** @psalm-suppress PossiblyNullArgument */
        return Hash::get($this->data, $name, $default);
    }

    // METHODS USED TO UNDERSTAND USER REQUEST

    /**
     * @return bool true if policy from request is different than current one
     */
    public function isPolicyChange(): bool
    {
        return $this->getCurrentPolicy() != $this->getData('policy');
    }

    /**
     * @return bool true if currently disabled and going towards enabled state
     */
    public function isEnabling(): bool
    {
        $currentlyDisabled = $this->getCurrentPolicy() === AccountRecoveryOrganizationPolicy::ACCOUNT_RECOVERY_ORGANIZATION_POLICY_DISABLED; // phpcs:ignore
        $goingToEnable = $this->getData('policy') !== AccountRecoveryOrganizationPolicy::ACCOUNT_RECOVERY_ORGANIZATION_POLICY_DISABLED; // phpcs:ignore

        return $currentlyDisabled && $goingToEnable;
    }

    /**
     * @return bool true if currently enabled and going towards disabled state
     */
    public function isDisabling(): bool
    {
        $currentlyEnabled = $this->getCurrentPolicy() !== AccountRecoveryOrganizationPolicy::ACCOUNT_RECOVERY_ORGANIZATION_POLICY_DISABLED; // phpcs:ignore
        $goingToDisabled = $this->getData('policy') === AccountRecoveryOrganizationPolicy::ACCOUNT_RECOVERY_ORGANIZATION_POLICY_DISABLED; // phpcs:ignore

        return $currentlyEnabled && $goingToDisabled;
    }

    /**
     * @return bool true if the account_recovery_organization_public_key data is set
     */
    public function isPublicKeyProvided(): bool
    {
        $publicKey = $this->getData('account_recovery_organization_public_key');

        return isset($publicKey) && is_array($publicKey);
    }

    /**
     * @return bool true if the account_recovery_organization_revoked_key data is set
     */
    public function isRevokedKeyProvided(): bool
    {
        $revokedKey = $this->getData('account_recovery_organization_revoked_key');

        return isset($revokedKey) && is_array($revokedKey);
    }

    /**
     * @return bool true if the account_recovery_private_key_passwords data is set
     */
    public function isPrivateKeyPasswordsProvided(): bool
    {
        $passwords = $this->getData('account_recovery_private_key_passwords');

        return isset($passwords) && is_array($passwords);
    }

    // METHODS USED TO ASSERT REQUEST DATA AND BUILD ENTITIES

    /**
     * Assert if the policy in the provided data is correct
     * e.g. it is not empty, it is part of the supported policy, etc.
     * Return a valid AccountRecoveryOrganizationPolicy if correct
     *
     * @throw ValidationException if policy field does not validate
     * @param \App\Utility\UserAccessControl $uac The user at the origin of the operation
     * @return \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryOrganizationPolicy
     */
    protected function assertOrganizationPolicy(UserAccessControl $uac): AccountRecoveryOrganizationPolicy
    {
        $policyData = $this->getData('policy');
        $newPolicy = $this->AccountRecoveryOrganizationPolicies
            ->newEntity([
                'policy' => $policyData,
                'created_by' => $uac->getId(),
                'modified_by' => $uac->getId(),
            ], ['accessibleFields' => [
                'policy' => true,
                'created' => true,
                'modified' => true,
                'created_by' => true,
                'modified_by' => true,
            ]]);

        if ($newPolicy->getErrors()) {
            $em = __('Could not validate policy data.');
            throw new ValidationException($em, $newPolicy, $this->AccountRecoveryOrganizationPolicies);
        }

        return $newPolicy;
    }

    /**
     * Assert public key data and build entity
     *
     * @param \App\Utility\UserAccessControl $uac user access control
     * @return \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryOrganizationPublicKey
     */
    public function assertNewPublicKey(UserAccessControl $uac): AccountRecoveryOrganizationPublicKey
    {
        try {
            $data = $this->getData('account_recovery_organization_public_key');
            $entity = $this->newPublicKeyEntityOrFail($uac, $data);
            $rules = PublicKeyValidationService::getStrictRules();
            $keyInfo = PublicKeyValidationService::parseAndValidatePublicKey($entity->armored_key, $rules);
            $this->assertSameFingerprint($keyInfo['fingerprint'], $entity->fingerprint);
            $this->assertPublicKeyModelRules($entity);
        } catch (ValidationException | CustomValidationException $exception) {
            throw new CustomValidationException(__('Could not validate policy data.'), [
                'account_recovery_organization_public_key' => $exception->getErrors(),
            ]);
        } catch (\Exception $exception) {
            throw new CustomValidationException(__('Could not validate policy data.'), [
                'account_recovery_organization_public_key' => [
                    'armored_key' => [
                        'invalidArmoredKey' => $exception->getMessage(),
                    ],
                ],
            ]);
        }

        return $entity;
    }

    /**
     * Assert public key revocation
     * Check user provided valid valid account_recovery_organization_revoked_key
     * Return patched entity corresponding to the key to revoke (e.g. to update in DB)
     *
     * @param \App\Utility\UserAccessControl $uac user access control
     * @throws \App\Error\Exception\CustomValidationException if any of the check on fingerprint or armored key data fails
     * @return \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryOrganizationPublicKey currently in use key patched with new revoked armored_key
     */
    public function assertAndPatchRevokedKeyEntity(UserAccessControl $uac): AccountRecoveryOrganizationPublicKey
    {
        try {
            $data = $this->getData('account_recovery_organization_revoked_key');
            $entity = $this->newPublicKeyEntityOrFail($uac, $data);
            $oldEntity = $this->assertKeyWithFingerprintExists($data['fingerprint']);
            $keyInfo = PublicKeyValidationService::parseAndValidatePublicKey(
                $entity->armored_key,
                PublicKeyValidationService::getRevokedKeyRules()
            );
            $this->assertSameFingerprint($keyInfo['fingerprint'], $data['fingerprint']);
        } catch (ValidationException | CustomValidationException $exception) {
            throw new CustomValidationException(__('Could not validate key revocation.'), [
                'account_recovery_organization_revoked_key' => $exception->getErrors(),
            ]);
        } catch (\Exception $exception) {
            throw new CustomValidationException(__('Could not validate key revocation.'), [
                'account_recovery_organization_revoked_key' => [
                    'armored_key' => [
                        'invalidArmoredKey' => $exception->getMessage(),
                    ],
                ],
            ]);
        }

        // Patch old key with new revoked value
        $patchedEntity = $this->AccountRecoveryOrganizationPublicKeys->patchEntity($oldEntity, [
            'modified_by' => $uac->getId(),
            'armored_key' => $data['armored_key'],
            'deleted' => Chronos::now(),
        ], [
            'fields' => ['deleted', 'modified_by', 'armored_key'],
        ]);
        if ($patchedEntity->getErrors()) {
            throw new CustomValidationException(__('Could not validate key revocation.'), [
                'account_recovery_organization_revoked_key' => $patchedEntity->getErrors(),
            ]);
        }

        return $patchedEntity;
    }

    /**
     * @param \App\Utility\UserAccessControl $uac user access control
     * @param array $data public key data
     * @throws \App\Error\Exception\ValidationException if entity validation fails
     * @return \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryOrganizationPublicKey
     */
    private function newPublicKeyEntityOrFail(UserAccessControl $uac, array $data): AccountRecoveryOrganizationPublicKey
    {
        // Entity validation
        $data['created_by'] = $uac->getId();
        $data['modified_by'] = $uac->getId();
        $table = $this->AccountRecoveryOrganizationPublicKeys;
        $publicKey = $table->newEntity($data, [
            'accessibleFields' => [
                'fingerprint' => true,
                'armored_key' => true,
                'created_by' => true,
                'modified_by' => true,
            ],
        ]);
        if ($publicKey->getErrors()) {
            throw new ValidationException(__('Could not validate public key data.'), $publicKey, $table);
        }

        return $publicKey;
    }

    /**
     * Run public key model rules and throw an exception in case of errors
     *
     * @param \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryOrganizationPublicKey $publicKey entity
     * @throws \App\Error\Exception\ValidationException if model rules fail
     * @return void
     */
    private function assertPublicKeyModelRules(AccountRecoveryOrganizationPublicKey $publicKey): void
    {
        $table = $this->AccountRecoveryOrganizationPublicKeys;
        if (!$table->checkRules($publicKey) && $publicKey->getErrors()) {
            throw new ValidationException(__('Could not validate public key data.'), $publicKey, $table);
        }
    }

    /**
     * Assert that the provided fingerprint in data is matching the one in the key info
     *
     * @param string $f1 fingerprint
     * @param string $f2 fingerprint
     * @throws \App\Error\Exception\CustomValidationException if the fingerprint don't match
     * @return void
     */
    private function assertSameFingerprint(string $f1, string $f2): void
    {
        if ($f1 !== $f2) {
            throw new CustomValidationException(__('Could not validate policy data.'), [
                'fingerprint' => [
                    'isMatchingKeyFingerprintRule' => __('The fingerprint does not match the one of the armored key.'),
                ],
            ]);
        }
    }

    /**
     * Assert an organization recovery public key exists for given fingerprint
     *
     * @param string $fingerprint user provided data
     * @throws \App\Error\Exception\CustomValidationException if the provided fingerprint does not match the one
     * from the currently active key
     * @return \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryOrganizationPublicKey existing key
     */
    private function assertKeyWithFingerprintExists(string $fingerprint): AccountRecoveryOrganizationPublicKey
    {
        try {
            /** @var \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryOrganizationPublicKey $key */
            $key = $this->AccountRecoveryOrganizationPublicKeys->find()->where([
                'fingerprint' => $fingerprint,
                'deleted IS NOT' => null,
            ])->firstOrFail();
        } catch (RecordNotFoundException $exception) {
            throw new CustomValidationException(__('Could not validate policy data.'), [
                'fingerprint' => [
                    '_exists' => __('The fingerprint should match the one from the currently active key.'),
                ],
            ]);
        }

        return $key;
    }

    /**
     * @param \App\Utility\UserAccessControl $uac The user at the origin of the operation
     * @return \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryOrganizationPolicy entity
     */
    public function getNewDisabledEntity(UserAccessControl $uac): AccountRecoveryOrganizationPolicy
    {
        /** @var \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryOrganizationPolicy $policy */
        $policy = $this->AccountRecoveryOrganizationPolicies->newEntity([
            'policy' => AccountRecoveryOrganizationPolicy::ACCOUNT_RECOVERY_ORGANIZATION_POLICY_DISABLED,
            'created_by' => $uac->getId(),
            'modified_by' => $uac->getId(),
        ], [
            'accessibleFields' => [
                'policy' => true,
                'created_by' => true,
                'modified_by' => true,
            ],
        ]);

        return $policy;
    }
}