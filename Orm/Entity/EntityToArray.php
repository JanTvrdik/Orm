<?php

/**
 * Entity prevadi na array, je monze nastavit co udelat z asociacemi
 * @see Entity::toArray()
 */
class EntityToArray extends Object
{
	/**#@+ @var int nastaveni prevodu */

	/** Vse se vraci tak jak je */
	const AS_IS = 9; // self::ENTITY_AS_IS | self::RELATIONSHIP_AS_IS
	/** Entity se prevedou na id (i v ManyToMany OneToMany atd.) */
	const AS_ID = 18; // self::ENTITY_AS_ID | self::RELATIONSHIP_AS_ARRAY_OF_ID
	/** Entity se prevedou na pole (i v ManyToMany OneToMany atd.) */
	const AS_ARRAY = 36; // self::ENTITY_AS_ARRAY | self::RELATIONSHIP_AS_ARRAY_OF_ARRAY

	/** Entity se vraceji tak jak jsou */
	const ENTITY_AS_IS = 1;
	/** Entity se prevedou na id */
	const ENTITY_AS_ID = 2;
	/** Entity se prevedou na pole */
	const ENTITY_AS_ARRAY = 4;

	/** ManyToMany OneToMany atd. se vraceji tak jak jsou */
	const RELATIONSHIP_AS_IS = 8;
	/** ManyToMany OneToMany atd. se prevedou na pole a jejich Entity na idcka */
	const RELATIONSHIP_AS_ARRAY_OF_ID = 16;
	/** ManyToMany OneToMany atd. se prevedou na pole a jejich Entity na pole */
	const RELATIONSHIP_AS_ARRAY_OF_ARRAY = 32;

	/**#@-*/

	/**
	 * @internal
	 * @param IEntity
	 * @param array
	 * @param int
	 */
	public static function toArray(IEntity $entity, array $rules, $mode = self::AS_IS)
	{
		if ($mode === NULL) $mode = self::AS_IS;
		$result = array(
			'id' => isset($entity->id) ? $entity->id : NULL,
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
					else throw new InvalidStateException('No mode for entity');
				}
				else if ($result[$name] instanceof IRelationship AND !($mode & self::RELATIONSHIP_AS_IS))
				{
					$arr = array();
					foreach ($result[$name] as $e)
					{
						if ($mode & self::RELATIONSHIP_AS_ARRAY_OF_ID) $arr[] = $e->id;
						else if ($mode & self::RELATIONSHIP_AS_ARRAY_OF_ARRAY) $arr[] = $e->toArray($mode); // todo co rekurze?
						else throw new InvalidStateException('No mode for relationship');
					}
					$result[$name] = $arr;
				}
			}
		}

		return $result;
	}

}