<?php
/**
 * NestedSetBehavior
 *
 * @version 0.99
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
	 * @throws CException if many root mode is off.
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

		$db=$owner->getDbConnection();
		$transaction=$db->beginTransaction();

		try
		{
			$root=$this->hasManyRoots ? $owner->{$this->rootAttribute} : null;

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

				if($root!==null)
					$condition.=' AND '.$db->quoteColumnName($this->rootAttribute).'='.$root;

				$result=$owner->deleteAll($condition)>0;
			}

			if($result)
			{
				$first=$owner->{$this->rightAttribute}+1;
				$delta=$owner->{$this->leftAttribute}-$owner->{$this->rightAttribute}-1;
				$this->shiftLeftRight($first,$delta,$root);
				$transaction->commit();

				return true;
			}
		}
		catch(Exception $e)
		{
			$transaction->rollBack();
		}

		return false;
	}

	public function deleteNode()
	{
		return $this->delete();
	}

	/**
	 * Prepends target to node as first child.
	 * @return boolean whether the prepending succeeds.
	 * @throws CException if the target node is self.
	 */
	public function prepend($target,$runValidation=true,$attributes=null)
	{
		return $target->prependTo($this->getOwner(),$runValidation,$attributes);
	}

	/**
	 * Prepends node to target as first child.
	 * @return boolean whether the prepending succeeds.
	 * @throws CException if the target node is self.
	 */
	public function prependTo($target,$runValidation=true,$attributes=null)
	{
		$owner=$this->getOwner();

		if(!$owner->getIsNewRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because it is not new.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($runValidation && !$owner->validate())
			return false;

		if($this->hasManyRoots)
			$owner->{$this->rootAttribute}=$target->{$this->rootAttribute};

		$owner->{$this->levelAttribute}=$target->{$this->levelAttribute}+1;
		$key=$target->{$this->leftAttribute}+1;

		return $this->addNode($key,$attributes);
	}

	/**
	 * Appends target to node as last child.
	 * @return boolean whether the appending succeeds.
	 * @throws CException if the target node is self.
	 */
	public function append($target,$runValidation=true,$attributes=null)
	{
		return $target->appendTo($this->getOwner(),$runValidation,$attributes);
	}

	/**
	 * Appends node to target as last child.
	 * @return boolean whether the appending succeeds.
	 * @throws CException if the target node is self.
	 */
	public function appendTo($target,$runValidation=true,$attributes=null)
	{
		$owner=$this->getOwner();

		if(!$owner->getIsNewRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because it is not new.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($runValidation && !$owner->validate())
			return false;

		if($this->hasManyRoots)
			$owner->{$this->rootAttribute}=$target->{$this->rootAttribute};

		$owner->{$this->levelAttribute}=$target->{$this->levelAttribute}+1;
		$key=$target->{$this->rightAttribute};

		return $this->addNode($key,$attributes);
	}

	/**
	 * Inserts node as previous sibling of target.
	 * @return boolean whether the inserting succeeds.
	 * @throws CException if the target node is self or target node is root.
	 */
	public function insertBefore($target,$runValidation=true,$attributes=null)
	{
		$owner=$this->getOwner();

		if(!$owner->getIsNewRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because it is not new.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($target->isRoot())
			throw new CException(Yii::t('yiiext','The target node should not be root.'));

		if($runValidation && !$owner->validate())
			return false;

		if($this->hasManyRoots)
			$owner->{$this->rootAttribute}=$target->{$this->rootAttribute};

		$owner->{$this->levelAttribute}=$target->{$this->levelAttribute};
		$key=$target->{$this->leftAttribute};

		return $this->addNode($key,$attributes);
	}

	/**
	 * Inserts node as next sibling of target.
	 * @return boolean whether the inserting succeeds.
	 * @throws CException if the target node is self or target node is root.
	 */
	public function insertAfter($target,$runValidation=true,$attributes=null)
	{
		$owner=$this->getOwner();

		if(!$owner->getIsNewRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because it is not new.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($target->isRoot())
			throw new CException(Yii::t('yiiext','The target node should not be root.'));

		if($runValidation && !$owner->validate())
			return false;

		if($this->hasManyRoots)
			$owner->{$this->rootAttribute}=$target->{$this->rootAttribute};

		$owner->{$this->levelAttribute}=$target->{$this->levelAttribute};
		$key=$target->{$this->rightAttribute}+1;

		return $this->addNode($key,$attributes);
	}

	/**
	 * Move node as previous sibling of target.
	 * @return boolean whether the moving succeeds.
	 * @throws CException if the target node is self or target node is root.
	 */
	public function moveBefore($target)
	{
		$owner=$this->getOwner();

		if($owner->getIsNewRecord())
			throw new CException(Yii::t('yiiext','The node should not be new record.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($target->isDescendantOf($owner))
			throw new CException(Yii::t('yiiext','The target node should not be descendant.'));

		if($target->isRoot())
			throw new CException(Yii::t('yiiext','The target node should not be root.'));

		if($this->hasManyRoots && $owner->{$this->rootAttribute}!==$target->{$this->rootAttribute})
			return $this->moveBetweenTrees($target,$target->{$this->leftAttribute},$target->{$this->levelAttribute}-$owner->{$this->levelAttribute});
		else
			return $this->moveNode($target->{$this->leftAttribute},$target->{$this->levelAttribute}-$owner->{$this->levelAttribute});
	}

	/**
	 * Move node as next sibling of target.
	 * @return boolean whether the moving succeeds.
	 * @throws CException if the target node is self or target node is root.
	 */
	public function moveAfter($target)
	{
		$owner=$this->getOwner();

		if($owner->getIsNewRecord())
			throw new CException(Yii::t('yiiext','The node should not be new record.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($target->isDescendantOf($owner))
			throw new CException(Yii::t('yiiext','The target node should not be descendant.'));

		if($target->isRoot())
			throw new CException(Yii::t('yiiext','The target node should not be root.'));

		if($this->hasManyRoots && $owner->{$this->rootAttribute}!==$target->{$this->rootAttribute})
			return $this->moveBetweenTrees($target,$target->{$this->rightAttribute}+1,$target->{$this->levelAttribute}-$owner->{$this->levelAttribute});
		else
			return $this->moveNode($target->{$this->rightAttribute}+1,$target->{$this->levelAttribute}-$owner->{$this->levelAttribute});
	}

	/**
	 * Move node as first child of target.
	 * @return boolean whether the moving succeeds.
	 * @throws CException if the target node is self.
	 */
	public function moveAsFirst($target)
	{
		$owner=$this->getOwner();

		if($owner->getIsNewRecord())
			throw new CException(Yii::t('yiiext','The node should not be new record.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($target->isDescendantOf($owner))
			throw new CException(Yii::t('yiiext','The target node should not be descendant.'));

		if($this->hasManyRoots && $owner->{$this->rootAttribute}!==$target->{$this->rootAttribute})
			return $this->moveBetweenTrees($target,$target->{$this->leftAttribute}+1,$target->{$this->levelAttribute}-$owner->{$this->levelAttribute}+1);
		else
			return $this->moveNode($target->{$this->leftAttribute}+1,$target->{$this->levelAttribute}-$owner->{$this->levelAttribute}+1);
	}

	/**
	 * Move node as last child of target.
	 * @return boolean whether the moving succeeds.
	 * @throws CException if the target node is self.
	 */
	public function moveAsLast($target)
	{
		$owner=$this->getOwner();

		if($owner->getIsNewRecord())
			throw new CException(Yii::t('yiiext','The node should not be new record.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($target->isDescendantOf($owner))
			throw new CException(Yii::t('yiiext','The target node should not be descendant.'));

		if($this->hasManyRoots && $owner->{$this->rootAttribute}!==$target->{$this->rootAttribute})
			return $this->moveBetweenTrees($target,$target->{$this->rightAttribute},$target->{$this->levelAttribute}-$owner->{$this->levelAttribute}+1);
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

	protected function shiftLeftRight($first,$delta,$root)
	{
		$owner=$this->getOwner();
		$db=$owner->getDbConnection();

		foreach(array($this->leftAttribute,$this->rightAttribute) as $key)
		{
			$condition=$db->quoteColumnName($key).'>='.$first;

			if($root!==null)
				$condition.=' AND '.$db->quoteColumnName($this->rootAttribute).'='.$root;

			$owner->updateAll(array($key=>new CDbExpression($db->quoteColumnName($key).sprintf('%+d',$delta))),$condition);
		}
	}

	protected function shiftLeftRightRange($first,$last,$delta,$root)
	{
		$owner=$this->getOwner();
		$db=$owner->getDbConnection();

		foreach(array($this->leftAttribute,$this->rightAttribute) as $key)
		{
			$condition=$db->quoteColumnName($key).'>='.$first.' AND '.$db->quoteColumnName($key).'<='.$last;

			if($root!==null)
				$condition.=' AND '.$db->quoteColumnName($this->rootAttribute).'='.$root;

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
			$this->shiftLeftRight($key,2,$this->hasManyRoots ? $owner->{$this->rootAttribute} : null);
			$owner->{$this->leftAttribute}=$key;
			$owner->{$this->rightAttribute}=$key+1;
			$this->_ignoreEvent=true;
			$result=$owner->insert($attributes);
			$this->_ignoreEvent=false;

			if($result)
			{
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

		return false;
	}

	protected function makeRoot($attributes)
	{
		if(!$this->hasManyRoots)
			throw new CException(Yii::t('yiiext','Many roots mode is off.'));

		$owner=$this->getOwner();
		$db=$owner->getDbConnection();
		$extTransFlag=$db->getCurrentTransaction();

		if($extTransFlag===null)
			$transaction=$db->beginTransaction();

		try
		{
			$owner->{$this->leftAttribute}=1;
			$owner->{$this->rightAttribute}=2;
			$owner->{$this->levelAttribute}=1;
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
			$root=$this->hasManyRoots ? $owner->{$this->rootAttribute} : null;

			$this->shiftLeftRight($key,$delta,$root);

			if($left>=$key)
			{
				$left+=$delta;
				$right+=$delta;
			}

			$condition=$db->quoteColumnName($this->leftAttribute).'>='.$left.' AND '.$db->quoteColumnName($this->rightAttribute).'<='.$right;

			if($root!==null)
				$condition.=' AND '.$db->quoteColumnName($this->rootAttribute).'='.$root;

			$owner->updateAll(array($this->levelAttribute=>new CDbExpression($db->quoteColumnName($this->levelAttribute).sprintf('%+d',$levelDiff))),$condition);

			$this->shiftLeftRightRange($left,$right,$key-$left,$root);
			$this->shiftLeftRight($right+1,-$delta,$root);

			if($extTransFlag===null)
				$transaction->commit();

			return true;
		}
		catch(Exception $e)
		{
			if($extTransFlag===null)
				$transaction->rollBack();

			return false;
		}
	}

	protected function moveBetweenTrees($target,$key,$levelDiff)
	{
		$owner=$this->getOwner();
		$db=$owner->getDbConnection();
		$extTransFlag=$db->getCurrentTransaction();

		if($extTransFlag===null)
			$transaction=$db->beginTransaction();

		try
		{
			$newRoot=$target->{$this->rootAttribute};
			$oldRoot=$owner->{$this->rootAttribute};
			$oldLeft=$owner->{$this->leftAttribute};
			$oldRight=$owner->{$this->rightAttribute};

			$this->shiftLeftRight($key,$oldRight-$oldLeft+1,$newRoot);

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
				$db->quoteColumnName($this->rootAttribute).'='.$oldRoot
			);

			$this->shiftLeftRight($oldRight+1,$oldLeft-$oldRight-1,$oldRoot);

			if($extTransFlag===null)
				$transaction->commit();

			return true;
		}
		catch(Exception $e)
		{
			if($extTransFlag===null)
				$transaction->rollBack();

			return false;
		}
	}
}