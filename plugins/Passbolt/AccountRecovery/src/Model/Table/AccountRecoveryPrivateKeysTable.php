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

namespace Passbolt\AccountRecovery\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * AccountRecoveryPrivateKeys Model
 *
 * @property \Passbolt\AccountRecovery\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \Passbolt\AccountRecovery\Model\Table\AccountRecoveryPrivateKeyPasswordsTable&\Cake\ORM\Association\HasMany $AccountRecoveryPrivateKeyPasswords
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKey newEmptyEntity()
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKey newEntity(array $data, array $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKey[] newEntities(array $data, array $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKey get($primaryKey, $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKey findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKey patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKey[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKey|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKey saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKey[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKey[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKey[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKey[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AccountRecoveryPrivateKeysTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('account_recovery_private_keys');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users');
        $this->hasMany('AccountRecoveryPrivateKeyPasswords', [
            'className' => AccountRecoveryPrivateKeyPasswordsTable::class,
            'foreignKey' => 'private_key_id',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create')
            ->add('id', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('data')
            ->requirePresence('data', 'create')
            ->notEmptyString('data');

        $validator
            ->uuid('created_by')
            ->requirePresence('created_by', 'create')
            ->notEmptyString('created_by');

        $validator
            ->uuid('modified_by')
            ->requirePresence('modified_by', 'create')
            ->notEmptyString('modified_by');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['id']), ['errorField' => 'id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }
}