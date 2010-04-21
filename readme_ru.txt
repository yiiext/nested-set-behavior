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
$root=Comments::model()->roots()->findByPk($pk);
// получаем все узлы
$comments=$root->descendants()->findAll();
~~~