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
namespace Passbolt\AccountRecovery\Test\Factory;

use App\Test\Factory\UserFactory;
use Cake\Chronos\Chronos;
use CakephpFixtureFactories\Factory\BaseFactory as CakephpBaseFactory;
use Faker\Generator;
use Passbolt\AccountRecovery\Model\Entity\AccountRecoveryRequest;
use Passbolt\AccountRecovery\Model\Table\AccountRecoveryRequestsTable;

/**
 * AccountRecoveryRequestFactory
 *
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryRequest|\Passbolt\AccountRecovery\Model\Entity\AccountRecoveryRequest[] persist()
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryRequest getEntity()
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryRequest[] getEntities()
 */
class AccountRecoveryRequestFactory extends CakephpBaseFactory
{
    /**
     * Defines the Table Registry used to generate entities with
     *
     * @return string
     */
    protected function getRootTableRegistryName(): string
    {
        return AccountRecoveryRequestsTable::class;
    }

    /**
     * Defines the factory's default values. This is useful for
     * not nullable fields. You may use methods of the present factory here too.
     *
     * @return void
     */
    protected function setDefaultTemplate(): void
    {
        $this->setDefaultData(function (Generator $faker) {
            return [
                'status' => AccountRecoveryRequest::ACCOUNT_RECOVERY_REQUEST_PENDING,
                'user_id' => $faker->uuid(),
                'authentication_token_id' => $faker->uuid(),
                'armored_key' => $faker->text(),
                'fingerprint' => $faker->uuid(),
                'created_by' => $faker->uuid(),
                'modified_by' => $faker->uuid(),
                'created' => Chronos::now()->subDay($faker->randomNumber(4)),
                'modified' => Chronos::now()->subDay($faker->randomNumber(4)),
            ];
        });
    }

    /**
     * @param ?string $userId User ID
     * @return AccountRecoveryRequestFactory
     */
    public function withUser(?string $userId)
    {
        if (!isset($userId)) {
            $userId = UserFactory::make()->persist();
        }

        return $this->setField('user_id', $userId);
    }

    /**
     * @param UserFactory|null $factory User Factory
     * @return AccountRecoveryRequestFactory
     */
    public function createdBy(?UserFactory $factory = null)
    {
        return $this->with('Creator', $factory);
    }

    /**
     * @param UserFactory|null $factory User Factory
     * @return AccountRecoveryRequestFactory
     */
    public function modifiedBy(?UserFactory $factory = null)
    {
        return $this->with('Modifier', $factory);
    }

    /**
     * @return $this
     */
    public function pending()
    {
        return $this->setField('status', AccountRecoveryRequest::ACCOUNT_RECOVERY_REQUEST_PENDING);
    }

    /**
     * @return $this
     */
    public function approved()
    {
        return $this->setField('status', AccountRecoveryRequest::ACCOUNT_RECOVERY_REQUEST_APPROVED);
    }
}