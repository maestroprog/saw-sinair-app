<?php

namespace Iassasin\Sinair\SampleApp;

use Iassasin\Fidb\Connection\ConnectionMysql;
use Iassasin\Phplate\Exception\PhplateConfigException;
use Iassasin\Phplate\Template;
use Iassasin\Phplate\TemplateOptions;
use Maestroprog\Saw\Application\BasicMultiThreaded;
use Maestroprog\Saw\Application\Context\ContextPool;
use Maestroprog\Saw\Memory\SharedMemoryInterface;
use Maestroprog\Saw\Thread\AbstractThread;
use Maestroprog\Saw\Thread\MultiThreadingProvider;

class MyApplication extends BasicMultiThreaded
{
    const ID = 'saw.sinair.test';

    /**
     * @var AbstractThread
     */
    private $times;

    private $i;
    private $p;

    private $mcglOnline;
    private $db;

    public function __construct(
        string $id,
        MultiThreadingProvider $multiThreadingProvider,
        SharedMemoryInterface $applicationMemory,
        ContextPool $contextPool,
        MCGLOnline $mcglOnline,
        ConnectionMysql $db
    )
    {
        parent::__construct($id, $multiThreadingProvider, $applicationMemory, $contextPool);

        $this->mcglOnline = $mcglOnline;
        $this->db = $db;
    }

    public function init(): void
    {
        $this->i = microtime(true);
        $options = new TemplateOptions();
        try {
            $options->setCacheDir(__DIR__ . '/../cache/');
        } catch (PhplateConfigException $e) {
            die('Cannot open cache dir.');
        }
        Template::init(__DIR__ . '/../template/', $options);
    }

    public function prepare()
    {
        $this->p = microtime(true);

        return null;
    }

    protected function main($prepared): void
    {
        $this->init();

        echo '<div class="content-inner mcgl-online-block"><a href="/mcgl/ping/">Состояния каналов (beta)</a></div>';
        echo '<div class="content-inner mcgl-online-block">Онлайн по минутам (вертикальные линии - часы):<br/>';

        $w = 800;
        $h = 100;

        $htmlThreads[] = $onlineGeneral = $this->thread('online_general', function () use ($w, $h) {

            $inner1 = $this->thread('INNER1', function () use ($w, $h) {
                $arr = $this->mcglOnline->getLastMins($w);
                $vals = [];
                $max = max(array_column($arr, 'v'));
                $i = 0;
                foreach ($arr as $v) {
                    $x = $w - $i;
                    $y = round($h - (($v['v'] + 0.0) / $max * $h), 1);
                    $vals[] = ['x' => $x, 'y' => $y];
                    ++$i;
                }
                return $vals;
            });

            $inner2 = $this->thread('INNER2', function () use ($w) {
                $arr = $this->mcglOnline->getLastMins($w);
                $hours = [];
                $i = 0;
                foreach ($arr as $v) {
                    $x = $w - $i;
                    if (date('i', $v['t']) == 0) {
                        $hours[] = $x;
                    }
                    ++$i;
                }
                return $hours;
            });

            $inner3 = $this->thread('INNER3', function () use ($w) {
                $arr = $this->mcglOnline->getLastMins($w);
                $origvals = [];
                foreach ($arr as $v) {
                    $origvals[] = +$v['v'];
                }
                return $origvals;
            });

            $start = microtime(true);
            $this->runThreads([$inner1, $inner2, $inner3]);
            $this->synchronizeThreads($ts = [$inner1, $inner2, $inner3]);

            $vals = $inner1->getResult();
            $hours = $inner2->getResult();
            $origvals = $inner3->getResult();

            $tpl = microtime(true);
            $html = Template::build('online_general', [
                'w' => $w,
                'h' => $h,
                'vals' => $vals,
                'hours' => $hours,
                'time' => (+date('H') * 60 + +date('i')),
                'origvals' => $origvals,
            ]);
            return $html
                /*. ' templating ' . (microtime(true) - $tpl)
                . ' local timings ' . implode('<br>', array_map(function (AbstractThread $t) {
                    return ' ' . $t->getUniqueId() . ' by ' . $t->getWorkerId() . ' ' . $t->getExecTime();
                }, $ts)) . ' awaiting '*/ . (microtime(true) - $start);
        });

        $htmlThreads[] = $todayMaximum = $this->thread('today_maximum', function () {
            $dmax = $this
                ->db
                ->query('
                    SELECT value, time FROM mcgl_online
                    WHERE time >= CURRENT_DATE AND value = (
                      SELECT MAX(value) FROM mcgl_online WHERE time >= CURRENT_DATE
                    ) ORDER BY TIME DESC LIMIT 1')
                ->fetch();
            $mtime = $dmax['time'];
            $dmax = $dmax['value'];
            return '<br/>Максимум за сегодня: ' . $dmax . ' в ' . date('H:i', strtotime($mtime)) . '</div>';
        });

        $htmlThreads[] = $lastDayMaximum = $this->thread('last_day_maximum', function () {

            $max = 0;
            $min = 9999999;
            $vals_ = $this->mcglOnline->getLastDaysMax(20);
            $vals = [];

            foreach ($vals_ as $row) {
                $w = +date('w', $row['t']);
                $vals[] = [
                    't' => date('d.m', $row['t']),
                    'v' => $row['v'],
                    's' => ($w == 0 || $w == 6 ? '2' : '1'),
                ];
                if ($row['v'] > $max) {
                    $max = $row['v'];
                }
                if ($row['v'] < $min) {
                    $min = $row['v'];
                }
            }

            return $this->print_diagram('Максимальный онлайн в сутки:', $vals, $min, $max, 800, 100, 40, 11);
        });

        $htmlThreads[] = $lastDayAvg = $this->thread('last_day_avg', function () {

            $maxavg = 0;
            $minavg = 9999999;
            $valsavg_ = $this->mcglOnline->getLastDaysAvg(20);
            $valsavg = [];

            foreach ($valsavg_ as $row) {
                $w = +date('w', $row['t']);
                $valsavg[] = [
                    't' => date('d.m', $row['t']),
                    'v' => round($row['v']),
                    's' => ($w == 0 || $w == 6 ? '2' : '1'),
                ];
                if ($row['v'] > $maxavg) {
                    $maxavg = $row['v'];
                }
                if ($row['v'] < $minavg) {
                    $minavg = $row['v'];
                }
            }

            return $this->print_diagram('Средний онлайн в сутки (без учета нулей):', $valsavg, $minavg, $maxavg, 800, 100, 40, 11);
        });

        $htmlThreads[] = $monthlyMax = $this->thread('monthly_max', function () {

            $max = 0;
            $min = 9999999;
            $vals_ = $this->mcglOnline->getLastMonthsMax(20);
            $vals = [];

            foreach ($vals_ as $row) {
                $vals[] = [
                    't' => date('m.y', $row['t']),
                    'v' => $row['v'],
                    's' => '1',
                ];
                if ($row['v'] > $max) {
                    $max = $row['v'];
                }
                if ($row['v'] < $min) {
                    $min = $row['v'];
                }
            }

            return $this->print_diagram('Максимальный онлайн за месяц:', $vals, $min, $max, 800, 100, 40, 11);
        });

        $htmlThreads[] = $monthlyAvg = $this->thread('monthly_avg', function () {

            $max = 0;
            $min = 9999999;
            $vals_ = $this->mcglOnline->getLastMonthsAvg(20);
            $vals = [];

            foreach ($vals_ as $row) {
                $vals[] = [
                    't' => date('m.y', $row['t']),
                    'v' => round($row['v']),
                    's' => '1',
                ];
                if ($row['v'] > $max) {
                    $max = $row['v'];
                }
                if ($row['v'] < $min) {
                    $min = $row['v'];
                }
            }

            return $this->print_diagram('Средний онлайн за месяц (без учета нулей):', $vals, $min, $max, 800, 100, 40, 11);
        });

        $this->htmls = $htmlThreads;
    }


    /**
     * @var AbstractThread[]
     */
    private $htmls = [];

    public function end()
    {
        $time = microtime(true);
        $this->synchronizeAll();
        $ended = microtime(true) - $this->i;
        $content = '';
        foreach ($this->htmls as $html) {
            $content .= $html->getResult();
            /*$timinigs[] = $html->getUniqueId()
                . ' : work time: ' . round(($html->getEndedTime() - $html->getStartedTime()) * 1000, 1)
                . ' started at ' . $html->getStartedTime()
                . ' ended at ' . $html->getEndedTime()
                . ' really exec time: ' . round($html->getExecTime() * 1000, 1)
                . ' on worker ' . $html->getWorkerId();*/
        }
        echo Template::build(
            'index',
            [
                'content' => $content,
                'timing' => round($ended * 1000, 1),
                'times' => [
                    ['label' => 'p', 'time' => microtime(true) - $this->p],
                    ['label' => 'sync', 'time' => microtime(true) - $time],
                    ['label' => 'ended', 'time' => $ended],
                ],
            ]
        );
    }

    private function print_diagram($title, $vals, $min, $max, $w, $h, $rw, $fs)
    {
        $html = "<div class=\"content-inner mcgl-online-block\">$title<br/>";

        $dy = 25 * ($max - $min + 0.0) / $h;
        $max += $dy;
        $min -= $dy;

        $x = $w;
        foreach ($vals as &$v) {
            $x -= $rw;
            $y = round(($v['v'] - $min) / ($max - $min) * $h, 1);
            $tx = $x + $rw / 2;
            $ty = ($y < $h - $fs - 3) ? $h - $y - 3 : $fs + 3;
            if ($ty > $h - 20) {
                $ty = $h - 20;
            }

            $v = ['x' => $x, 'y' => $y, 'tx' => $tx, 'ty' => $ty, 'v' => $v['v'], 't' => $v['t'], 's' => $v['s']];
        }

        $tpl = microtime(true);
        $html .= Template::build('online_diagram', ['w' => $w, 'h' => $h, 'rw' => $rw, 'fs' => $fs, 'to' => $h - 3, 'vals' => $vals]);

        return $html . '</div>' . ' templating ' . (microtime(true) - $tpl);
    }
}
