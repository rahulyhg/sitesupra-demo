<?php

namespace Supra\NestedSet\SelectOrder;

use Supra\NestedSet\Node\NodeInterface,
		Supra\NestedSet\Exception,
		Doctrine\ORM\QueryBuilder;

/**
 * 
 */
class DoctrineSelectOrder extends SelectOrderAbstraction
{
	public function getOrderDQL(QueryBuilder $qb)
	{
		$orderRules = $this->orderRules;
		foreach ($orderRules as $orderRule) {
			$field = $orderRule[ArraySelectOrder::FIELD_POS];
			switch ($field) {
				case SelectOrderAbstraction::LEFT_FIELD:
				case SelectOrderAbstraction::RIGHT_FIELD:
				case SelectOrderAbstraction::LEVEL_FIELD:
					break;
				default:
					throw new Exception\InvalidOperation("Field $field is not recognized");
			}

			$field = "e.$field";

			$direction = $orderRule[ArraySelectOrder::DIRECTION_POS];
			switch ($direction) {
				case SelectOrderAbstraction::DIRECTION_ASCENDING:
					$directionSQL = 'ASC';
					break;
				case SelectOrderAbstraction::DIRECTION_DESCENDING:
					$directionSQL = 'DESC';
					break;
				default:
					throw new Exception\InvalidOperation("Direction $direction is not recognized");
			}
			$qb->addOrderBy($field, $directionSQL);
		}
		
		return $qb;
	}
}