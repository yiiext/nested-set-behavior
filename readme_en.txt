Nested Set
==========

AR models behavior that allows to work with nested sets tree.

Installing and configuring
--------------------------

Create DB structure (see schema.sql or schema_with_many_roots.sql).

Configure your model:

~~~
[php]
class Comment extends CActiveRecord {
    public function behaviors(){
        return array(
            'tree' => array(
                'class' => 'ext.yiiext.behaviors.model.trees.ENestedSetBehavior',
                // store multiple trees in one table
                'hasManyRoots' => false,
                // where to store each tree id. Not used when $hasManyRoots is false
				'rootAttribute' => 'root',
				// required fields
				'leftAttribute' => 'lft',
				'rightAttribute' => 'rgt',
				'levelAttribute' => 'level',
            ),
        );
    }
}
~~~

Remove validation rules for `root`, `lft`, `rgt` and `level` from `rules` method.

Usage
-----

~~~
[php]
// getting a root
$root=Comment::model()->roots()->findByPk($pk);
// getting a tree for this root
$comments=$root->descendants()->findAll();
~~~

Limitations
-----------
There is no way right now to make multiple changes in one request because we
need to rebuild the whole tree changing `left` and `right`. If you need to do multiple
operations, use `$model->refresh()` to get fresh data.

API
---

### AR named scopes

- descendants($depth=null) get descendants
- children() get direct descendants
- ancestors($depth=null) get ancestors
- roots() get roots

### Finders

- getParent() parent
- getPrevSibling() previous sibling
- getNextSibling() next sibling

### Informational methods

- isDescendantOf($subj) is descendant of $subj?
- isLeaf() if leaf (is node without children)?
- isRoot() is root?

### Action methods

- saveNode() or tree->save() save node
- deleteNode() or tree->delete() delete node
- insertBefore($target), insertAfter($target) insert node before/after $target
- append($target) append $target to the node
- appendTo($target) append node to the $target
- prepend($target) prepend $target to the node
- prependTo($target) prepend node to the $target
- moveBefore($target), moveAfter($target) move node to before/after $target
- moveAsFirst($target), moveAsLast($target) move node to the beginning/end of $target
- moveAsRoot() move node as new root
