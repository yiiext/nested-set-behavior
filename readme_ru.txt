Nested Set
==========

Поведение для моделей AR, позволяющее работать с деревом, хранящимся в виде
вложенных множеств.

Установка и настройка
---------------------

Создать необходимые поля в таблице БД (см. schema.sql).

Сконфигурировать модель:
~~~
[php]
class Comment extends CActiveRecord{
    public function behaviors(){
        return array(
            'tree' => array(
                'class' => 'ext.yiiext.behaviors.model.trees.ENestedSetBehavior',
                // хранить ли множество деревьев в одной таблице
                'hasManyRoots' => false,
                // поле для хранения идентификатора дерева при $hasManyRoots=false; не используется
				'root' => 'root',
				// обязательные поля для NS
				'left' => 'lft',
				'right' => 'rgt',
				'level' => 'level',
            ),
        );
    }
}
~~~

Использование
-------------

[php]
// выбираем корень дерева
$root=Comment::model()->roots()->findByPk($pk);
// получаем все узлы
$comments=$root->descendants()->findAll();
~~~

Ограничения
-----------
Поведение пока не умеет делать несколько изменяющих дерево операций за один запрос.
Это связано с тем, что после какого-либо изменения дерево перестраивается и меняются
значения left и right. Решением на данный момент является вызов $model->refresh();
после обновления дерева для получения актуальных данных.

API
---
API пока ещё не закончен. Может меняться.

### Named Scopes
descendants($depth=null) потомки
children() прямые потомки
ancestors($depth=null) предки
roots() корни

### Finders
parent() Gets record of node parent. Returns CActiveRecord the record found. Null if no record is found.
getPrevSibling() Gets record of previous sibling.
getNextSibling()

### Info
isDescendantOf($subj)
isLeaf()
isRoot()

### Actions
saveNode() | tree->save()
deleteNode() | tree->delete()
insertBefore($target,$runValidation=true), insertAfter($target,$runValidation=true)
append($target,$runValidation=true), appendTo($target,$runValidation=true)
prepend($target,$runValidation=true), prependTo($target,$runValidation=true)
moveBefore($target), moveAfter($target)
moveAsFirst($target), moveAsLast($target)
