Nested Set
==========

Nested Set behavior для Yii 2: https://github.com/creocoder/yii2-nested-sets

Этот компонент предназначен для работы с деревьями, которые хранятся в виде
вложенных множеств. Реализация компонента выполнена в виде поведения для
Active Record моделей.

Установка и настройка
---------------------

Для начала работы необходимо сконфигурировать модель следующим образом:

~~~php
public function behaviors()
{
    return array(
        'nestedSetBehavior'=>array(
            'class'=>'ext.yiiext.behaviors.model.trees.NestedSetBehavior',
            'leftAttribute'=>'lft',
            'rightAttribute'=>'rgt',
            'levelAttribute'=>'level',
        ),
    );
}
~~~

Необходимости в валидации полей, которые определяются опциями `leftAttribute`,
`rightAttribute`, `rootAttribute` и `levelAttribute` нет. Кроме того, могут
возникнуть проблемы с некоторыми методами поведения, если для данных полей
имеются правила валидации. Проверьте их отсутствие в методе rules() модели.

Структура базы данных может быть аналогична
`extensions/yiiext/behaviors/trees/schema.sql` в случае если в таблице планируется
хранение только одного дерева. Если в таблице необходимо хранить множество деревьев,
то подойдет схема `extensions/yiiext/behaviors/trees/schema_many_roots.sql`.

Значения опций `leftAttribute`, `rightAttribute` и `levelAttribute` по умолчанию
совпадают с названием полей в вышеприведенных схемах, поэтому при конфигурации
поведения их можно опустить.

У поведения существует два режима работы: одно дерево и много деревьев.
Режим работы управляется опцией `hasManyRoots`, которая по умолчанию имеет
значение `false`. В режиме работы «много деревьев» возможно использование
ещё одной опции `rootAttribute`, значение которой по умолчанию также совпадает
с названием поля в соответствующей схеме.

Описание работы методов выборки
-------------------------------

В дальнейшем все особенности работы методов будут рассмотрены в контексте
конкретного дерева. Допустим у нас есть модель `Category`, а в базе данных
хранится следующая структура:

~~~
- 1. Mobile phones
	- 2. iPhone
	- 3. Samsung
		- 4. X100
		- 5. C200
	- 6. Motorola
- 7. Cars
	- 8. Audi
	- 9. Ford
	- 10. Mercedes
~~~

В этом примере в таблице хранится два дерева, корнями которых являются
соответственно узлы с ID=1 и ID=7.

### Выборка всех корней

Используем метод `NestedSetBehavior::roots()`:

~~~php
$roots=Category::model()->roots()->findAll();
~~~

Результат:

Массив объектов Active Record, которые характеризуют узлы Mobile phones и Cars.

### Выборка всех потомков узла

Используем метод `NestedSetBehavior::descendants()`:

~~~php
$category=Category::model()->findByPk(1);
$descendants=$category->descendants()->findAll();
~~~

Результат:

Массив объектов Active Record, которые характеризуют узлы iPhone, Samsung, X100, C200 и Motorola.

### Выборка прямых потомков узла

Используем метод `NestedSetBehavior::children()`:

~~~php
$category=Category::model()->findByPk(1);
$descendants=$category->children()->findAll();
~~~

Результат:

Массив объектов Active Record, которые характеризуют узлы iPhone, Samsung и Motorola.

### Выборка всех предков узла

Используем метод `NestedSetBehavior::ancestors()`:

~~~php
$category=Category::model()->findByPk(5);
$ancestors=$category->ancestors()->findAll();
~~~

Результат:

Массив объектов Active Record, которые характеризуют узлы Samsung и Mobile phones.

### Выборка предка узла

Используем метод `NestedSetBehavior::parent()`:

~~~php
$category=Category::model()->findByPk(9);
$parent=$category->parent()->find();
~~~

Результат:

Объект Active Record, который характеризует узел Cars.

### Выборка соседей узла

Используем методы `NestedSetBehavior::prev()` или
`NestedSetBehavior::next()`:

~~~php
$category=Category::model()->findByPk(9);
$nextSibling=$category->next()->find();
~~~

Результат:

Объект Active Record, который характеризует узел Mercedes.

### Выборка дерева целиком

Это может быть осуществлено при помощи стандартных методов Active Record.

Для режима «одно дерево»:
~~~php
Category::model()->findAll(array('order'=>'lft'));
~~~

Для режима «много деревьев»:
~~~php
Category::model()->findAll(array('condition'=>'root=?','order'=>'lft'),array($root));
~~~

Описание работы методов создания узлов
--------------------------------------

В этом разделе мы построим дерево похожее на то, которое было приведено в предыдущем разделе.

### Создание корневых узлов

Создание корня может быть осуществлено при помощи метода NestedSetBehavior::saveNode().
В режиме работы «одно дерево» может быть создан только один корень, в противном
случае вы получите CException.

~~~php
$root=new Category;
$root->title='Mobile Phones';
$root->saveNode();
$root=new Category;
$root->title='Cars';
$root->saveNode();
~~~

Результат в виде дерева:

~~~
- 1. Mobile Phones
- 2. Cars
~~~

### Добавление дочерних узлов

Для добавление дочерних узлов поведение содержит много методов, использование
которых будет показано на примерах. Более подробно об этих методах можно прочитать в API.

Продолжим работать с деревом, полученным в предыдущем разделе:
~~~php
$category1=new Category;
$category1->title='Ford';
$category2=new Category;
$category2->title='Mercedes';
$category3=new Category;
$category3->title='Audi';
$root=Category::model()->findByPk(1);
$category1->appendTo($root);
$category2->insertAfter($category1);
$category3->insertBefore($category1);
~~~

Результат в виде дерева:

~~~
- 1. Mobile phones
	- 3. Audi
	- 4. Ford
	- 5. Mercedes
- 2. Cars
~~~

Можно заметить, что это некорректно с точки зрения логики, но в следующих разделах мы это исправим.

Продолжаем:
~~~php
$category1=new Category;
$category1->title='Samsung';
$category2=new Category;
$category2->title='Motorola';
$category3=new Category;
$category3->title='iPhone';
$root=Category::model()->findByPk(2);
$category1->appendTo($root);
$category2->insertAfter($category1);
$category3->prependTo($root);
~~~

Результат в виде дерева:

~~~
- 1. Mobile phones
	- 3. Audi
	- 4. Ford
	- 5. Mercedes
- 2. Cars
	- 6. iPhone
	- 7. Samsung
	- 8. Motorola
~~~

И снова, не обращаем внимание на нелогичность дерева.

Продолжаем:
~~~php
$category1=new Category;
$category1->title='X100';
$category2=new Category;
$category2->title='C200';
$node=Category::model()->findByPk(3);
$category1->appendTo($node);
$category2->prependTo($node);
~~~

Результат в виде дерева:

~~~
- 1. Mobile phones
	- 3. Audi
		- 9. С200
		- 10. X100
	- 4. Ford
	- 5. Mercedes
- 2. Cars
	- 6. iPhone
	- 7. Samsung
	- 8. Motorola
~~~

Методы модифицирующие дерево
----------------------------

В этом разделе мы окончательно преобразуем дерево к должному виду.

### Методы перемещения узлов

Этих методов также довольно много, поэтому использование будет показано на
примерах, а более подробно обо всех можно узнать в API.

Начнем модификацию дерева:
~~~php
// сначала переместим модели телефонов на место
$x100=Category::model()->findByPk(10);
$c200=Category::model()->findByPk(9);
$samsung=Category::model()->findByPk(7);
$x100->moveAsFirst($samsung);
$c200->moveBefore($x100);
// теперь переместим всю ветку с телефонами Samsung
$mobile_phones=Category::model()->findByPk(1);
$samsung->moveAsFirst($mobile_phones);
// переместим остальные модели телефонов
$iphone=Category::model()->findByPk(6);
$iphone->moveAsFirst($mobile_phones);
$motorola=Category::model()->findByPk(8);
$motorola->moveAfter($samsung);
// переместим модели машин на место
$cars=Category::model()->findByPk(2);
$audi=Category::model()->findByPk(3);
$ford=Category::model()->findByPk(4);
$mercedes=Category::model()->findByPk(5);

foreach(array($audi,$ford,$mercedes) as $category)
    $category->moveAsLast($cars);
~~~

Результат в виде дерева:

~~~
- 1. Mobile phones
	- 6. iPhone
	- 7. Samsung
		- 10. X100
		- 9. С200
	- 8. Motorola
- 2. Cars
	- 3. Audi
	- 4. Ford
	- 5. Mercedes
~~~

### Перемещение узла в качестве нового корня

Для этого в поведении присутствует метод `moveAsRoot()`, который преобразует узел
в новый корень, а все его дочерние узлы становятся потомками нового корня.

Пример использования:
~~~php
$node=Category::model()->findByPk(10);
$node->moveAsRoot();
~~~

### Идентификация узлов дерева

Для этого в поведении присутствуют методы `isRoot()`, `isLeaf()`, `isDescendantOf()`.

Пример использования:
~~~php
$root=Category::model()->findByPk(1);
CVarDumper::dump($root->isRoot()); //true;
CVarDumper::dump($root->isLeaf()); //false;
$node=Category::model()->findByPk(9);
CVarDumper::dump($node->isDescendantOf($root)); //true;
CVarDumper::dump($node->isRoot()); //false;
CVarDumper::dump($node->isLeaf()); //true;
$samsung=Category::model()->findByPk(7);
CVarDumper::dump($node->isDescendantOf($samsung)); //true;
~~~

Полезный код
------------

### Обход дерева без рекурсии

~~~php
$criteria=new CDbCriteria;
$criteria->order='t.lft'; // или 't.root, t.lft' для множественных деревьев
$categories=Category::model()->findAll($criteria);
$level=0;

foreach($categories as $n=>$category)
{
	if($category->level==$level)
		echo CHtml::closeTag('li')."\n";
	else if($category->level>$level)
		echo CHtml::openTag('ul')."\n";
	else
	{
		echo CHtml::closeTag('li')."\n";

		for($i=$level-$category->level;$i;$i--)
		{
			echo CHtml::closeTag('ul')."\n";
			echo CHtml::closeTag('li')."\n";
		}
	}

	echo CHtml::openTag('li');
	echo CHtml::encode($category->title);
	$level=$category->level;
}

for($i=$level;$i;$i--)
{
	echo CHtml::closeTag('li')."\n";
	echo CHtml::closeTag('ul')."\n";
}
~~~
