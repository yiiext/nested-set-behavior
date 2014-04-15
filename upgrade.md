Upgrading instructions for NestedSetBehavior v1.0.6
===================================================

!!!IMPORTANT!!!

The following upgrading instructions are cumulative. That is,
if you want to upgrade from version A to version C and there is
version B between A and C, you need to following the instructions
for both A and B.

Upgrading from v1.0.5
---------------------

- You need to change following code:

~~~
$parent=$node->getParent();
$prevSibling=$node->getPrevSibling();
$nextSibling=$node->getNextSibling();
~~~

to

~~~
$parent=$node->parent()->find();
$prevSibling=$node->prev()->find();
$nextSibling=$node->next()->find();
~~~
