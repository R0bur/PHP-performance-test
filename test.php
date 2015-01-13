<?php
require_once "iaperform.php";
class Test extends IAPerformanceTest
{
	protected function Prep1 ($v)
	{
		global $a;
		$a = '';
	}
	protected function Test1 ($v)
	{
		global $a;
		for ($i = 0; $i < $v; ++$i):
			$a .= 'X';
		endfor;
	}
	protected function Prep2 ($v)
	{
		global $a;
		$a = str_repeat (' ', $v);
	}
	protected function Test2 ($v)
	{
		global $a;
		for ($i = 0; $i < $v; ++$i):
			$a[$i] = 'X';
		endfor;
	}

}

/*========================*/
/* Тестирующая программа. */
/*========================*/
$test = new Test ();
$test->Go (100, 300, 100);
?>
