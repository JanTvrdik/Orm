<?php


class EntityToArray extends Object
{
	const AS_IS = NULL;
	const AS_ID = Entity::ENTITY_TO_ID;
	const AS_ARRAY = Entity::ENTITY_TO_ARRAY;


	const ENTITY_AS_IS = 1;
	const ENTITY_AS_ID = 2;
	const ENTITY_AS_ARRAY = 4;

	const RELATIONSHIP_AS_IS = 8;
	const RELATIONSHIP_AS_ARRAY_OF_ID = 16;
	const RELATIONSHIP_AS_ARRAY_OF_ARRAY = 32;

	/**
	 * @internal
	 * @param IEntity
	 * @param int
	 */
	public static function toArray(IEntity $entity, array $rules, $mode = self::AS_IS)
	{
		if ($mode === self::AS_IS) $mode = self::ENTITY_AS_IS | self::RELATIONSHIP_AS_IS;
		else if ($mode === self::AS_ID) $mode = self::ENTITY_AS_ID | self::RELATIONSHIP_AS_ARRAY_OF_ID;
		else if ($mode === self::AS_ARRAY) $mode = self::ENTITY_AS_ARRAY | self::RELATIONSHIP_AS_ARRAY_OF_ARRAY;
		$result = array(
			'id' => $entity->__isset('id') ? $entity->__get('id') : NULL,
		);

		foreach ($rules as $name => $rule)
		{
			if ($name === 'id') continue;

			if (isset($rule['get']))
			{
				$result[$name] = $entity->__get($name);
				if ($result[$name] instanceof IEntity AND !($mode & self::ENTITY_AS_IS))
				{
					if ($mode & self::ENTITY_AS_ID) $result[$name] = $result[$name]->id;
					else if ($mode & self::ENTITY_AS_ARRAY) $result[$name] = $result[$name]->toArray($mode); // todo co rekurze?
					else throw new InvalidStateException();
				}
				else if ($result[$name] instanceof IRelationship AND !($mode & self::RELATIONSHIP_AS_IS))
				{
					$arr = array();
					foreach ($result[$name] as $e)
					{
						if ($mode & self::RELATIONSHIP_AS_ARRAY_OF_ID) $arr[] = $e->id;
						else if ($mode & self::RELATIONSHIP_AS_ARRAY_OF_ARRAY) $arr[] = $e->toArray($mode); // todo co rekurze?
						else throw new InvalidStateException();
					}
					$result[$name] = $arr;
				}
			}
		}

		return $result;
	}

}