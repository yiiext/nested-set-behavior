<?php
/**
 * NestedSetBehavior
 *
 * TODO: добавить исключения в методы beforeDelete() и beforeSave(),
 * чтобы предотвратить вызов методов save() и delete() напрямую
 * TODO: реализовать перемещение узлов в пределах разных деревьев
 * TODO: обновлять модели в run-time
 *
 * @version 0.85
 * @author creocoder <creocoder@gmail.com>
 */
class ENestedSetBehavior extends CActiveRecordBehavior
{
	public $hasManyRoots=false;
	public $root='root';
	public $left='lft';
	public $right='rgt';
	public $level='level';
	private $_ignoreEvent=false; //TODO: для блокировки исключений

	/**
	 * Named scope. Gets descendants for node.
	 * @param int depth.
	 * @return CActiveRecord the owner.
	 */
	public function descendants($depth=null)
	{
		$owner=$this->getOwner();
		$criteria=$owner->getDbCriteria();
		$alias=$owner->getTableAlias();

		$criteria->mergeWith(array(
			'condition'=>$alias.'.'.$this->left.'>'.$owner->{$this->left}.
				' AND '.$alias.'.'.$this->right.'<'.$owner->{$this->right},
			'order'=>$alias.'.'.$this->left,
		));

		if($depth!==null)
			$criteria->addCondition($alias.'.'.$this->level.'<='.($owner->{$this->level}+$depth));

		if($this->hasManyRoots)
			$criteria->addCondition($alias.'.'.$this->root.'='.$owner->{$this->root});

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
		$criteria=$owner->getDbCriteria();
		$alias=$owner->getTableAlias();

		$criteria->mergeWith(array(
			'condition'=>$alias.'.'.$this->left.'<'.$owner->{$this->left}.
				' AND '.$alias.'.'.$this->right.'>'.$owner->{$this->right},
			'order'=>$alias.'.'.$this->left,
		));

		if($depth!==null)
			$criteria->addCondition($alias.'.'.$this->level.'>='.($owner->{$this->level}+$depth));

		if($this->hasManyRoots)
			$criteria->addCondition($alias.'.'.$this->root.'='.$owner->{$this->root});

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
		$owner->getDbCriteria()->addCondition($owner->getTableAlias().'.'.$this->left.'=1');

		return $owner;
	}

	/**
	 * Gets record of node parent.
	 * @return CActiveRecord the record found. Null if no record is found.
	 */
	public function parent()
	{
		$owner=$this->getOwner();
		$criteria=$owner->getDbCriteria();
		$alias=$owner->getTableAlias();

		$criteria->mergeWith(array(
			'condition'=>$alias.'.'.$this->left.'<'.$owner->{$this->left}.
				' AND '.$alias.'.'.$this->right.'>'.$owner->{$this->right},
			'order'=>$alias.'.'.$this->right,
		));

		if($this->hasManyRoots)
			$criteria->addCondition($alias.'.'.$this->root.'='.$owner->{$this->root});

		return $owner->find();
	}

	/**
	 * Gets record of previous sibling.
	 * @return CActiveRecord the record found. Null if no record is found.
	 */
	public function getPrevSibling($restrictLevel=false) //TODO: переименовать в prev()?
	{
		$owner=$this->getOwner();
		$condition=$this->right.'='.($owner->{$this->left}-1);

		if($this->hasManyRoots)
			$condition.=' AND '.$this->root.'='.$owner->{$this->root};

		if($restrictLevel)
			$condition.=' AND '.$this->level.'='.$owner->{$this->level};

		return $owner->find($condition);
	}

	/**
	 * Gets record of next sibling.
	 * @return CActiveRecord the record found. Null if no record is found.
	 */
	public function getNextSibling($restrictLevel=false) //TODO: переименовать в next()?
	{
		$owner=$this->getOwner();
		$condition=$this->left.'='.($owner->{$this->right}+1);

		if($this->hasManyRoots)
			$condition.=' AND '.$this->root.'='.$owner->{$this->root};

		if($restrictLevel)
			$condition.=' AND '.$this->level.'='.$owner->{$this->level};

		return $owner->find($condition);
	}

	/**
	 * Create root node if multiple-root tree mode. Update node if it's not new.
	 * @return boolean whether the saving succeeds.
	 * @throws CException if many root mode is off.
	 */
	public function save($runValidation=true,$attributes=null)
	{
		$owner=$this->getOwner();

		if(!$runValidation || $owner->validate($attributes))
			return $owner->getIsNewRecord() ? $this->makeRoot($attributes) : $owner->update($attributes);
		else
			return false;
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

		$transaction=$owner->getDbConnection()->beginTransaction();

		try
		{
			$root=$this->hasManyRoots ? $owner->{$this->root} : null;

			if($owner->isLeaf())
				$result=$owner->delete();
			else
			{
				$condition=$this->left.'>='.$owner->{$this->left}.' AND '.
					$this->right.'<='.$owner->{$this->right};

				if($root!==null)
					$condition.=' AND '.$this->root.'='.$root;

				$result=$owner->deleteAll($condition)>0;
			}

			if($result)
			{
				$first=$owner->{$this->right}+1;
				$delta=$owner->{$this->left}-$owner->{$this->right}-1;
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

		if($runValidation && !$owner->validate())
			return false;

		$owner->{$this->level}=$target->{$this->level}+1;
		$key=$target->{$this->right};

		return $this->addNode($target,$key,$attributes);
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

		if($runValidation && !$owner->validate())
			return false;

		$owner->{$this->level}=$target->{$this->level}+1;
		$key=$target->{$this->left}+1;

		return $this->addNode($target,$key,$attributes);
	}

	/**
	 * Inserts node as previous sibling of target.
	 * @return boolean whether the inserting succeeds.
	 * @throws CException if the target node is self or target node is root.
	 */
	public function insertBefore($target,$runValidation=true,$attributes=null)
	{
		$owner=$this->getOwner();

		if($runValidation && !$owner->validate())
			return false;

		if($target->isRoot())
			throw new CException(Yii::t('yiiext','The target node should not be root.'));

		$owner->{$this->level}=$target->{$this->level};
		$key=$target->{$this->left};

		return $this->addNode($target,$key,$attributes);
	}

	/**
	 * Inserts node as next sibling of target.
	 * @return boolean whether the inserting succeeds.
	 * @throws CException if the target node is self or target node is root.
	 */
	public function insertAfter($target,$runValidation=true,$attributes=null)
	{
		$owner=$this->getOwner();

		if($runValidation && !$owner->validate())
			return false;

		if($target->isRoot())
			throw new CException(Yii::t('yiiext','The target node should not be root.'));

		$owner->{$this->level}=$target->{$this->level};
		$key=$target->{$this->right}+1;

		return $this->addNode($target,$key,$attributes);
	}

	/**
	 * Move node as previous sibling of target.
	 * @return boolean whether the moving succeeds.
	 * @throws CException if the target node is self or target node is root.
	 */
	public function moveBefore($target)
	{
		if($target->isRoot())
			throw new CException(Yii::t('yiiext','The target node should not be root.'));

		$key=$target->{$this->left};
		$levelDiff=$target->{$this->level}-$this->getOwner()->{$this->level};

		return $this->moveNode($target,$key,$levelDiff);
	}

	/**
	 * Move node as next sibling of target.
	 * @return boolean whether the moving succeeds.
	 * @throws CException if the target node is self or target node is root.
	 */
	public function moveAfter($target)
	{
		if($target->isRoot())
			throw new CException(Yii::t('yiiext','The target node should not be root.'));

		$key=$target->{$this->right}+1;
		$levelDiff=$target->{$this->level}-$this->getOwner()->{$this->level};

		return $this->moveNode($target,$key,$levelDiff);
	}

	/**
	 * Move node as first child of target.
	 * @return boolean whether the moving succeeds.
	 * @throws CException if the target node is self.
	 */
	public function moveAsFirst($target)
	{
		$key=$target->{$this->left}+1;
		$levelDiff=$target->{$this->level}-$this->getOwner()->{$this->level}+1;

		return $this->moveNode($target,$key,$levelDiff);
	}

	/**
	 * Move node as last child of target.
	 * @return boolean whether the moving succeeds.
	 * @throws CException if the target node is self.
	 */
	public function moveAsLast($target)
	{
		$key=$target->{$this->right};
		$levelDiff=$target->{$this->level}-$this->getOwner()->{$this->level}+1;

		return $this->moveNode($target,$key,$levelDiff);
	}

	/**
	 * Determines if node is descendant of subject node.
	 * @return boolean
	 */
	public function isDescendantOf($subj)
	{
		$owner=$this->getOwner();
		$result=($owner->{$this->left}>$subj->{$this->left})
			&& ($owner->{$this->right}<$subj->{$this->right});

		if($this->hasManyRoots)
			$result=$result && ($owner->{$this->root}===$subj->{$this->root});

		return $result;
	}

	/**
	 * Determines if node is leaf.
	 * @return boolean
	 */
	public function isLeaf()
	{
		$owner=$this->getOwner();

		return $owner->{$this->right}-$owner->{$this->left}===1;
	}

	/**
	 * Determines if node is root.
	 * @return boolean
	 */
	public function isRoot()
	{
		return $this->getOwner()->{$this->left}==1;
	}

	protected function shiftLeftRight($first,$delta,$root)
	{
		$owner=$this->getOwner();

		foreach(array($this->left,$this->right) as $key)
		{
			$condition=$key.'>='.$first;

			if($root!==null)
				$condition.=' AND '.$this->root.'='.$root;

			$owner->updateAll(array($key=>new CDbExpression($key.sprintf('%+d',$delta))),$condition);
		}
	}

	protected function shiftLeftRightRange($first,$last,$delta,$root)
	{
		$owner=$this->getOwner();

		foreach(array($this->left,$this->right) as $key)
		{
			$condition=$key.'>='.$first.' AND '.$key.'<='.$last;

			if($root!==null)
				$condition.=' AND '.$this->root.'='.$root;

			$owner->updateAll(array($key=>new CDbExpression($key.sprintf('%+d',$delta))),$condition);
		}
	}

	protected function addNode($target,$key,$attributes)
	{
		$owner=$this->getOwner();

		if(!$owner->getIsNewRecord())
			throw new CDbException(Yii::t('yiiext','The node cannot be inserted because it is not new.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		$db=$owner->getDbConnection();
		$extTransFlag=$db->getCurrentTransaction();

		if($extTransFlag===null)
			$transaction=$db->beginTransaction();

		try
		{
			$root=$this->hasManyRoots ? $target->{$this->root} : null;
			$this->shiftLeftRight($key,2,$root);
			$owner->{$this->left}=$key;
			$owner->{$this->right}=$key+1;

			if($root!==null)
				$owner->{$this->root}=$root;

			if($owner->insert($attributes))
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
			$owner->{$this->left}=1;
			$owner->{$this->right}=2;
			$owner->{$this->level}=1;

			if($owner->insert($attributes))
			{
				$pk=$owner->{$this->root}=$owner->getPrimaryKey();
				$owner->updateByPk($pk,array($this->root=>$pk));

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

	protected function moveNode($target,$key,$levelDiff)
	{
		$owner=$this->getOwner();

		if($owner->getIsNewRecord())
			throw new CException(Yii::t('yiiext','The node should not be new record.'));

		if($owner->equals($target))
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		if($target->isDescendantOf($owner))
			throw new CException(Yii::t('yiiext','The target node should not be descendant.'));

		if($this->hasManyRoots && $owner->{$this->root}!==$target->{$this->root})
			throw new CException(Yii::t('yiiext','Moving between trees not supported yet.'));

		$db=$owner->getDbConnection();
		$extTransFlag=$db->getCurrentTransaction();

		if($extTransFlag===null)
			$transaction=$db->beginTransaction();

		try
		{
			$left=$owner->{$this->left};
			$right=$owner->{$this->right};
			$delta=$right-$left+1;
			$root=$this->hasManyRoots ? $owner->{$this->root} : null;

			$this->shiftLeftRight($key,$delta,$root);

        	if($left>=$key)
        	{
				$left+=$delta;
				$right+=$delta;
        	}

			$condition=$this->left.'>='.$left.' AND '.$this->right.'<='.$right;

			if($root!==null)
				$condition.=' AND '.$this->root.'='.$root;

			$owner->updateAll(array($this->level=>new CDbExpression($this->level.sprintf('%+d',$levelDiff))),$condition);

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
}