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
class Comment extends CActiveRecord {
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
значения `left` и `right`. Решением на данный момент является вызов `$model->refresh()`
после обновления дерева для получения актуальных данных.

Краткое описание API
--------------------

### Именовынные группы условий AR

- descendants($depth=null) выбирать потомков
- children() выбирать прямых потомков
- ancestors($depth=null) выбирать предков
- roots() выбирать корни

### Методы поиска

- parent() родитель
- getPrevSibling() предыдущий сосед
- getNextSibling() следующий сосед

### Информационные методы

- isDescendantOf($subj) является ли потомком $subj
- isLeaf() является ли листом (узлом без детей)
- isRoot() является ли корнем

### Методы действий

- saveNode() или tree->save() сохранить узел
- deleteNode() или tree->delete() удалить узел
- insertBefore($target), insertAfter($target) вставить узел до или после $target
- append($target) добавить $target в конец узла
- appendTo($target) добавить узел в конец $target
- prepend($target) добавить $target в начало узла
- prependTo($target) добавить узел в начало $target
- moveBefore($target), moveAfter($target) переместить до или после $target
- moveAsFirst($target), moveAsLast($target) переместить в начало или конец $target
