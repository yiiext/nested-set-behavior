1.06
----

- Changed the name of the methods getParent(), getPrevSibling(), getNextSibling() to parent(), prev(), next(). Also changed the return type of methods. (creocoder)
- Updated readmes. (creocoder)
- Added upgrade. (creocoder)

1.05
----

- External transaction support for NestedSetBehavior::delete() method. (creocoder)

1.04
----

- Fix for $depth parameter of NestedSetBehavior::ancestors() method. (creocoder)

1.03
----

- Class renamed to NestedSetBehavior. (creocoder)
- Rare bug with cache correction after NestedSetBehavior::addNode() fixed. (creocoder)
- Readmes non-recursive tree traversal algorithm fix. (creocoder)

1.02
----

- Added moveAsRoot() method. (creocoder)
- Updated readmes. (Sam Dark)

1.01
----

- Added not integer pk support. (creocoder)
- Remove `final` from class. (creocoder)

1.00
----

- Some behavior refactoring before release. (creocoder)
- Advanced doc added. (creocoder)

0.99b
----

- Allow to use multiply change tree operations. (creocoder)
- Method saveNode() now create root in "single root mode". (creocoder)
- Unit tests refactored. (creocoder)
- Some behavior refactoring. (creocoder)

0.99
----

- Added note about removing fields from `rules`. (Sam Dark)
- Added Unit tests for single root mode. (creocoder)
- All attributes are now correctly quoted. (creocoder)
- Renamed fields: 'root'=>'rootAttribute', 'left'=>'leftAttribute', 'right'=>'rightAttribute', 'level'=>'levelAttribute'. (creocoder)
- Renamed method parent() => getParent(). (creocoder)

0.95
----

- Unit tests added. (creocoder)
- Incorrect usage of save() and delete() instead of behavior's saveNode() and deleteNode() is now detected. (creocoder)

0.90
----

- Moving a node to a different root is now supported. (creocoder)

0.85
----

- Initial public release. (creocoder)