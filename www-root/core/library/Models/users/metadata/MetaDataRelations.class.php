<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 *
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
*/

/**
 * 
 * Provides a collection wrapper to a set of MetaDataRelation objects and a method for aquiring them.
 * @author Jonathan Fingland
 *
 */
class MetaDataRelations extends Collection {
	/**
	 * Returns a collection of MetaDataRelation objects based on the relations mask generated by the four parameters
	 * <code>
	 * $relations = MetaDataRelations::getRelations(1); 
	 * // returns relations for organisation_id 1
	 * 
	 * $relations = MetaDataRelations::getRelations(1, "student"); 
	 * // returns relations for members of the "student" group in organisation_id 1
	 * </code>
	 * @param int $organisation
	 * @param string $group
	 * @param string $role
	 * @param int $user Proxy ID
	 * @return MetaDataRelations
	 */
	public static function getRelations($organisation=null, $group=null, $role=null, $user=null) {
		$cache = SimpleCache::getCache();
		$relation_set = $cache->get("MetaTypeRelation", "$organization-$group-$role-$user");
		if ($relation_set) {
			return $relation_set;
		}
		global $db;
		$conditions = generateMaskConditions($organisation, $group, $role, $user);
		$query = "SELECT * from `meta_type_relations`";
		if ($conditions) {
			$query .= "\n WHERE ".$conditions;
			
		}
		$results = $db->getAll($query);
		$relations = array();
		if ($results) {
			foreach ($results as $result) {
				$relation =  MetaDataRelation::fromArray($result);
				$relations[] = $relation;
			}
		}
		$relation_set = new self($relations);
		$cache->set($relation_set,"MetaTypeRelation", "$organization-$group-$role-$user" );
		return $relation_set;
	}
	
}