<?php
/**
 * NestedSetBehavior
 *
 * TODO: сделать возможным удаление через delete(), а не remove()
 * TODO: сделать возможным создание корня через save(), а не createRoot()
 * TODO: проверять существование цели в appendTo,prependTo,insertBefore,insertAfter?
 * TODO: запретить перемещение родителя в своего потомка
 * TODO: ввести статическую переменную и обновлять модели в run-time
 *
 * @version 0.72
 * @author creocoder <creocoder@gmail.com>
 */
class ENestedSetBehavior extends CActiveRecordBehavior
{
	public $hasManyRoots=false;
	public $root='root';
	public $left='lft';
	public $right='rgt';
	public $level='level';

	/**
	 * Named scope. Gets descendants for node.
	 * @param int depth.
	 * @return CActiveRecord the owner.
	 */
	public function descendants($depth=null)
	{
		$owner=$this->getOwner();
		$criteria=$owner->getDbCriteria();
		$alias=$criteria->alias===null ? 't' : $criteria->alias; //TODO: watch issue 914

		$criteria->mergeWith(array(
			'condition'=>$alias.'.'.$this->left.'>'.$owner->getAttribute($this->left).
				' AND '.$alias.'.'.$this->right.'<'.$owner->getAttribute($this->right),
			'order'=>$alias.'.'.$this->left,
		));

		if($depth!==null)
			$criteria->addCondition($alias.'.'.$this->level.'<='.($owner->getAttribute($this->level)+$depth));

		if($this->hasManyRoots)
			$criteria->addCondition($alias.'.'.$this->root.'='.$owner->getAttribute($this->root));

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
		$alias=$criteria->alias===null ? 't' : $criteria->alias; //TODO: watch issue 914

		$criteria->mergeWith(array(
			'condition'=>$alias.'.'.$this->left.'<'.$owner->getAttribute($this->left).
				' AND '.$alias.'.'.$this->right.'>'.$owner->getAttribute($this->right),
			'order'=>$alias.'.'.$this->left,
		));

		if($depth!==null)
			$criteria->addCondition($alias.'.'.$this->level.'>='.($owner->getAttribute($this->level)+$depth));

		if($this->hasManyRoots)
			$criteria->addCondition($alias.'.'.$this->root.'='.$owner->getAttribute($this->root));

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
		$criteria=$owner->getDbCriteria();
		$alias=$criteria->alias===null ? 't' : $criteria->alias; //TODO: watch issue 914

		$criteria->addCondition($alias.'.'.$this->left.'=1');

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
		$alias=$criteria->alias===null ? 't' : $criteria->alias; //TODO: watch issue 914

		$criteria->mergeWith(array(
			'condition'=>$alias.'.'.$this->left.'<'.$owner->getAttribute($this->left).
				' AND '.$alias.'.'.$this->right.'>'.$owner->getAttribute($this->right),
			'order'=>$alias.'.'.$this->right,
		));

		if($this->hasManyRoots)
			$criteria->addCondition($alias.'.'.$this->root.'='.$owner->getAttribute($this->root));

		return $owner->find();
	}

	/**
	 * Gets record of previous sibling.
	 * @return CActiveRecord the record found. Null if no record is found.
	 */
	public function getPrevSibling() //TODO: переименовать в prev()?
	{
		$owner=$this->getOwner();
		$condition=$this->right.'='.$owner->getAttribute($this->left)-1;

		if($this->hasManyRoots)
			$condition.=' AND '.$this->root.'='.$owner->getAttribute($this->root);

		return $owner->find($condition);
	}

	/**
	 * Gets record of next sibling.
	 * @return CActiveRecord the record found. Null if no record is found.
	 */
	public function getNextSibling() //TODO: переименовать в next()?
	{
		$owner=$this->getOwner();
		$condition=$this->left.'='.$owner->getAttribute($this->right)+1;

		if($this->hasManyRoots)
			$condition.=' AND '.$this->root.'='.$owner->getAttribute($this->root);

		return $owner->find($condition);
	}

	/**
	 * Create root node. Only used in multiple-root trees.
	 * @return boolean whether the creating succeeds.
	 * @throws CException if many root mode is off.
	 */
	public function createRoot($runValidation=true) //TODO: переименовать в saveAsRoot()?
	{
		if(!$this->hasManyRoots)
			throw new CException(Yii::t('yiiext','Many roots mode is off.'));

		$owner=$this->getOwner();

		if($runValidation && !$owner->validate())
			return false;

		$db=$owner->getDbConnection();
		$extTransFlag=$db->getCurrentTransaction();

		if($extTransFlag===null)
			$transaction=$db->beginTransaction();

		try
		{
			$owner->setAttribute($this->left,1);
			$owner->setAttribute($this->right,2);
			$owner->setAttribute($this->level,1);
			$owner->save(false);
			$owner->setAttribute($this->root,$owner->getPrimaryKey());
			$owner->save(false);

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

	/**
	 * Deletes node it's descendants.
	 * @return boolean whether the creating succeeds.
	 */
	public function remove()
	{
		$owner=$this->getOwner();

		$transaction=$owner->getDbConnection()->beginTransaction();

		try
		{
			$condition=$this->left.'>='.$owner->getAttribute($this->left).' AND '.
				$this->right.'<='.$owner->getAttribute($this->right);

			$root=$this->hasManyRoots ? $owner->getAttribute($this->root) : null;

			if($root!==null)
				$condition.=' AND '.$this->root.'='.$root;

			$owner->deleteAll($condition);

			$first=$owner->getAttribute($this->right)+1;
			$delta=$owner->getAttribute($this->left)-$owner->getAttribute($this->right)-1;
			$this->shiftLeftRight($first,$delta,$root);

			$transaction->commit();

			return true;
		}
		catch(Exception $e)
		{
			$transaction->rollBack();

			return false;
		}
	}

	/**
	 * Appends target to node as last child.
	 * @return boolean whether the appending succeeds.
	 * @throws CException if the target node is self.
	 */
	public function append($target,$runValidation=true)
	{
		return $target->appendTo($this->getOwner(),$runValidation);
	}

	/**
	 * Appends node to target as last child.
	 * @return boolean whether the appending succeeds.
	 * @throws CException if the target node is self.
	 */
	public function appendTo($target,$runValidation=true)
	{
		$this->getOwner()->setAttribute($this->level,$target->getAttribute($this->level)+1);
		$key=$target->getAttribute($this->right);
		return $this->addNode($target,$key,$runValidation);
	}

	/**
	 * Prepends target to node as first child.
	 * @return boolean whether the prepending succeeds.
	 * @throws CException if the target node is self.
	 */
	public function prepend($target,$runValidation=true)
	{
		return $target->prependTo($this->getOwner(),$runValidation);
	}

	/**
	 * Prepends node to target as first child.
	 * @return boolean whether the prepending succeeds.
	 * @throws CException if the target node is self.
	 */
	public function prependTo($target,$runValidation=true)
	{
		$this->getOwner()->setAttribute($this->level,$target->getAttribute($this->level)+1);
		$key=$target->getAttribute($this->left)+1;
		return $this->addNode($target,$key,$runValidation);
	}

	/**
	 * Inserts node as previous sibling of target.
	 * @return boolean whether the inserting succeeds.
	 * @throws CException if the target node is self or target node is root.
	 */
	public function insertBefore($target,$runValidation=true)
	{
		if($target->isRoot())
			throw new CException(Yii::t('yiiext','The target node should not be root.'));

		$this->getOwner()->setAttribute($this->level,$target->getAttribute($this->level));
		$key=$target->getAttribute($this->left);
		return $this->addNode($target,$key,$runValidation);
	}

	/**
	 * Inserts node as next sibling of target.
	 * @return boolean whether the inserting succeeds.
	 * @throws CException if the target node is self or target node is root.
	 */
	public function insertAfter($target,$runValidation=true)
	{
		if($target->isRoot())
			throw new CException(Yii::t('yiiext','The target node should not be root.'));

		$this->getOwner()->setAttribute($this->level,$target->getAttribute($this->level));
		$key=$target->getAttribute($this->right)+1;
		return $this->addNode($target,$key,$runValidation);
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

		$key=$target->getAttribute($this->left);
		$levelDiff=$target->getAttribute($this->level)-$this->getOwner()->getAttribute($this->level);
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

		$key=$target->getAttribute($this->right)+1;
		$levelDiff=$target->getAttribute($this->level)-$this->getOwner()->getAttribute($this->level);
		return $this->moveNode($target,$key,$levelDiff);
	}

	/**
	 * Move node as first child of target.
	 * @return boolean whether the moving succeeds.
	 * @throws CException if the target node is self.
	 */
	public function moveAsFirst($target)
	{
		$key=$target->getAttribute($this->left)+1;
		$levelDiff=$target->getAttribute($this->level)-$this->getOwner()->getAttribute($this->level)+1;
		return $this->moveNode($target,$key,$levelDiff);
	}

	/**
	 * Move node as last child of target.
	 * @return boolean whether the moving succeeds.
	 * @throws CException if the target node is self.
	 */
	public function moveAsLast($target)
	{
		$key=$target->getAttribute($this->right);
		$levelDiff=$target->getAttribute($this->level)-$this->getOwner()->getAttribute($this->level)+1;
		return $this->moveNode($target,$key,$levelDiff);
	}

	/**
	 * Determines if node is descendant of subject node.
	 * @return boolean
	 */
	public function isDescentantOf($subj)
	{
		$owner=$this->getOwner();
		$result=($owner->getAttribute($this->left)>$subj->getAttribute($this->left))
			&& ($owner->getAttribute($this->right)<$subj->getAttribute($this->right));

		if($this->hasManyRoots)
			$result=$result && ($owner->getAttribute($this->root)===$subj->getAttribute($this->root));

		return $result;
	}

	/**
	 * Determines if node is leaf.
	 * @return boolean
	 */
	public function isLeaf()
	{
		return $this->getOwner()->getAttribute($this->right)-$this->getOwner()->getAttribute($this->left)===1;
	}

	/**
	 * Determines if node is root.
	 * @return boolean
	 */
	public function isRoot()
	{
		return $this->getOwner()->getAttribute($this->left)==1;
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

	protected function addNode($target,$key,$runValidation)
	{
		$owner=$this->getOwner();

		if($runValidation && !$owner->validate())
			return false;

		if($owner===$target)
			throw new CException(Yii::t('yiiext','The target node should not be self.'));

		$db=$owner->getDbConnection();
		$extTransFlag=$db->getCurrentTransaction();

		if($extTransFlag===null)
			$transaction=$db->beginTransaction();

		try
		{
			$root=$this->hasManyRoots ? $target->getAttribute($this->root) : null;
			$this->shiftLeftRight($key,2,$root);
			$owner->setAttribute($this->left,$key);
			$owner->setAttribute($this->right,$key+1);

			if($root!==null)
				$owner->setAttribute($this->root,$root);

			$owner->save(false);

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

	protected function moveNode($target,$key,$levelDiff)
	{
		$owner=$this->getOwner();

		if($owner===$target)
			throw new CException(Yii::t('yiiext','The target node should not be self.')); //TODO: исправить смысл фразы

		$db=$owner->getDbConnection();
		$extTransFlag=$db->getCurrentTransaction();

		if($extTransFlag===null)
			$transaction=$db->beginTransaction();

		try
		{
			$left=$owner->getAttribute($this->left);
			$right=$owner->getAttribute($this->right);
			$delta=$right-$left+1;
			$root=$this->hasManyRoots ? $owner->getAttribute($this->root) : null;

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
