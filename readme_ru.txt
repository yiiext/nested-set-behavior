// хранить ли множество деревьев в одной таблице
public $hasManyRoots=false;

// поле для хранения идентификатора дерева при $hasManyRoots=false; не используется
public $root='root';

// обязательные поля для NS
public $left='lft';
public $right='rgt';
public $level='level';

Пример SQL для таблицы:
~~~
[sql]
CREATE TABLE `category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `root` int(10) unsigned NOT NULL,
  `lft` int(10) unsigned NOT NULL,
  `rgt` int(10) unsigned NOT NULL,
  `level` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`),
  KEY `level` (`level`),  
  KEY `root` (`root`)
) ENGINE=InnoDB
~~~

~~~
[php]
// выбираем корень дерева
$root=Comments::model()->roots()->findByPk($pk);
// получаем все узлы
$comments=$root->descendants()->findAll();
~~~