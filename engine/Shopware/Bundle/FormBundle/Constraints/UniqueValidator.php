<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Bundle\FormBundle\Constraints;

use Doctrine\DBAL\Connection;
use RuntimeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueValidator extends ConstraintValidator
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed      $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Unique) {
            throw new RuntimeException('Invalid constraint for validator given.');
        }

        if (empty($value)) {
            return;
        }

        $builder = $this->connection->createQueryBuilder()->select(['1'])
            ->from($constraint->table)
            ->where($constraint->column . ' = :value')
            ->setParameter('value', $value);

        if (!empty($constraint->ignoreField)) {
            $ignoreIdValue = $this->context->getRoot()->getData()[$constraint->ignoreField];

            $builder->andWhere($constraint->ignoreColumn . ' <> :ignoreId')
                ->setParameter('ignoreId', $ignoreIdValue);
        }

        if ($builder->execute()->rowCount() === 0) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->atPath($this->context->getPropertyPath())
            ->setParameter(':value', $value)
            ->setParameter(':table', $constraint->table)
            ->setParameter(':column', $constraint->column)
            ->addViolation();
    }
}
