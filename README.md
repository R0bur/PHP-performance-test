# PHP-performance-test
Author: Ihar Areshchankau, 2015.
Mailto: r0bur@tut.by
PHP 5.3 class for testing the performance of the PHP language constructions.

How to use:
1) Create empty document, for example test.php
2) Include the presented PHP-class:

require_once "iaperform.php";

and derive your test class from the class IAPerformanceTest:

<?php
class Test extends IAPerformanceTest
{
...
}
?>

3) Write in your class protected methods for initialization and testing language construction. The initialization method name must start from 'Prep' and follow with the sequential number of the experiment. The name of the the testing method must start from 'Test' and follow the same number. For example, testing of the string concatenations might look like this:

protected function Prep1 ($v)
{
	global $a;
	$a = '';
}

protected function Test1 ($v)
{
	global $a;
	for ($i = 0; $i < $v; ++$i)
		$a .= 'X';
}

4) Write outside the class commands for creating the class instance and execute the performance testing:

$test = new Test ();
$test->Go (100, 300, 100);

where the first parameter is minimal value of the parameter $v of testing function, the second parameter is maximal value, and the third parameter is step of $v incrementation.
5) Run the testing from command-line:

php test.php

and wait for results.
