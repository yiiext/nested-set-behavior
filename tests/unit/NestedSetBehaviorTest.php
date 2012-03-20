<?php
class NestedSetBehaviorTest extends CDbTestCase
{
	public $fixtures=array(
		'NestedSet',
		'NestedSetWithManyRoots',
	);

	public function testDescendants()
	{
		// single root
		$nestedSet=NestedSet::model()->findByPk(1);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$descendants=$nestedSet->descendants()->findAll();
		$this->assertEquals(count($descendants),6);
		foreach($descendants as $descendant)
			$this->assertTrue($descendant instanceof NestedSet);
		$this->assertEquals($descendants[0]->primaryKey,2);
		$this->assertEquals($descendants[1]->primaryKey,3);
		$this->assertEquals($descendants[2]->primaryKey,4);
		$this->assertEquals($descendants[3]->primaryKey,5);
		$this->assertEquals($descendants[4]->primaryKey,6);
		$this->assertEquals($descendants[5]->primaryKey,7);

		// many roots
		$nestedSet=NestedSetWithManyRoots::model()->findByPk(1);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$descendants=$nestedSet->descendants()->findAll();
		$this->assertEquals(count($descendants),6);
		foreach($descendants as $descendant)
			$this->assertTrue($descendant instanceof NestedSetWithManyRoots);
		$this->assertEquals($descendants[0]->primaryKey,2);
		$this->assertEquals($descendants[1]->primaryKey,3);
		$this->assertEquals($descendants[2]->primaryKey,4);
		$this->assertEquals($descendants[3]->primaryKey,5);
		$this->assertEquals($descendants[4]->primaryKey,6);
		$this->assertEquals($descendants[5]->primaryKey,7);
	}

	public function testChildren()
	{
		// single root
		$nestedSet=NestedSet::model()->findByPk(1);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$children=$nestedSet->children()->findAll();
		$this->assertEquals(count($children),2);
		foreach($children as $child)
			$this->assertTrue($child instanceof NestedSet);
		$this->assertEquals($children[0]->primaryKey,2);
		$this->assertEquals($children[1]->primaryKey,5);

		// many roots
		$nestedSet=NestedSetWithManyRoots::model()->findByPk(1);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$children=$nestedSet->children()->findAll();
		$this->assertEquals(count($children),2);
		foreach($children as $child)
			$this->assertTrue($child instanceof NestedSetWithManyRoots);
		$this->assertEquals($children[0]->primaryKey,2);
		$this->assertEquals($children[1]->primaryKey,5);
	}

	public function testAncestors()
	{
		// single root
		$nestedSet=NestedSet::model()->findByPk(7);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$ancestors=$nestedSet->ancestors()->findAll();
		$this->assertEquals(count($ancestors),2);
		foreach($ancestors as $ancestor)
			$this->assertTrue($ancestor instanceof NestedSet);
		$this->assertEquals($ancestors[0]->primaryKey,1);
		$this->assertEquals($ancestors[1]->primaryKey,5);

		// many roots
		$nestedSet=NestedSetWithManyRoots::model()->findByPk(7);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$ancestors=$nestedSet->ancestors()->findAll();
		$this->assertEquals(count($ancestors),2);
		foreach($ancestors as $ancestor)
			$this->assertTrue($ancestor instanceof NestedSetWithManyRoots);
		$this->assertEquals($ancestors[0]->primaryKey,1);
		$this->assertEquals($ancestors[1]->primaryKey,5);
	}

	public function testRoots()
	{
		// single root
		$roots=NestedSet::model()->roots()->findAll();
		$this->assertEquals(count($roots),1);
		foreach($roots as $root)
			$this->assertTrue($root instanceof NestedSet);
		$this->assertEquals($roots[0]->primaryKey,1);

		// many roots
		$roots=NestedSetWithManyRoots::model()->roots()->findAll();
		$this->assertEquals(count($roots),2);
		foreach($roots as $root)
			$this->assertTrue($root instanceof NestedSetWithManyRoots);
		$this->assertEquals($roots[0]->primaryKey,1);
		$this->assertEquals($roots[1]->primaryKey,8);
	}

	public function testParent()
	{
		// single root
		$nestedSet=NestedSet::model()->findByPk(4);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$parent=$nestedSet->parent()->find();
		$this->assertTrue($parent instanceof NestedSet);
		$this->assertEquals($parent->primaryKey,2);

		// many roots
		$nestedSet=NestedSetWithManyRoots::model()->findByPk(4);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$parent=$nestedSet->parent()->find();
		$this->assertTrue($parent instanceof NestedSetWithManyRoots);
		$this->assertEquals($parent->primaryKey,2);
	}

	public function testPrev()
	{
		// single root
		$nestedSet=NestedSet::model()->findByPk(7);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$sibling=$nestedSet->prev()->find();
		$this->assertTrue($sibling instanceof NestedSet);
		$this->assertEquals($sibling->primaryKey,6);
		$sibling=$sibling->prev()->find();
		$this->assertNull($sibling);

		// many roots
		$nestedSet=NestedSetWithManyRoots::model()->findByPk(7);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$sibling=$nestedSet->prev()->find();
		$this->assertTrue($sibling instanceof NestedSetWithManyRoots);
		$this->assertEquals($sibling->primaryKey,6);
		$sibling=$sibling->prev()->find();
		$this->assertNull($sibling);
	}

	public function testNext()
	{
		// single root
		$nestedSet=NestedSet::model()->findByPk(6);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$sibling=$nestedSet->next()->find();
		$this->assertTrue($sibling instanceof NestedSet);
		$this->assertEquals($sibling->primaryKey,7);
		$sibling=$sibling->next()->find();
		$this->assertNull($sibling);

		// many roots
		$nestedSet=NestedSetWithManyRoots::model()->findByPk(6);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$sibling=$nestedSet->next()->find();
		$this->assertTrue($sibling instanceof NestedSetWithManyRoots);
		$this->assertEquals($sibling->primaryKey,7);
		$sibling=$sibling->next()->find();
		$this->assertNull($sibling);
	}

	/**
	* @depends testDescendants
	*/
	public function testIsDescendantOf()
	{
		// single root
		$nestedSet=NestedSet::model()->findByPk(1);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$descendants=$nestedSet->descendants()->findAll();
		foreach($descendants as $descendant)
			$this->assertTrue($descendant->isDescendantOf($nestedSet));
		$descendant=NestedSet::model()->findByPk(4);
		$this->assertTrue($descendant instanceof NestedSet);
		$this->assertFalse($nestedSet->isDescendantOf($descendant));

		// many roots
		$nestedSet=NestedSetWithManyRoots::model()->findByPk(1);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$descendants=$nestedSet->descendants()->findAll();
		foreach($descendants as $descendant)
			$this->assertTrue($descendant->isDescendantOf($nestedSet));
		$descendant=NestedSetWithManyRoots::model()->findByPk(4);
		$this->assertTrue($descendant instanceof NestedSetWithManyRoots);
		$this->assertFalse($nestedSet->isDescendantOf($descendant));
	}

	public function testIsRoot()
	{
		// single root
		$roots=NestedSet::model()->roots()->findAll();
		$this->assertEquals(count($roots),1);
		foreach($roots as $root)
		{
			$this->assertTrue($root instanceof NestedSet);
			$this->assertTrue($root->isRoot());
		}
		$notRoot=NestedSet::model()->findByPk(4);
		$this->assertTrue($notRoot instanceof NestedSet);
		$this->assertFalse($notRoot->isRoot());

		// many roots
		$roots=NestedSetWithManyRoots::model()->roots()->findAll();
		$this->assertEquals(count($roots),2);
		foreach($roots as $root)
		{
			$this->assertTrue($root instanceof NestedSetWithManyRoots);
			$this->assertTrue($root->isRoot());
		}
		$notRoot=NestedSetWithManyRoots::model()->findByPk(4);
		$this->assertTrue($notRoot instanceof NestedSetWithManyRoots);
		$this->assertFalse($notRoot->isRoot());
	}

	public function testIsLeaf()
	{
		// single root
		$nestedSet=NestedSet::model()->findByPk(5);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$this->assertFalse($nestedSet->isLeaf());
		$descendants=$nestedSet->descendants()->findAll();
		$this->assertEquals(count($descendants),2);
		foreach($descendants as $descendant)
		{
			$this->assertTrue($descendant instanceof NestedSet);
			$this->assertTrue($descendant->isLeaf());
		}

		// many roots
		$nestedSet=NestedSetWithManyRoots::model()->findByPk(5);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$this->assertFalse($nestedSet->isLeaf());
		$descendants=$nestedSet->descendants()->findAll();
		$this->assertEquals(count($descendants),2);
		foreach($descendants as $descendant)
		{
			$this->assertTrue($descendant instanceof NestedSetWithManyRoots);
			$this->assertTrue($descendant->isLeaf());
		}
	}

	public function testSaveNode()
	{
		// single root

		// many roots
		$nestedSet=new NestedSetWithManyRoots;
		$this->assertFalse($nestedSet->saveNode());
		$nestedSet->name='test';
		$this->assertTrue($nestedSet->saveNode());
		$this->assertEquals($nestedSet->root,$nestedSet->primaryKey);
		$this->assertEquals($nestedSet->lft,1);
		$this->assertEquals($nestedSet->rgt,2);
		$this->assertEquals($nestedSet->level,1);
	}

	public function testDeleteNode()
	{
		// single root
		$array=NestedSet::model()->findAll();
		$nestedSet=NestedSet::model()->findByPk(4);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$this->assertTrue($nestedSet->deleteNode());
		$this->assertTrue($this->checkTree());
		$this->assertTrue($nestedSet->getIsDeletedRecord());
		$this->assertTrue($this->checkArray($array));
		$nestedSet=NestedSet::model()->findByPk(5);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$this->assertTrue($nestedSet->deleteNode());
		$this->assertTrue($this->checkTree());
		$this->assertTrue($nestedSet->getIsDeletedRecord());
		$this->assertTrue($this->checkArray($array));
		foreach($array as $item)
		{
			if(in_array($item->primaryKey,array(4,5,6,7)))
				$this->assertTrue($item->getIsDeletedRecord());
			else
				$this->assertFalse($item->getIsDeletedRecord());
		}

		// many roots
		$array=NestedSetWithManyRoots::model()->findAll();
		$nestedSet=NestedSetWithManyRoots::model()->findByPk(4);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->deleteNode());
		$this->assertTrue($this->checkTreeWithManyRoots());
		$this->assertTrue($nestedSet->getIsDeletedRecord());
		$this->assertTrue($this->checkArrayWithManyRoots($array));
		$nestedSet=NestedSetWithManyRoots::model()->findByPk(9);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->deleteNode());
		$this->assertTrue($this->checkTreeWithManyRoots());
		$this->assertTrue($nestedSet->getIsDeletedRecord());
		$this->assertTrue($this->checkArrayWithManyRoots($array));
		foreach($array as $item)
		{
			if(in_array($item->primaryKey,array(4,9,10,11)))
				$this->assertTrue($item->getIsDeletedRecord());
			else
				$this->assertFalse($item->getIsDeletedRecord());
		}
	}

	public function testPrependTo()
	{
		// single root
		$array=NestedSet::model()->findAll();
		$target=NestedSet::model()->findByPk(5);
		$this->assertTrue($target instanceof NestedSet);
		$nestedSet1=new NestedSet;
		$this->assertFalse($nestedSet1->prependTo($target));
		$nestedSet1->name='test';
		$this->assertTrue($nestedSet1->prependTo($target));
		$this->assertTrue($this->checkTree());
		$array[]=$nestedSet1;
		$nestedSet2=new NestedSet;
		$nestedSet2->name='test';
		$this->assertTrue($nestedSet2->prependTo($target));
		$this->assertTrue($this->checkTree());
		$array[]=$nestedSet2;
		$this->assertTrue($this->checkArray($array));

		// many roots
		$array=NestedSetWithManyRoots::model()->findAll();
		$target=NestedSetWithManyRoots::model()->findByPk(5);
		$this->assertTrue($target instanceof NestedSetWithManyRoots);
		$nestedSet1=new NestedSetWithManyRoots;
		$this->assertFalse($nestedSet1->prependTo($target));
		$nestedSet1->name='test';
		$this->assertTrue($nestedSet1->prependTo($target));
		$this->assertTrue($this->checkTreeWithManyRoots());
		$array[]=$nestedSet1;
		$nestedSet2=new NestedSetWithManyRoots;
		$nestedSet2->name='test';
		$this->assertTrue($nestedSet2->prependTo($target));
		$this->assertTrue($this->checkTreeWithManyRoots());
		$array[]=$nestedSet2;
		$this->assertTrue($this->checkArrayWithManyRoots($array));
	}

	public function testAppendTo()
	{
		// single root
		$array=NestedSet::model()->findAll();
		$target=NestedSet::model()->findByPk(2);
		$this->assertTrue($target instanceof NestedSet);
		$nestedSet1=new NestedSet;
		$this->assertFalse($nestedSet1->appendTo($target));
		$nestedSet1->name='test';
		$this->assertTrue($nestedSet1->appendTo($target));
		$this->assertTrue($this->checkTree());
		$array[]=$nestedSet1;
		$nestedSet2=new NestedSet;
		$nestedSet2->name='test';
		$this->assertTrue($nestedSet2->appendTo($target));
		$this->assertTrue($this->checkTree());
		$array[]=$nestedSet2;
		$this->assertTrue($this->checkArray($array));

		// many roots
		$array=NestedSetWithManyRoots::model()->findAll();
		$target=NestedSetWithManyRoots::model()->findByPk(2);
		$this->assertTrue($target instanceof NestedSetWithManyRoots);
		$nestedSet1=new NestedSetWithManyRoots;
		$this->assertFalse($nestedSet1->appendTo($target));
		$nestedSet1->name='test';
		$this->assertTrue($nestedSet1->appendTo($target));
		$this->assertTrue($this->checkTreeWithManyRoots());
		$array[]=$nestedSet1;
		$nestedSet2=new NestedSetWithManyRoots;
		$nestedSet2->name='test';
		$this->assertTrue($nestedSet2->appendTo($target));
		$this->assertTrue($this->checkTreeWithManyRoots());
		$array[]=$nestedSet2;
		$this->assertTrue($this->checkArrayWithManyRoots($array));
	}

	public function testInsertBefore()
	{
		// single root
		$array=NestedSet::model()->findAll();
		$target=NestedSet::model()->findByPk(5);
		$this->assertTrue($target instanceof NestedSet);
		$nestedSet1=new NestedSet;
		$this->assertFalse($nestedSet1->insertBefore($target));
		$nestedSet1->name='test';
		$this->assertTrue($nestedSet1->insertBefore($target));
		$this->assertTrue($this->checkTree());
		$array[]=$nestedSet1;
		$nestedSet2=new NestedSet;
		$nestedSet2->name='test';
		$this->assertTrue($nestedSet2->insertBefore($target));
		$this->assertTrue($this->checkTree());
		$array[]=$nestedSet2;
		$this->assertTrue($this->checkArray($array));

		// many roots
		$array=NestedSetWithManyRoots::model()->findAll();
		$target=NestedSetWithManyRoots::model()->findByPk(5);
		$this->assertTrue($target instanceof NestedSetWithManyRoots);
		$nestedSet1=new NestedSetWithManyRoots;
		$this->assertFalse($nestedSet1->insertBefore($target));
		$nestedSet1->name='test';
		$this->assertTrue($nestedSet1->insertBefore($target));
		$this->assertTrue($this->checkTreeWithManyRoots());
		$array[]=$nestedSet1;
		$nestedSet2=new NestedSetWithManyRoots;
		$nestedSet2->name='test';
		$this->assertTrue($nestedSet2->insertBefore($target));
		$this->assertTrue($this->checkTreeWithManyRoots());
		$array[]=$nestedSet2;
		$this->assertTrue($this->checkArrayWithManyRoots($array));
	}

	public function testInsertAfter()
	{
		// single root
		$array=NestedSet::model()->findAll();
		$target=NestedSet::model()->findByPk(2);
		$this->assertTrue($target instanceof NestedSet);
		$nestedSet1=new NestedSet;
		$this->assertFalse($nestedSet1->insertAfter($target));
		$nestedSet1->name='test';
		$this->assertTrue($nestedSet1->insertAfter($target));
		$this->assertTrue($this->checkTree());
		$array[]=$nestedSet1;
		$nestedSet2=new NestedSet;
		$nestedSet2->name='test';
		$this->assertTrue($nestedSet2->insertAfter($target));
		$this->assertTrue($this->checkTree());
		$array[]=$nestedSet2;
		$this->assertTrue($this->checkArray($array));

		// many roots
		$array=NestedSetWithManyRoots::model()->findAll();
		$target=NestedSetWithManyRoots::model()->findByPk(2);
		$this->assertTrue($target instanceof NestedSetWithManyRoots);
		$nestedSet1=new NestedSetWithManyRoots;
		$this->assertFalse($nestedSet1->insertAfter($target));
		$nestedSet1->name='test';
		$this->assertTrue($nestedSet1->insertAfter($target));
		$this->assertTrue($this->checkTreeWithManyRoots());
		$array[]=$nestedSet1;
		$nestedSet2=new NestedSetWithManyRoots;
		$nestedSet2->name='test';
		$this->assertTrue($nestedSet2->insertAfter($target));
		$this->assertTrue($this->checkTreeWithManyRoots());
		$array[]=$nestedSet2;
		$this->assertTrue($this->checkArrayWithManyRoots($array));
	}

	public function testMoveBefore()
	{
		// single root
		$array=NestedSet::model()->findAll();

		$nestedSet=NestedSet::model()->findByPk(6);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$target=NestedSet::model()->findByPk(2);
		$this->assertTrue($target instanceof NestedSet);
		$this->assertTrue($nestedSet->moveBefore($target));
		$this->assertTrue($this->checkTree());

		$this->assertTrue($this->checkArray($array));

		$nestedSet=NestedSet::model()->findByPk(5);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$this->assertTrue($nestedSet->moveBefore($target));
		$this->assertTrue($this->checkTree());

		$this->assertTrue($this->checkArray($array));

		// many roots
		$array=NestedSetWithManyRoots::model()->findAll();

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(6);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$target=NestedSetWithManyRoots::model()->findByPk(2);
		$this->assertTrue($target instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveBefore($target));
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(5);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveBefore($target));
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(6);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$target=NestedSetWithManyRoots::model()->findByPk(9);
		$this->assertTrue($target instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveBefore($target));
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(5);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveBefore($target));
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));
	}

	public function testMoveAfter()
	{
		// single root
		$array=NestedSet::model()->findAll();

		$nestedSet=NestedSet::model()->findByPk(3);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$target=NestedSet::model()->findByPk(5);
		$this->assertTrue($target instanceof NestedSet);
		$this->assertTrue($nestedSet->moveAfter($target));
		$this->assertTrue($this->checkTree());

		$this->assertTrue($this->checkArray($array));

		$nestedSet=NestedSet::model()->findByPk(2);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$this->assertTrue($nestedSet->moveAfter($target));
		$this->assertTrue($this->checkTree());

		$this->assertTrue($this->checkArray($array));

		// many roots
		$array=NestedSetWithManyRoots::model()->findAll();

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(3);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$target=NestedSetWithManyRoots::model()->findByPk(5);
		$this->assertTrue($target instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveAfter($target));
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(2);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveAfter($target));
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(3);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$target=NestedSetWithManyRoots::model()->findByPk(12);
		$this->assertTrue($target instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveAfter($target));
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(2);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveAfter($target));
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));
	}

	public function testMoveAsFirst()
	{
		// single root
		$array=NestedSet::model()->findAll();

		$nestedSet=NestedSet::model()->findByPk(6);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$target=NestedSet::model()->findByPk(2);
		$this->assertTrue($target instanceof NestedSet);
		$this->assertTrue($nestedSet->moveAsFirst($target));
		$this->assertTrue($this->checkTree());

		$this->assertTrue($this->checkArray($array));

		$nestedSet=NestedSet::model()->findByPk(5);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$this->assertTrue($nestedSet->moveAsFirst($target));
		$this->assertTrue($this->checkTree());

		$this->assertTrue($this->checkArray($array));

		// many roots
		$array=NestedSetWithManyRoots::model()->findAll();

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(6);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$target=NestedSetWithManyRoots::model()->findByPk(2);
		$this->assertTrue($target instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveAsFirst($target));
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(5);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveAsFirst($target));
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(6);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$target=NestedSetWithManyRoots::model()->findByPk(9);
		$this->assertTrue($target instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveAsFirst($target));
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(5);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveAsFirst($target));
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));
	}

	public function testMoveAsLast()
	{
		// single root
		$array=NestedSet::model()->findAll();

		$nestedSet=NestedSet::model()->findByPk(3);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$target=NestedSet::model()->findByPk(5);
		$this->assertTrue($target instanceof NestedSet);
		$this->assertTrue($nestedSet->moveAsLast($target));
		$this->assertTrue($this->checkTree());

		$this->assertTrue($this->checkArray($array));

		$nestedSet=NestedSet::model()->findByPk(2);
		$this->assertTrue($nestedSet instanceof NestedSet);
		$this->assertTrue($nestedSet->moveAsLast($target));
		$this->assertTrue($this->checkTree());

		$this->assertTrue($this->checkArray($array));

		// many roots
		$array=NestedSetWithManyRoots::model()->findAll();

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(3);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$target=NestedSetWithManyRoots::model()->findByPk(5);
		$this->assertTrue($target instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveAsLast($target));
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(2);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveAsLast($target));
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(3);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$target=NestedSetWithManyRoots::model()->findByPk(12);
		$this->assertTrue($target instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveAsLast($target));
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(2);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveAsLast($target));
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));
	}

	public function testMoveAsRoot()
	{
		$array=NestedSetWithManyRoots::model()->findAll();

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(2);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveAsRoot());
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));

		$nestedSet=NestedSetWithManyRoots::model()->findByPk(10);
		$this->assertTrue($nestedSet instanceof NestedSetWithManyRoots);
		$this->assertTrue($nestedSet->moveAsRoot());
		$this->assertTrue($this->checkTreeWithManyRoots());

		$this->assertTrue($this->checkArrayWithManyRoots($array));
	}

	private function checkTree()
	{
		return $this->checkTree1()
			&& $this->checkTree2()
			&& $this->checkTree3()
			&& $this->checkTree4();
	}

	private function checkTree1()
	{
		return !Yii::app()->db->createCommand('SELECT COUNT(`id`) FROM `NestedSet` WHERE `lft`>=`rgt`;')->queryScalar();
	}

	private function checkTree2()
	{
		return !Yii::app()->db->createCommand('SELECT COUNT(`id`) FROM `NestedSet` WHERE NOT MOD(`rgt`-`lft`,2);')->queryScalar();
	}

	private function checkTree3()
	{
		return !Yii::app()->db->createCommand('SELECT COUNT(`id`) FROM `NestedSet` WHERE MOD(`lft`-`level`,2);')->queryScalar();
	}

	private function checkTree4()
	{
		$row=Yii::app()->db->createCommand('SELECT MIN(`lft`),MAX(`rgt`),COUNT(`id`) FROM `NestedSet`;')->queryRow(false);

		if($row[0]!=1 || $row[1]!=$row[2]*2)
			return false;

		return true;
	}

	private function checkArray($array)
	{
		return $this->checkArray1($array)
			&& $this->checkArray2($array)
			&& $this->checkArray3($array)
			&& $this->checkArray4($array);
	}

	private function checkArray1($array)
	{
		foreach($array as $node)
		{
			if(!$node->getIsDeletedRecord() && $node->lft>=$node->rgt)
				return false;
		}

		return true;
	}

	private function checkArray2($array)
	{
		foreach($array as $node)
		{
			if(!$node->getIsDeletedRecord() && !(($node->rgt-$node->lft)%2))
				return false;
		}

		return true;
	}

	private function checkArray3($array)
	{
		foreach($array as $node)
		{
			if(!$node->getIsDeletedRecord() && ($node->lft-$node->level)%2)
				return false;
		}

		return true;
	}

	private function checkArray4($array)
	{
		$count=0;

		foreach($array as $node)
		{
			if($node->getIsDeletedRecord())
				continue;
			else
				$count++;

			if(!isset($min) || $min>$node->lft)
				$min=$node->lft;

			if(!isset($max) || $max<$node->rgt)
				$max=$node->rgt;
		}

		if(!$count)
			return true;

		if($min!=1 || $max!=$count*2)
			return false;

		return true;
	}

	private function checkTreeWithManyRoots()
	{
		return $this->checkTreeWithManyRoots1()
			&& $this->checkTreeWithManyRoots2()
			&& $this->checkTreeWithManyRoots3()
			&& $this->checkTreeWithManyRoots4();
	}

	private function checkTreeWithManyRoots1()
	{
		return !Yii::app()->db->createCommand('SELECT COUNT(`id`) FROM `NestedSetWithManyRoots` WHERE `lft`>=`rgt` GROUP BY `root`;')->query()->getRowCount();
	}

	private function checkTreeWithManyRoots2()
	{
		return !Yii::app()->db->createCommand('SELECT COUNT(`id`) FROM `NestedSetWithManyRoots` WHERE NOT MOD(`rgt`-`lft`,2) GROUP BY `root`;')->query()->getRowCount();
	}

	private function checkTreeWithManyRoots3()
	{
		return !Yii::app()->db->createCommand('SELECT COUNT(`id`) FROM `NestedSetWithManyRoots` WHERE MOD(`lft`-`level`,2) GROUP BY `root`;')->query()->getRowCount();
	}

	private function checkTreeWithManyRoots4()
	{
		$rows=Yii::app()->db->createCommand('SELECT MIN(`lft`),MAX(`rgt`),COUNT(`id`) FROM `NestedSetWithManyRoots` GROUP BY `root`;')->queryAll(false);

		foreach($rows as $row)
		{
			if($row[0]!=1 || $row[1]!=$row[2]*2)
				return false;
		}

		return true;
	}

	private function checkArrayWithManyRoots($array)
	{
		return $this->checkArrayWithManyRoots1($array)
			&& $this->checkArrayWithManyRoots2($array)
			&& $this->checkArrayWithManyRoots3($array)
			&& $this->checkArrayWithManyRoots4($array);
	}

	private function checkArrayWithManyRoots1($array)
	{
		foreach($array as $node)
		{
			if(!$node->getIsDeletedRecord() && $node->lft>=$node->rgt)
				return false;
		}

		return true;
	}

	private function checkArrayWithManyRoots2($array)
	{
		foreach($array as $node)
		{
			if(!$node->getIsDeletedRecord() && !(($node->rgt-$node->lft)%2))
				return false;
		}

		return true;
	}

	private function checkArrayWithManyRoots3($array)
	{
		foreach($array as $node)
		{
			if(!$node->getIsDeletedRecord() && ($node->lft-$node->level)%2)
				return false;
		}

		return true;
	}

	private function checkArrayWithManyRoots4($array)
	{
		$min=array();
		$max=array();
		$count=array();

		foreach($array as $n=>$node)
		{
			if($node->getIsDeletedRecord())
				continue;
			else if(isset($count[$node->root]))
				$count[$node->root]++;
			else
				$count[$node->root]=1;

			if(!isset($min[$node->root]) || $min[$node->root]>$node->lft)
				$min[$node->root]=$node->lft;

			if(!isset($max[$node->root]) || $max[$node->root]<$node->rgt)
				$max[$node->root]=$node->rgt;
		}

		foreach($count as $root=>$c)
		{
			if($min[$root]!=1 || $max[$root]!=$c*2)
				return false;
		}

		return true;
	}
}