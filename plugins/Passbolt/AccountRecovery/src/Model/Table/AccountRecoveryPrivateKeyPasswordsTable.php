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
namespace Passbolt\AccountRecovery\Model\Table;

use App\Error\Exception\CustomValidationException;
use App\Model\Validation\ArmoredMessage\IsParsableMessageValidationRule;
use App\Model\Validation\Fingerprint\IsValidFingerprintValidationRule;
use App\Utility\UserAccessControl;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKeyPassword;
use Passbolt\AccountRecovery\Model\Table\Traits\TableTruncateTrait;

/**
 * AccountRecoveryPrivateKeyPasswords Model
 *
 * @property \Passbolt\AccountRecovery\Model\Table\AccountRecoveryPrivateKeysTable&\Cake\ORM\Association\BelongsTo $AccountRecoveryPrivateKeys
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKeyPassword newEmptyEntity()
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKeyPassword newEntity(array $data, array $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKeyPassword[] newEntities(array $data, array $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKeyPassword get($primaryKey, $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKeyPassword findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKeyPassword patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKeyPassword[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKeyPassword|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKeyPassword saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKeyPassword[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKeyPassword[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKeyPassword[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKeyPassword[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AccountRecoveryPrivateKeyPasswordsTable extends Table
{
    use TableTruncateTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('account_recovery_private_key_passwords');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('AccountRecoveryPrivateKeys', [
            'foreignKey' => 'private_key_id',
            'joinType' => 'INNER',
            'className' => 'Passbolt/AccountRecovery.AccountRecoveryPrivateKeys',
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
            ->allowEmptyString('id', null, 'create');

        $validator
            ->ascii('recipient_fingerprint', __('The fingerprint should be a valid ASCII string.'))
            ->requirePresence('recipient_fingerprint', 'create', __('A fingerprint is required'))
            ->notEmptyString('recipient_fingerprint', __('The fingerprint should not be empty'))
            ->add('recipient_fingerprint', 'invalidFingerprint', new IsValidFingerprintValidationRule());

        $validator
            ->scalar('recipient_foreign_model')
            ->requirePresence('recipient_foreign_model', 'create')
            ->notEmptyString('recipient_foreign_model')
            ->inList('recipient_foreign_model', AccountRecoveryPrivateKeyPassword::ALLOWED_RECIPIENT_FOREIGN_MODELS, __(
                'The recipient_foreign_model must be one of the following: {0}.',
                implode(', ', AccountRecoveryPrivateKeyPassword::ALLOWED_RECIPIENT_FOREIGN_MODELS)
            ));

        $validator
            ->scalar('data')
            ->requirePresence('data', 'create')
            ->notEmptyString('data')
            ->add('data', 'isValidOpenPGPMessage', new IsParsableMessageValidationRule());

        $validator
            ->uuid('created_by')
            ->requirePresence('created_by', 'create')
            ->notEmptyString('created_by');

        $validator
            ->uuid('modified_by')
            ->requirePresence('modified_by')
            ->notEmptyString('modified_by');

        return $validator;
    }

    /**
     * Validation to use when rotating keys
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationRotateKeys(Validator $validator): Validator
    {
        $validator = $this->validationDefault($validator);

        $validator
            ->uuid('private_key_id', __('The private key identifier should be a valid UUID.'))
            ->requirePresence('private_key_id', 'create', __('A private key identifier is required.'))
            ->notEmptyString('private_key_id', __('The private key identifier should not be empty.'));

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

        $rules->add(
            $rules->existsIn(['private_key_id'], 'AccountRecoveryPrivateKeys'),
            ['errorField' => 'private_key_id']
        );

        return $rules;
    }

    /**
     * Format recipient fingerprint data to remove spaces and set it to uppercase
     *
     * @param \Cake\Event\EventInterface $event event
     * @param \ArrayObject $data user provided data
     * @param \ArrayObject $options options
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['recipient_fingerprint']) && is_string($data['recipient_fingerprint'])) {
            $f = strtoupper(str_replace(' ', '', $data['recipient_fingerprint']));
            $data['recipient_fingerprint'] = $f;
        }
    }

    /**
     * @param \App\Utility\UserAccessControl $uac user access control
     * @param array $passwords user provided data
     * @param string $validationRules ruleset
     * @throws \App\Error\Exception\CustomValidationException if data doesn't validate
     * @return \Passbolt\AccountRecovery\Model\Entity\AccountRecoveryPrivateKeyPassword[] array of entities
     */
    public function buildAndValidateEntities(
        UserAccessControl $uac,
        array $passwords,
        string $validationRules = 'default'
    ): array {
        if (!in_array($validationRules, ['default', 'rotateKeys'])) {
            throw new InternalErrorException('Invalid validation ruleset.');
        }

        foreach ($passwords as $i => $entity) {
            $passwords[$i]['created_by'] = $uac->getId();
            $passwords[$i]['modified_by'] = $uac->getId();
        }

        $accessibleFields = [
            'recipient_fingerprint' => true,
            'recipient_foreign_model' => true,
            'data' => true,
            'created_by' => true,
            'modified_by' => true,
        ];

        // Private key id should only be set when rotating keys
        // Otherwise passwords are created with the keys during setup or user settings change
        if ($validationRules === 'rotateKeys') {
            $accessibleFields['private_key_id'] = true;
        }

        $passwordEntities = $this->newEntities($passwords, [
            'accessibleFields' => $accessibleFields,
            'validate' => $validationRules,
        ]);

        $errors = [];
        foreach ($passwordEntities as $i => $entity) {
            if ($entity->getErrors()) {
                $errors[$i] = $entity->getErrors();
            }
        }

        if (count($errors)) {
            throw new CustomValidationException(__('Could not validate password data.'), [
                'account_recovery_private_key_passwords' => $errors,
            ]);
        }

        return $passwordEntities;
    }
}