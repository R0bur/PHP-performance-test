<?php
/*==============================================================*/
/* Класс тестирования производительности конструкций языка PHP. */
/* Автор: Игорь Орещенков, 2015 г.                              */
/* Контактная информация: r0bur@tut.by                          */
/*==============================================================*/
class IAPerformanceTest {
	private $testmeth;	// массив названий тестируемых методов
	private $prepmeth;	// массив названий подготавливающих методов
	/*================================*/
	/* Пустой подготавливающий метод. */
	/* Вызов: $v - параметр опыта.    */
	/*================================*/
	final private function Prep0 ($v)
	{
	}
	/*=============================*/
	/* Пустой тестирующий метод.   */
	/* Вызов: $v - параметр опыта. */
	/*=============================*/
	final private function Test0 ($v)
	{
	}
	/*==================================*/
	/* Конструктор тестирующего класса. */
	/*==================================*/
	public function __construct ()
	{
		$this->testmeth = array ();
		$this->prepmeth = array ();
		$i = 0;
		$name = "Test$i";
		while (method_exists ($this, $name)):
			$this->testmeth[] = $name;
			$name = "Prep$i";
			$this->prepmeth[] = method_exists ($this, $name)? $name: '';
			++$i;
			$name = "Test$i";
		endwhile;
	}
	/*========================================================*/
	/* Статистическая обработка результатов серии опытов.     */
	/* Вызов: $ax - массив с результатами опытов.             */
	/* Возврат: array (                                       */
	/*            выборочное среднее значение,                */
	/*            выборочное среднее квадратичное отклонение, */
	/*            */
	/*========================================================*/
	private function Statist1 (&$ax)
	{
		$n = count ($ax);
		/* Вычисление выборочного среднего значения. */
		$sx = 0;
		foreach ($ax as $x):
			$sx += $x;
		endforeach;
		$mean = $sx / $n;
		/* Вычисление выборочного среднего квадратического отклонения отсчётов. */
		$sdx2 = 0;
		foreach ($ax as $x):
			$dx = $x - $mean;
			$sdx2 += $dx * $dx;
		endforeach;
		$sdm = sqrt ($sdx2 / ($n - 1));
		return array ($mean, $sdm);
	}
	/*===================================================*/
	/* Вычисление относительной погрешности измерений.   */
	/* Вызов: $n - количество измерений,                 */
	/*        $m - среднее значение,                     */
	/*        $s - среднее квадратическое отклонение,    */
	/*        $di - инструментальная погрешность,        */
	/*        $kst - коэффициент доверия (Стьюдента).    */
	/* Возврат: относительная погрешность (в процентах). */
	/*===================================================*/
	private function RelatAccur ($n, $m, $s, $di, $kst)
	{
		/* Вычисление случайной составляющей погрешности. */
		$dr = $kst * $s / sqrt ($n);
		/* Вычисление полной абсолютной погрешности. */
		$da = sqrt ($di * $di + $dr * $dr);
		/* Вычисление полной относительной погрешности. */
		return round (100 * $da / $m, 2);
	}
	/*===========================================================*/
	/* Выполнение опыта.                                         */
	/* Вызов: $m - номер тестируемого метода (с нуля),           */
	/*        $n - количество итераций,                          */
	/*        $v - параметр опыта.                               */
	/* Возврат: время, затраченное на выполнение опыта (секунд). */
	/*===========================================================*/
	private function DoExperiment ($m, $n, $v)
	{
		/* Подготовка к тестированию. */
		$prepmeth = $this->prepmeth[$m];
		$testmeth = $this->testmeth[$m];
		/* Получение стартовой отметки времени. */
		$tstart = microtime (TRUE);
		/* Подготовка выполнения опыта. */
		if ($prepmeth):
			$this->$prepmeth ($v);
		endif;
		/* Выполнение опыта. */
		for ($i = 0; $i < $n; ++$i):
			$this->$testmeth ($v);
		endfor;
		/* Получение финишной отметки времени. */
		$tfinish = microtime (TRUE);
		return $tfinish - $tstart;
	}
	/*================================================================*/
	/* Определение количества итераций тестовой функции для получения */
	/* поддающихся измерению временных интервалов опытов.             */
	/* Предполагается, что время выполнения от значения параметра --  */
	/* монотонная функция.                                            */
	/* Вызов: $vmin - минимальное значение параметра,                 */
	/*        $vmax - максимальное значение параметра.                */
	/* Возврат: найденное количество итераций тестовой функции.       */
	/*================================================================*/
	private function Phase1 ($vmin, $vmax)
	{
		$n = 1;				// количество итераций
		$limit = 2;			// нижняя граница времени выполнения
		echo "Phase 1 begin: determining experiment time intervals.\n";
		echo '               Cycles count:';
		do {
			/* Определение минимального времени выполнения. */
			$tmin = 9999999;
			$tmax = 0;
			$m = count ($this->testmeth);
			for ($i = 1; $i < $m; ++$i):
				/* тест по нижней границе значений параметра */
				$t = $this->DoExperiment ($i, $n, $vmin);
				$tmin = min ($tmin, $t);
				$tmax = max ($tmax, $t);
				if ($tmin < $limit):
					break;
				endif;
				/* тест по верхней границе значений параметра (а вдруг функция убывающая) */
				$t = $this->DoExperiment ($i, $n, $vmax);
				$tmin = min ($tmin, $t);
				$tmax = max ($tmax, $t);
				if ($tmin < $limit):
					break;
				endif;
			endfor;
			/* Корректировка количества операций при необходимости. */
			if ($tmin < $limit):
				$n *= 2;
				echo ".";
			endif;
		} while ($tmin < $limit);
		$tmin = round ($tmin, 3);
		$tmax = round ($tmax, 3);
		echo "$n\nPhase 1 end: Tmin = $tmin (sec), Tmax = $tmax (sec).\n";
		return $n;
	}
	/*==================================================================*/
	/* Вычисление постоянной составляющей времени выполнения, связанной */
	/* с затратами на организацию измерительных циклов.                 */
	/* Вызов: $n - количество итераций.                                 */
	/* Возврат: значение постоянной составляющей времени выполнения.    */
	/*==================================================================*/
	private function Phase2 ($n)
	{
		$nexp = 10;		// количество опытов
		$kst9810 = 2.8;	// коэффициент доверия для 98% надёжности и 10 опытов
		$di = 0.02;		// инструментальная погрешность (точность системного таймера 20 мс)
		echo "Phase 2 begin: determining constant component in time.\n";
		/* Цикл до достижения приемлемых результатов (проверка по критерию Шовене). */
		do {
			/* Проведение опытов. */
			$at = array ();
			for ($j = 0; $j < $nexp + 2; ++$j):
				$t = round ($this->DoExperiment (0, $n, 0), 3);
				echo "               Experiment # $j, t = $t (sec)\n";
				$at[] = $t;
			endfor;
			/* Отбрасывание крайних результатов. */
			sort ($at);
			unset ($at[count ($at) - 1]);
			unset ($at[0]);
			/* Статистическая обработка результатов. */
			list ($m, $s) = $this->Statist1 ($at);
			$shovene = $this->Shovene ($at[1], $m, $s) and $this->Shovene ($at[count($at)], $m, $s);
			if (!$shovene):
				echo "Shovene criteria! Repeat the experiment...\n";
			endif;
		} while (!$shovene);
		/* Вычисление относительной погрешности. */
		if ($m != 0):
			$ra = $this->RelatAccur ($nexp, $m, $s, $di, $kst9810);
			echo "               Constant component: <t> = $m +/- $ra% (sec)\n";
		else:
			echo "               Constant component: <t> = $m (sec)\n";
		endif;
		echo "Phase 2 end.\n";
		return $m;
	}
	/*====================================================================*/
	/* Проведение опытов с измерением времени выполнения функций "TestN". */
	/* Вызов: $n - количество "выравнивающих" итераций,                   */
	/*        $t0 - постоянная составляющая времени измерений,            */
	/*        $vmin - минимальное значение параметра,                     */
	/*        $vmax - максимальное значение параметра,                    */
	/*        $vstep - шаг изменения параметра.                           */
	/*====================================================================*/
	private function Phase3 ($n, $t0, $vmin, $vmax, $vstep)
	{
		$nexp = 10;
		$kst9810 = 2.8;		// коэффициент доверия для 98% надёжности и 10 опытов
		$di = 0.02;			// инструментальная погрешность (точность системного таймера 20 мс)
		$res = array ();	// результаты измерений
		/*---------------------------------*/
		/* Выполнение серии экспериментов. */
		/*---------------------------------*/
		echo "Phase 3 begin: Testing methods performance.\n";
		
		for ($v = $vmin; $v <= $vmax; $v += $vstep):	// цикл по параметру
			$res[$v] = array ();
			for ($i = 1; $i < count ($this->testmeth); ++$i):	// цикл по методам
				do {
					$method = $this->testmeth[$i];
					echo "    *** Testing method $method ($v).\n";
					/* Проведение на 2 опыта больше, чем нужно. */
					$at = array ();	// результаты измерений
					for ($j = 0; $j < $nexp + 2; ++$j):	// цикл по опытам
						$t = round ($this->DoExperiment ($i, $n, $v), 3);
						echo "               Experiment # $j, t = $t (sec)\n";
						$at[] = $t;
					endfor;
					/* Отбрасывание крайних результатов. */
					sort ($at);
					unset ($at[count ($at) - 1]);
					unset ($at[0]);
					/* Статистическая обработка результатов. */
					list ($m, $s) = $this->Statist1 ($at);
					/* Оценка достоверности оставшихся результатов по критерию Шовене. */
					$shovene = $this->Shovene ($at[1], $m, $s) and $this->Shovene ($at[count($at)], $m, $s);
					if (!$shovene):
						echo "Shovene criteria! Repeat the experiment...\n";
					endif;
				} while (!$shovene);
				/* Вычисление относительной погрешности. */
				$ra = $this->RelatAccur ($nexp, $m, $s, $di, $kst9810);
				echo "    Result of experiment: <t> = $m +/- $ra% (sec)\n";
				$res[$v][$i] = array ($m, $ra);
			endfor;
		endfor;
		echo "Phase 3 end.\n";
		return $res;
	}
	/*===================================================*/
	/* Запись результатов эксперимента в файл.           */
	/* Вызов: $res - массив с результатами эксперимента. */
	/*        $t0  - постоянная составляющая времени.    */
	/*===================================================*/
	private function Save (&$res, $t0)
	{
		$fname = 'r' . substr (time (), -7) . '.txt';
		$f = fopen ($fname, 'w');
		if ($f):
			fputs ($f, "Constant component: $t0 (sec).\r\n");
			foreach ($res as $v => $expr):
				fputs ($f, $v);
				foreach ($expr as $n => $a):
					list ($t, $ra) = $a;
					fputs ($f, "; $t, $ra");
				endforeach;
				fputs ($f, "\r\n");
			endforeach;
			fclose ($f);
			echo "Results saved to file: $fname\n";
		else:
			echo "Error write to file: $fname\n";
		endif;
	}
	/*==============================================*/
	/* Оценка результата опыта по критерию Шовене,  */
	/* исходя из 10 (десяти) испытаний.             */
	/* Вызов: $x - проверяемое значение,            */
	/*        $m - среднее значение,                */
	/*        $s - среднеквадратическое отклонение. */
	/*==============================================*/
	private function Shovene ($x, $m, $s)
	{
		/*----------------------------------------------------------*/
		/* Вычисление  относительного отклонения случайной величины */
		/* от её среднего значения в единицах среднеквадратического */
		/* отклонения.                                              */
		/*----------------------------------------------------------*/
		$z = abs ($x - $m) / $s;
		return $z <= 1.98;
	}
	/*======================*/
	/* Запуск эксперимента. */
	/*======================*/
	public function Go ($vmin, $vmax, $vstep)
	{
		$n = $this->Phase1 ($vmin, $vmax);
		$t0 = $this->Phase2 ($n);
		$res = $this->Phase3 ($n, $t0, $vmin, $vmax, $vstep);
		$this->Save ($res, $t0);
	}
}
?>
