<?php
/**
 * AdjacencyListBehavior
 *
 * @version 0.01 (skeleton)
 * @author creocoder <creocoder@gmail.com>
 */
class EAdjacencyListBehavior extends CActiveRecordBehavior
{
	public $hasLevel=false;
	public $hasWeight=false;
	public $parentAttribute='parent_id';
	public $levelAttribute='level';
	public $weightAttribute='weight';
	private $_ignoreEvent=false;
	private $_deleted=false;
	private $_id;
	private static $_cached;
	private static $_c=0;

	/**
	 * Named scope. Gets descendants for node.
	 * @param int depth
	 * @return CActiveRecord the owner
	 */
	public function descendants($depth=null)
	{
		//check hasLevel if $depth===null

		$owner=$this->getOwner();
		$db=$owner->getDbConnection();
		$criteria=$owner->getDbCriteria();
		$alias=$db->quoteColumnName($owner->getTableAlias());

		if($depth>1)
		{
			$with=array('children');

			for($i=1;$i<$depth-1;$i++)
			{
				$with=array(
					'children'=>array(
						'with'=>$with,
						'alias'=>"children$i",
					),
				);
			}

			$owner->getMetaData()->addRelation('children',array(CActiveRecord::HAS_MANY,get_class($owner),$this->parentAttribute));
			$criteria->mergeWith(array('with'=>$with));
		}

		$criteria->addCondition($alias.'.'.$db->quoteColumnName($this->parentAttribute).'='.$owner->primaryKey);

		return $owner;
	}

	/**
	 * Named scope. Gets children for node (direct descendants only).
	 * @return CActiveRecord the owner
	 */
	public function children()
	{
		return $this->descendants(1);
	}

	public function ancestors($depth=null)
	{
	}

	/**
	 * Gets root node.
	 * @return CActiveRecord the record found. Null if no record is found
	 */
	public function getRoot()
	{
		$owner=$this->getOwner();
		$db=$owner->getDbConnection();
		$owner->getDbCriteria()->addCondition($db->quoteColumnName($owner->getTableAlias()).'.'.$db->quoteColumnName($this->parentAttribute).' IS NULL');

		return $owner->find();
	}

	/**
	 * Gets record of node parent.
	 * @return CActiveRecord the record found. Null if no record is found
	 */
	public function getParent()
	{
	}

	/**
	 * Gets record of previous sibling.
	 * @return CActiveRecord the record found. Null if no record is found
	 */
	public function getPrevSibling()
	{
		//only if level future is on
	}

	/**
	 * Gets record of next sibling.
	 * @return CActiveRecord the record found. Null if no record is found
	 */
	public function getNextSibling()
	{
		//only if level future is on
	}

	/**
	 * Update node if it's not new.
	 * @return boolean whether the saving succeeds
	 */
	public function save($runValidation=true,$attributes=null)
	{
		$owner=$this->getOwner();

		if($runValidation && !$owner->validate($attributes))
			return false;

		if($owner->getIsNewRecord())
			// throw an exception here

		$this->_ignoreEvent=true;
		$result=$owner->update($attributes);
		$this->_ignoreEvent=false;

		return $result;
	}

	public function saveNode($runValidation=true,$attributes=null)
	{
		return $this->save($runValidation,$attributes);
	}

	/**
	 * Deletes node and it's descendants.
	 * @return boolean whether the deletion is successful
	 */
	public function delete()
	{
		// cascad ?
	}

	public function deleteNode()
	{
		return $this->delete();
	}

	/**
	 * Appends node to target as last child.
	 * @return boolean whether the appending succeeds
	 */
	public function appendTo($target,$runValidation=true,$attributes=null)
	{
	}

	/**
	 * Appends target to node as last child.
	 * @return boolean whether the appending succeeds
	 */
	public function append($target,$runValidation=true,$attributes=null)
	{
		return $target->appendTo($this->getOwner(),$runValidation,$attributes);
	}

	/**
	 * Move node as last child of target.
	 * @return boolean whether the moving succeeds
	 */
	public function moveAsLast($target)
	{
		// check hasWeight
	}

	/**
	 * Determines if node is descendant of subject node.
	 * @return boolean
	 */
	public function isDescendantOf($subj)
	{
	}

	/**
	 * Determines if node is leaf.
	 * @return boolean
	 */
	public function isLeaf()
	{
	}

	public function getIsDeletedRecord()
	{
		return $this->_deleted;
	}

	public function setIsDeletedRecord($value)
	{
		$this->_deleted=$value;
	}

	public function afterConstruct($event)
	{
		$owner=$this->getOwner();
		self::$_cached[get_class($owner)][$this->_id=self::$_c++]=$owner;
	}

	public function afterFind($event)
	{
		$owner=$this->getOwner();
		self::$_cached[get_class($owner)][$this->_id=self::$_c++]=$owner;
	}

	public function beforeSave($event)
	{
		if($this->_ignoreEvent)
			return true;
		else
			throw new CDbException(Yii::t('yiiext','You should not use CActiveRecord::save() method when EAjacencyListBehavior attached.'));
	}

	public function beforeDelete($event)
	{
		if($this->_ignoreEvent)
			return true;
		else
			throw new CDbException(Yii::t('yiiext','You should not use CActiveRecord::delete() method when EAjacencyListBehavior attached.'));
	}

	private function correctCachedOnDelete()
	{
	}

	private function correctCachedOnAddNode()
	{
	}

	public function __destruct()
	{
		unset(self::$_cached[get_class($this->getOwner())][$this->_id]);
	}
}