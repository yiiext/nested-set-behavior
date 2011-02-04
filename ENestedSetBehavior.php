<?php
/**
 * NestedSetBehavior
 *
 * TODO: add @throws to comments
 * TODO: check owner and target class to prevent errors
 *
 * @version 0.99b
 * @author creocoder <creocoder@gmail.com>
 */
class ENestedSetBehavior extends CActiveRecordBehavior
{
	public $hasManyRoots=false;
	public $rootAttribute='root';
	public $leftAttribute='lft';
	public $rightAttribute='rgt';
	public $levelAttribute='level';
	private $_ignoreEvent=false;
	private $_deleted=false;
	private $_id;
	private static $_cached;
	private static $_c=0;

	/**
	 * Named scope. Gets descendants for node.
	 * @param int depth.
	 * @return CActiveRecord the owner.
	 */
	public function descendants($depth=null)
	{
		$owner=$this->getOwner();
		$db=$owner->getDbConnection();
		$criteria=$owner->getDbCriteria();
		$alias=$db->quoteColumnName($owner->getTableAlias());

		$criteria->mergeWith(array(
			'condition'=>$alias.'.'.$db->quoteColumnName($this->leftAttribute).'>'.$owner->{$this->leftAttribute}.
				' AND '.$alias.'.'.$db->quoteColumnName($this->rightAttribute).'<'.$owner->{$this->rightAttribute},
			'order'=>$alias.'.'.$db->quoteColumnName($this->leftAttribute),
		));

		if($depth!==null)
			$criteria->addCondition($alias.'.'.$db->quoteColumnName($this->levelAttribute).'<='.($owner->{$this->levelAttribute}+$depth));

		if($this->hasManyRoots)
			$criteria->addCondition($alias.'.'.$db->quoteColumnName($this->rootAttribute).'='.$owner->{$this->rootAttribute});

		return $owner;
	}

	/**
	 * Named scope. Gets children for node (direct descendants only).
	 * @return CActiveRecord the owner.
	 */
	public function children()
	{
		return $this->descendants(1);
	}

	/**
	 * Named scope. Gets ancestors for node.
	 * @param int depth.
	 * @return CActiveRecord the owner.
	 */
	public function ancestors($depth=null)
	{
		$owner=$this->getOwner();
		$db=$owner->getDbConnection();
		$criteria=$owner->getDbCriteria();
		$alias=$db->quoteColumnName($owner->getTableAlias());

		$criteria->mergeWith(array(
			'condition'=>$alias.'.'.$db->quoteColumnName($this->leftAttribute).'<'.$owner->{$this->leftAttribute}.
				' AND '.$alias.'.'.$db->quoteColumnName($this->rightAttribute).'>'.$owner->{$this->rightAttribute},
			'order'=>$alias.'.'.$db->quoteColumnName($this->leftAttribute),
		));

		if($depth!==null)
			$criteria->addCondition($alias.'.'.$db->quoteColumnName($this->levelAttribute).'>='.($owner->{$this->levelAttribute}+$depth));

		if($this->hasManyRoots)
			$criteria->addCondition($alias.'.'.$db->quoteColumnName($this->rootAttribute).'='.$owner->{$this->rootAttribute});

		return $owner;
	}

	/**
	 * Named scope. Gets root node(s).
	 * @param int depth.
	 * @return CActiveRecord the owner.
	 */
	public function roots()
	{
		$owner=$this->getOwner();
		$db=$owner->getDbConnection();
		$owner->getDbCriteria()->addCondition($db->quoteColumnName($owner->getTableAlias()).'.'.$db->quoteColumnName($this->leftAttribute).'=1');

		return $owner;
	}

	/**
	 * Gets record of node parent.
	 * @return CActiveRecord the record found. Null if no record is found.
	 */
	public function getParent()
	{
		$owner=$this->getOwner();
		$db=$owner->getDbConnection();
		$criteria=$owner->getDbCriteria();
		$alias=$db->quoteColumnName($owner->getTableAlias());

		$criteria->mergeWith(array(
			'condition'=>$alias.'.'.$db->quoteColumnName($this->leftAttribute).'<'.$owner->{$this->leftAttribute}.
				' AND '.$alias.'.'.$db->quoteColumnName($this->rightAttribute).'>'.$owner->{$this->rightAttribute},
			'order'=>$alias.'.'.$db->quoteColumnName($this->rightAttribute),
		));

		if($this->hasManyRoots)
			$criteria->addCondition($alias.'.'.$db->quoteColumnName($this->rootAttribute).'='.$owner->{$this->rootAttribute});

		return $owner->find();
	}

	/**
	 * Gets record of previous sibling.
	 * @return CActiveRecord the record found. Null if no record is found.
	 */
	public function getPrevSibling()
	{
		$owner=$this->getOwner();
		$db=$owner->getDbConnection();
		$criteria=$owner->getDbCriteria();
		$alias=$db->quoteColumnName($owner->getTableAlias());
		$criteria->addCondition($alias.'.'.$db->quoteColumnName($this->rightAttribute).'='.($owner->{$this->leftAttribute}-1));

		if($this->hasManyRoots)
			$criteria->addCondition($alias.'.'.$db->quoteColumnName($this->rootAttribute).'='.$owner->{$this->rootAttribute});

		return $owner->find();
	}

	/**
	 * Gets record of next sibling.
	 * @return CActiveRecord the record found. Null if no record is found.
	 */
	public function getNextSibling()
	{
		$owner=$this->getOwner();
		$db=$owner->getDbConnection();
		$criteria=$owner->getDbCriteria();
		$alias=$db->quoteColumnName($owner->getTableAlias());
		$criteria->addCondition($alias.'.'.$db->quoteColumnName($this->leftAttribute).'='.($owner->{$this->rightAttribute}+1));

		if($this->hasManyRoots)
			$criteria->addCondition($alias.'.'.$db->quoteColumnName($this->rootAttribute).'='.$owner->{$this->rootAttribute});

		return $owner->find();
	}

	/**
	 * Create root node if multiple-root tree mode. Update node if it's not new.
	 * @return boolean whether the saving succeeds.
	 */
	public function save($runValidation=true,$attributes=null)
	{
		$owner=$this->getOwner();

		if($runValidation && !$owner->validate($attributes))
			return false;

		if($owner->getIsNewRecord())
			return $this->makeRoot($attributes);

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
	 * @return boolean whether the deletion is successful.
	 */
	public function delete()
	{
		$owner=$this->getOwner();

		if($owner->getIsNewRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be deleted because it is new.'));

		if($this->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be deleted because it is already deleted.'));

		$db=$owner->getDbConnection();
		$transaction=$db->beginTransaction();

		try
		{
			if($owner->isLeaf())
			{
				$this->_ignoreEvent=true;
				$result=$owner->delete();
				$this->_ignoreEvent=false;
			}
			else
			{
				$condition=$db->quoteColumnName($this->leftAttribute).'>='.$owner->{$this->leftAttribute}.' AND '.
					$db->quoteColumnName($this->rightAttribute).'<='.$owner->{$this->rightAttribute};

				if($this->hasManyRoots)
					$condition.=' AND '.$db->quoteColumnName($this->rootAttribute).'='.$owner->{$this->rootAttribute};

				$result=$owner->deleteAll($condition)>0;
			}

			if($result)
			{
				$this->shiftLeftRight($owner->{$this->rightAttribute}+1,$owner->{$this->leftAttribute}-$owner->{$this->rightAttribute}-1);
				$transaction->commit();
				$this->correctCachedOnDelete();

				return true;
			}
		}
		catch(Exception $e)
		{
			$transaction->rollBack();
		}

		return false;
	}

	protected function correctCachedOnDelete()
	{
		$owner=$this->getOwner();
		$left=$owner->{$this->leftAttribute};
		$right=$owner->{$this->rightAttribute};
		$key=$right+1;
		$delta=$left-$right-1;

		foreach(self::$_cached[get_class($owner)] as $node)
		{
			if($node->getIsNewRecord() || $node->getIsDeletedRecord())
				continue;

			if($this->hasManyRoots && $owner->{$this->rootAttribute}!==$node->{$this->rootAttribute})
				continue;

			if($node->{$this->leftAttribute}>=$left && $node->{$this->rightAttribute}<=$right)
				$node->setIsDeletedRecord(true);
			else
			{
				if($node->{$this->leftAttribute}>=$key)
					$node->{$this->leftAttribute}+=$delta;

				if($node->{$this->rightAttribute}>=$key)
					$node->{$this->rightAttribute}+=$delta;
			}
		}
	}

	public function deleteNode()
	{
		return $this->delete();
	}

	/**
	 * Prepends node to target as first child.
	 * @return boolean whether the prepending succeeds.
	 */
	public function prependTo($target,$runValidation=true,$attributes=null)
	{
		$owner=$this->getOwner();

		if(!$owner->getIsNewRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because it is not new.'));

		if($this->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because it is deleted.'));

		if($target->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because target node is deleted.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($runValidation && !$owner->validate())
			return false;

		if($this->hasManyRoots)
			$owner->{$this->rootAttribute}=$target->{$this->rootAttribute};

		$owner->{$this->levelAttribute}=$target->{$this->levelAttribute}+1;

		return $this->addNode($target->{$this->leftAttribute}+1,$attributes);
	}

	/**
	 * Prepends target to node as first child.
	 * @return boolean whether the prepending succeeds.
	 */
	public function prepend($target,$runValidation=true,$attributes=null)
	{
		return $target->prependTo($this->getOwner(),$runValidation,$attributes);
	}

	/**
	 * Appends node to target as last child.
	 * @return boolean whether the appending succeeds.
	 */
	public function appendTo($target,$runValidation=true,$attributes=null)
	{
		$owner=$this->getOwner();

		if(!$owner->getIsNewRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because it is not new.'));

		if($this->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because it is deleted.'));

		if($target->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because target node is deleted.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($runValidation && !$owner->validate())
			return false;

		if($this->hasManyRoots)
			$owner->{$this->rootAttribute}=$target->{$this->rootAttribute};

		$owner->{$this->levelAttribute}=$target->{$this->levelAttribute}+1;

		return $this->addNode($target->{$this->rightAttribute},$attributes);
	}

	/**
	 * Appends target to node as last child.
	 * @return boolean whether the appending succeeds.
	 */
	public function append($target,$runValidation=true,$attributes=null)
	{
		return $target->appendTo($this->getOwner(),$runValidation,$attributes);
	}

	/**
	 * Inserts node as previous sibling of target.
	 * @return boolean whether the inserting succeeds.
	 */
	public function insertBefore($target,$runValidation=true,$attributes=null)
	{
		$owner=$this->getOwner();

		if(!$owner->getIsNewRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because it is not new.'));

		if($this->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because it is deleted.'));

		if($target->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because target node is deleted.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($target->isRoot())
			throw new CException(Yii::t('yiiext','The target node should not be root.'));

		if($runValidation && !$owner->validate())
			return false;

		if($this->hasManyRoots)
			$owner->{$this->rootAttribute}=$target->{$this->rootAttribute};

		$owner->{$this->levelAttribute}=$target->{$this->levelAttribute};

		return $this->addNode($target->{$this->leftAttribute},$attributes);
	}

	/**
	 * Inserts node as next sibling of target.
	 * @return boolean whether the inserting succeeds.
	 */
	public function insertAfter($target,$runValidation=true,$attributes=null)
	{
		$owner=$this->getOwner();

		if(!$owner->getIsNewRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because it is not new.'));

		if($this->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because it is deleted.'));

		if($target->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because target node is deleted.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($target->isRoot())
			throw new CException(Yii::t('yiiext','The target node should not be root.'));

		if($runValidation && !$owner->validate())
			return false;

		if($this->hasManyRoots)
			$owner->{$this->rootAttribute}=$target->{$this->rootAttribute};

		$owner->{$this->levelAttribute}=$target->{$this->levelAttribute};

		return $this->addNode($target->{$this->rightAttribute}+1,$attributes);
	}

	/**
	 * Move node as previous sibling of target.
	 * @return boolean whether the moving succeeds.
	 */
	public function moveBefore($target)
	{
		$owner=$this->getOwner();

		if($owner->getIsNewRecord())
			throw new CException(Yii::t('yiiext','The node should not be new record.'));

		if($this->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The node should not be deleted.'));

		if($target->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The target node should not be deleted.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($target->isDescendantOf($owner))
			throw new CException(Yii::t('yiiext','The target node should not be descendant.'));

		if($target->isRoot())
			throw new CException(Yii::t('yiiext','The target node should not be root.'));

		if($this->hasManyRoots && $owner->{$this->rootAttribute}!==$target->{$this->rootAttribute})
			return $this->moveBetweenTrees($target->{$this->leftAttribute},$target->{$this->rootAttribute},$target->{$this->levelAttribute}-$owner->{$this->levelAttribute});
		else
			return $this->moveNode($target->{$this->leftAttribute},$target->{$this->levelAttribute}-$owner->{$this->levelAttribute});
	}

	/**
	 * Move node as next sibling of target.
	 * @return boolean whether the moving succeeds.
	 */
	public function moveAfter($target)
	{
		$owner=$this->getOwner();

		if($owner->getIsNewRecord())
			throw new CException(Yii::t('yiiext','The node should not be new record.'));

		if($this->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The node should not be deleted.'));

		if($target->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The target node should not be deleted.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($target->isDescendantOf($owner))
			throw new CException(Yii::t('yiiext','The target node should not be descendant.'));

		if($target->isRoot())
			throw new CException(Yii::t('yiiext','The target node should not be root.'));

		if($this->hasManyRoots && $owner->{$this->rootAttribute}!==$target->{$this->rootAttribute})
			return $this->moveBetweenTrees($target->{$this->rightAttribute}+1,$target->{$this->rootAttribute},$target->{$this->levelAttribute}-$owner->{$this->levelAttribute});
		else
			return $this->moveNode($target->{$this->rightAttribute}+1,$target->{$this->levelAttribute}-$owner->{$this->levelAttribute});
	}

	/**
	 * Move node as first child of target.
	 * @return boolean whether the moving succeeds.
	 */
	public function moveAsFirst($target)
	{
		$owner=$this->getOwner();

		if($owner->getIsNewRecord())
			throw new CException(Yii::t('yiiext','The node should not be new record.'));

		if($this->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The node should not be deleted.'));

		if($target->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The target node should not be deleted.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($target->isDescendantOf($owner))
			throw new CException(Yii::t('yiiext','The target node should not be descendant.'));

		if($this->hasManyRoots && $owner->{$this->rootAttribute}!==$target->{$this->rootAttribute})
			return $this->moveBetweenTrees($target->{$this->leftAttribute}+1,$target->{$this->rootAttribute},$target->{$this->levelAttribute}-$owner->{$this->levelAttribute}+1);
		else
			return $this->moveNode($target->{$this->leftAttribute}+1,$target->{$this->levelAttribute}-$owner->{$this->levelAttribute}+1);
	}

	/**
	 * Move node as last child of target.
	 * @return boolean whether the moving succeeds.
	 */
	public function moveAsLast($target)
	{
		$owner=$this->getOwner();

		if($owner->getIsNewRecord())
			throw new CException(Yii::t('yiiext','The node should not be new record.'));

		if($this->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The node should not be deleted.'));

		if($target->getIsDeletedRecord())
			throw new CDbException(Yii::t('yiiext','The target node should not be deleted.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($target->isDescendantOf($owner))
			throw new CException(Yii::t('yiiext','The target node should not be descendant.'));

		if($this->hasManyRoots && $owner->{$this->rootAttribute}!==$target->{$this->rootAttribute})
			return $this->moveBetweenTrees($target->{$this->rightAttribute},$target->{$this->rootAttribute},$target->{$this->levelAttribute}-$owner->{$this->levelAttribute}+1);
		else
			return $this->moveNode($target->{$this->rightAttribute},$target->{$this->levelAttribute}-$owner->{$this->levelAttribute}+1);
	}

	/**
	 * Determines if node is descendant of subject node.
	 * @return boolean
	 */
	public function isDescendantOf($subj)
	{
		$owner=$this->getOwner();
		$result=($owner->{$this->leftAttribute}>$subj->{$this->leftAttribute})
			&& ($owner->{$this->rightAttribute}<$subj->{$this->rightAttribute});

		if($this->hasManyRoots)
			$result=$result && ($owner->{$this->rootAttribute}===$subj->{$this->rootAttribute});

		return $result;
	}

	/**
	 * Determines if node is leaf.
	 * @return boolean
	 */
	public function isLeaf()
	{
		$owner=$this->getOwner();

		return $owner->{$this->rightAttribute}-$owner->{$this->leftAttribute}===1;
	}

	/**
	 * Determines if node is root.
	 * @return boolean
	 */
	public function isRoot()
	{
		return $this->getOwner()->{$this->leftAttribute}==1;
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
			throw new CDbException(Yii::t('yiiext','You should not use CActiveRecord::save() method when ENestedSetBehavior attached.'));
	}

	public function beforeDelete($event)
	{
		if($this->_ignoreEvent)
			return true;
		else
			throw new CDbException(Yii::t('yiiext','You should not use CActiveRecord::delete() method when ENestedSetBehavior attached.'));
	}

	protected function shiftLeftRight($first,$delta)
	{
		$owner=$this->getOwner();
		$db=$owner->getDbConnection();

		foreach(array($this->leftAttribute,$this->rightAttribute) as $key)
		{
			$condition=$db->quoteColumnName($key).'>='.$first;

			if($this->hasManyRoots)
				$condition.=' AND '.$db->quoteColumnName($this->rootAttribute).'='.$owner->{$this->rootAttribute};

			$owner->updateAll(array($key=>new CDbExpression($db->quoteColumnName($key).sprintf('%+d',$delta))),$condition);
		}
	}

	protected function addNode($key,$attributes)
	{
		$owner=$this->getOwner();
		$db=$owner->getDbConnection();
		$extTransFlag=$db->getCurrentTransaction();

		if($extTransFlag===null)
			$transaction=$db->beginTransaction();

		try
		{
			$this->shiftLeftRight($key,2);
			$owner->{$this->leftAttribute}=$key;
			$owner->{$this->rightAttribute}=$key+1;
			$this->_ignoreEvent=true;
			$result=$owner->insert($attributes);
			$this->_ignoreEvent=false;

			if($result)
			{
				if($extTransFlag===null)
					$transaction->commit();

				$this->correctCachedOnAddNode($key);

				return true;
			}
			else if($extTransFlag===null)
				$transaction->rollBack();
		}
		catch(Exception $e)
		{
			if($extTransFlag===null)
				$transaction->rollBack();
		}

		return false;
	}

	protected function correctCachedOnAddNode($key)
	{
		$owner=$this->getOwner();

		foreach(self::$_cached[get_class($owner)] as $node)
		{
			if($node->getIsNewRecord() || $node->getIsDeletedRecord())
				continue;

			if($this->hasManyRoots && $owner->{$this->rootAttribute}!==$node->{$this->rootAttribute})
				continue;

			if($node->{$this->leftAttribute}>=$key)
				$node->{$this->leftAttribute}+=2;

			if($node->{$this->rightAttribute}>=$key)
				$node->{$this->rightAttribute}+=2;
		}
	}

	protected function makeRoot($attributes)
	{
		$owner=$this->getOwner();
		$owner->{$this->leftAttribute}=1;
		$owner->{$this->rightAttribute}=2;
		$owner->{$this->levelAttribute}=1;

		if($this->hasManyRoots)
		{
			$db=$owner->getDbConnection();
			$extTransFlag=$db->getCurrentTransaction();

			if($extTransFlag===null)
				$transaction=$db->beginTransaction();

			try
			{
				$this->_ignoreEvent=true;
				$result=$owner->insert($attributes);
				$this->_ignoreEvent=false;

				if($result)
				{
					$pk=$owner->{$this->rootAttribute}=$owner->getPrimaryKey();
					$owner->updateByPk($pk,array($this->rootAttribute=>$pk));

					if($extTransFlag===null)
						$transaction->commit();

					return true;
				}
				else if($extTransFlag===null)
					$transaction->rollBack();
			}
			catch(Exception $e)
			{
				if($extTransFlag===null)
					$transaction->rollBack();
			}
		}
		else
		{
			if($owner->roots()->exists())
				throw new CException(Yii::t('yiiext','Cannot create more than one root in single root mode.'));

			$this->_ignoreEvent=true;
			$result=$owner->insert($attributes);
			$this->_ignoreEvent=false;

			if($result)
				return true;
		}

		return false;
	}

	protected function moveNode($key,$levelDiff)
	{
		$owner=$this->getOwner();
		$db=$owner->getDbConnection();
		$extTransFlag=$db->getCurrentTransaction();

		if($extTransFlag===null)
			$transaction=$db->beginTransaction();

		try
		{
			$left=$owner->{$this->leftAttribute};
			$right=$owner->{$this->rightAttribute};
			$delta=$right-$left+1;
			$this->shiftLeftRight($key,$delta);

			if($left>=$key)
			{
				$left+=$delta;
				$right+=$delta;
			}

			$condition=$db->quoteColumnName($this->leftAttribute).'>='.$left.' AND '.$db->quoteColumnName($this->rightAttribute).'<='.$right;

			if($this->hasManyRoots)
			{
				$rootCondition=' AND '.$db->quoteColumnName($this->rootAttribute).'='.$owner->{$this->rootAttribute};
				$condition.=$rootCondition;
			}

			$owner->updateAll(array($this->levelAttribute=>new CDbExpression($db->quoteColumnName($this->levelAttribute).sprintf('%+d',$levelDiff))),$condition);

			foreach(array($this->leftAttribute,$this->rightAttribute) as $attribute)
			{
				$condition=$db->quoteColumnName($attribute).'>='.$left.' AND '.$db->quoteColumnName($attribute).'<='.$right;

				if($this->hasManyRoots)
					$condition.=$rootCondition;

				$owner->updateAll(array($attribute=>new CDbExpression($db->quoteColumnName($attribute).sprintf('%+d',$key-$left))),$condition);
			}

			$this->shiftLeftRight($right+1,-$delta);

			if($extTransFlag===null)
				$transaction->commit();

			$this->correctCachedOnMoveNode($key,$levelDiff);

			return true;
		}
		catch(Exception $e)
		{
			if($extTransFlag===null)
				$transaction->rollBack();

			return false;
		}
	}

	protected function correctCachedOnMoveNode($key,$levelDiff)
	{
		$owner=$this->getOwner();
		$left=$owner->{$this->leftAttribute};
		$right=$owner->{$this->rightAttribute};
		$delta=$right-$left+1;

		if($left>=$key)
		{
			$left+=$delta;
			$right+=$delta;
		}

		foreach(self::$_cached[get_class($owner)] as $node)
		{
			if($node->getIsNewRecord() || $node->getIsDeletedRecord())
				continue;

			if($this->hasManyRoots && $owner->{$this->rootAttribute}!==$node->{$this->rootAttribute})
				continue;

			if($node->{$this->leftAttribute}>=$key && $node->{$this->rightAttribute}>=$key)
				$node->{$this->leftAttribute}+=$delta;

			if($node->{$this->rightAttribute}>=$key)
				$node->{$this->rightAttribute}+=$delta;

			if($node->{$this->leftAttribute}>=$left && $node->{$this->rightAttribute}<=$right)
				$node->{$this->levelAttribute}+=$levelDiff;

			if($node->{$this->leftAttribute}>=$left && $node->{$this->leftAttribute}<=$right)
				$node->{$this->leftAttribute}+=$key-$left;

			if($node->{$this->rightAttribute}>=$left && $node->{$this->rightAttribute}<=$right)
				$node->{$this->rightAttribute}+=$key-$left;

			if($node->{$this->leftAttribute}>=$right+1)
				$node->{$this->leftAttribute}-=$delta;

			if($node->{$this->rightAttribute}>=$right+1)
				$node->{$this->rightAttribute}-=$delta;
		}
	}

	protected function moveBetweenTrees($key,$newRoot,$levelDiff)
	{
		$owner=$this->getOwner();
		$db=$owner->getDbConnection();
		$extTransFlag=$db->getCurrentTransaction();

		if($extTransFlag===null)
			$transaction=$db->beginTransaction();

		try
		{
			$oldLeft=$owner->{$this->leftAttribute};
			$oldRight=$owner->{$this->rightAttribute};

			foreach(array($this->leftAttribute,$this->rightAttribute) as $attribute)
			{
				$condition=$db->quoteColumnName($attribute).'>='.$key.' AND '.$db->quoteColumnName($this->rootAttribute).'='.$newRoot;
				$owner->updateAll(array($attribute=>new CDbExpression($db->quoteColumnName($attribute).sprintf('%+d',$oldRight-$oldLeft+1))),$condition);
			}

			$diff=$key-$oldLeft;
			$owner->updateAll(
				array(
					$this->leftAttribute=>new CDbExpression($db->quoteColumnName($this->leftAttribute).sprintf('%+d',$diff)),
					$this->rightAttribute=>new CDbExpression($db->quoteColumnName($this->rightAttribute).sprintf('%+d',$diff)),
					$this->levelAttribute=>new CDbExpression($db->quoteColumnName($this->levelAttribute).sprintf('%+d',$levelDiff)),
					$this->rootAttribute=>$newRoot,
				),
				$db->quoteColumnName($this->leftAttribute).'>='.$oldLeft.' AND '.
				$db->quoteColumnName($this->rightAttribute).'<='.$oldRight.' AND '.
				$db->quoteColumnName($this->rootAttribute).'='.$owner->{$this->rootAttribute}
			);

			$this->shiftLeftRight($oldRight+1,$oldLeft-$oldRight-1);

			if($extTransFlag===null)
				$transaction->commit();

			$this->correctCachedOnMoveBetweenTrees($key,$newRoot,$levelDiff);

			return true;
		}
		catch(Exception $e)
		{
			if($extTransFlag===null)
				$transaction->rollBack();

			return false;
		}
	}

	protected function correctCachedOnMoveBetweenTrees($key,$newRoot,$levelDiff)
	{
		$owner=$this->getOwner();
		$oldLeft=$owner->{$this->leftAttribute};
		$oldRight=$owner->{$this->rightAttribute};

		foreach(self::$_cached[get_class($owner)] as $node)
		{
			if($node->getIsNewRecord() || $node->getIsDeletedRecord())
				continue;

			if($node->{$this->rootAttribute}===$newRoot)
			{
				if($node->{$this->leftAttribute}>=$key)
					$node->{$this->leftAttribute}+=$oldRight-$oldLeft+1;

				if($node->{$this->rightAttribute}>=$key)
					$node->{$this->rightAttribute}+=$oldRight-$oldLeft+1;
			}
			else if($node->{$this->rootAttribute}===$owner->{$this->rootAttribute})
			{
				if($node->{$this->leftAttribute}>=$oldLeft && $node->{$this->rightAttribute}<=$oldRight)
				{
					$node->{$this->leftAttribute}+=$key-$oldLeft;
					$node->{$this->rightAttribute}+=$key-$oldLeft;
					$node->{$this->levelAttribute}+=$levelDiff;
					$node->{$this->rootAttribute}=$newRoot;
				}
				else
				{
					if($node->{$this->leftAttribute}>=$oldRight+1)
						$node->{$this->leftAttribute}+=$oldLeft-$oldRight-1;

					if($node->{$this->rightAttribute}>=$oldRight+1)
						$node->{$this->rightAttribute}+=$oldLeft-$oldRight-1;
				}
			}
		}
	}

	public function __destruct()
	{
		unset(self::$_cached[get_class($this->getOwner())][$this->_id]);
	}
}