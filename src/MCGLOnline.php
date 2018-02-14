<?php

namespace Iassasin\Sinair\SampleApp;

use Iassasin\Fidb\Connection\Connection;

class MCGLOnline
{
    private $lastMins;
    private $lastDaysMax;
    private $lastDaysAvg;
    private $lastMonthsMax;
    private $lastMonthsAvg;

    public const CACHE_ITEMS = 800;

    /**
     * @var Connection
     */
    private $db;

    private function getDA($name)
    {
        return $this->da->get('mcgl_online_' . $name);
    }

    private function setDA($name, $val)
    {
        return $this->da->set('mcgl_online_' . $name, $val, MEMCACHE_COMPRESSED);
    }

    public function __construct(Connection $db)
    {
        $this->da = new \Memcache();
        $this->da->connect('localhost');

        $this->db = $db;
    }

    public function getLastMins($cnt)
    {
        $this->updateCache('getLastMins');
        return array_slice($this->lastMins, 0, $cnt);
    }

    public function getLastDaysMax($cnt)
    {
        $this->updateCache('getLastDaysMax');
        return array_slice($this->lastDaysMax, 0, $cnt);
    }

    public function getLastDaysAvg($cnt)
    {
        $this->updateCache('getLastDaysAvg');
        return array_slice($this->lastDaysAvg, 0, $cnt);
    }

    public function getLastMonthsMax($cnt)
    {
        $this->updateCache('getLastMonthsMax');
        return array_slice($this->lastMonthsMax, 0, $cnt);
    }

    public function getLastMonthsAvg($cnt)
    {
        $this->updateCache('getLastMonthsAvg');
        return array_slice($this->lastMonthsAvg, 0, $cnt);
    }

    private function updateLastMins()
    {
        $lmi = $this->getDA('last_mins');
        $lmi = $lmi === false ? [] : $lmi;

        if (count($lmi) == 0) {
            $res = $this->db->query('SELECT time, value FROM mcgl_online ORDER BY time DESC LIMIT ' . self::CACHE_ITEMS);
            while ($row = $res->fetch()) {
                $lmi[] = ['t' => strtotime($row['time']), 'v' => +$row['value']];
            }
        } else {
            $res = $this->db->query('SELECT time, value FROM mcgl_online WHERE time > FROM_UNIXTIME(%d)'
                . ' ORDER BY time DESC LIMIT ' . self::CACHE_ITEMS, $lmi[0]['t']);
            $c = $res->rows();
            $r = [];
            while ($row = $res->fetch()) {
                $r[] = ['t' => strtotime($row['time']), 'v' => +$row['value']];
            }

            if ($c) {
                $lmi = array_merge($r, array_slice($lmi, 0, -$c));
            }
        }

        $this->setDA('last_mins', $lmi);
        return $lmi;
    }

    private function updateLastGroupField($daname, $tcol, $vcol, $group)
    {
        $ldm = $this->getDA($daname);
        $ldm = $ldm === false ? [] : $ldm;

        if (count($ldm) == 0) {
            $res = $this->db->query('SELECT ' . $tcol . ' as tm, ' . $vcol . ' AS val FROM mcgl_online WHERE value != 0'
                . ' GROUP BY ' . $group . ' ORDER BY time DESC LIMIT ' . self::CACHE_ITEMS);
            while ($row = $res->fetch()) {
                $ldm[] = ['t' => strtotime($row['tm']), 'v' => +$row['val']];
            }
        } else {
            $res = $this->db->query('SELECT ' . $tcol . ' as tm, ' . $vcol . ' AS val FROM mcgl_online'
                . ' WHERE value != 0 AND time >= FROM_UNIXTIME(%d) GROUP BY ' . $group . ' ORDER BY time DESC LIMIT ' . self::CACHE_ITEMS, $ldm[0]['t']);
            $c = $res->rows();
            $r = [];
            while ($row = $res->fetch()) {
                $r[] = ['t' => strtotime($row['tm']), 'v' => +$row['val']];
            }

            if ($c > 1) {
                $ldm = array_slice($ldm, 1, -$c);
            } else {
                $ldm = array_slice($ldm, 1);
            }
            $ldm = array_merge($r, $ldm);
        }

        $this->setDA($daname, $ldm);
        return $ldm;
    }

    private $mt;

    public function updateCache($key)
    {
        $fieldKey = 'cacheUpdate' . $key;
        if (!isset($this->mt) || floor(time() / 60) > $this->mt || !isset($this->{$fieldKey})) {
            $cu = $this->getDA($fieldKey);
            if ($cu === false) {
                $cu = 0;
                $this->setDA($fieldKey, $cu);
            }
            $this->updateCacheItem($key);
            $this->{$fieldKey} = $cu;
            $this->mt = strtotime($this->db->query('SELECT MAX(time) FROM mcgl_online')->result());
        }
    }

    /**
     * @param $key
     */
    private function updateCacheItem($key): void
    {
        $fieldKey = 'cacheUpdate' . $key;

        if (!isset($this->{$fieldKey}) || $this->mt > $this->{$fieldKey}) {
            if ($key === 'getLastMins') {
                $this->lastMins = $this->updateLastMins();
            }
            if ($key === 'getLastDaysMax') {
                $this->lastDaysMax = $this->updateLastGroupField('last_days_max', 'DATE(time)', 'MAX(value)', 'DATE(time)');
            }
            if ($key === 'getLastDaysAvg') {
                $this->lastDaysAvg = $this->updateLastGroupField('last_days_avg', 'DATE(time)', 'AVG(value)', 'DATE(time)');
            }
            if ($key === 'getLastMonthsMax') {
                $this->lastMonthsMax = $this->updateLastGroupField('last_months_max', 'DATE_FORMAT(time, "%Y-%m-01")', 'MAX(value)', 'YEAR(time), MONTH(time)');
            }
            if ($key === 'getLastMonthsAvg') {
                $this->lastMonthsAvg = $this->updateLastGroupField('last_months_avg', 'DATE_FORMAT(time, "%Y-%m-01")', 'AVG(value)', 'YEAR(time), MONTH(time)');
            }

            $lmi = $this->lastMins[0]['t'];
            $this->setDA($fieldKey, $lmi);
            $this->{$fieldKey} = $lmi;
        } elseif (round(time() / 60) > $this->{$fieldKey}) {
            if ($key === 'getLastMins') {
                $this->lastMins = $this->getDA('last_mins');
            }
            if ($key === 'getLastDaysMax') {
                $this->lastDaysMax = $this->getDA('last_days_max');
            }
            if ($key === 'getLastDaysAvg') {
                $this->lastDaysAvg = $this->getDA('last_days_avg');
            }
            if ($key === 'getLastMonthsMax') {
                $this->lastMonthsMax = $this->getDA('last_months_max');
            }
            if ($key === 'getLastMonthsAvg') {
                $this->lastMonthsAvg = $this->getDA('last_months_avg');
            }
        }
    }
}
