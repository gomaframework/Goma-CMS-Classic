<?php namespace blub;
use Goma\Blub;
use Goma\i5;

defined("IN_GOMA") OR die();

class test extends Blub {

}interface i1{

}class test3{}interface i3 {

}interface i4 {

}

/**
 * Describe your class
 *
 * @package Goma
 *
 * @author D
 * @copyright 2016 D
 *
 * @version 1.0
 */
class myClass
    extends test
    implements i1{

}class myClass2 extends test implements i1,i3,
i4, i5{

}
