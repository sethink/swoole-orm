<?php

namespace sethink\swooleOrm\db;

class Builder
{
    protected $sethinkBind = [];

    // SQL表达式
    protected $selectSql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%UNION%%ORDER%%LIMIT%%LOCK%%COMMENT%';

    protected $insertSql = 'INSERT INTO %TABLE% (%FIELD%) VALUES (%DATA%) %COMMENT%';

    protected $insertAllSql = 'INSERT INTO %TABLE% (%FIELD%) VALUES %DATA% %COMMENT%';

    protected $updateSql = 'UPDATE %TABLE% SET %SET% %JOIN% %WHERE% %ORDER%%LIMIT% %LOCK%%COMMENT%';

    protected $deleteSql = 'DELETE FROM %TABLE% %USING% %JOIN% %WHERE% %ORDER%%LIMIT% %LOCK%%COMMENT%';


    public function select($options)
    {
        $sql = str_replace(
            ['%TABLE%', '%DISTINCT%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%', '%UNION%', '%LOCK%', '%COMMENT%', '%FORCE%'],
            [
                $this->parseTable($options['table']),
                $this->parseDistinct($options['distinct']),
                $this->parseField($options['field']),
                '',
                //                $this->parseJoin($options['join']),
                $this->parseWhere($options['where']),
                $this->parseGroup($options['group']),
                $this->parseHaving($options['having']),
                $this->parseOrder($options['order']),
                $this->parseLimit($options['limit']),
                '',
                //                $this->parseUnion($options['union']),
                '',
                //                $this->parseLock($options['lock']),
                '',
                //                $this->parseComment($options['comment']),
                '',
                //                $this->parseForce($options['force']),
            ],
            $this->selectSql);

        return [
            'sql'         => $sql,
            'sethinkBind' => $this->sethinkBind
        ];
    }

    public function insert($options)
    {
        // 分析并处理数据
        if (empty($options['data'])) {
            return '';
        }

        $fields = $values = '';
        foreach ($options['data'] as $k => $v) {
            $fields .= "`{$k}`,";
            $values .= "?,";

            $this->sethinkBind[] = $v;
        }
        $fields = rtrim($fields, ',');
        $values = rtrim($values, ',');

        $sql = str_replace(
            ['%TABLE%', '%FIELD%', '%DATA%', '%COMMENT%'],
            [
                $this->parseTable($options['table']),
                $fields,
                $values,
                ''
                //                    $this->parseComment($options['comment']),
            ],
            $this->insertSql);

        return [
            'sql'         => $sql,
            'sethinkBind' => $this->sethinkBind
        ];
    }

    public function insertAll($options)
    {
        if (empty($options['data'])) {
            return '';
        }

        $keys = [];
        foreach ($options['data'] as $v) {
            $keys = array_merge($keys, array_keys($v));
        }
        $keys = array_merge(array_unique($keys));

        $fields = '';
        foreach ($keys as $v) {
            $fields .= "`{$v}`,";
        }
        $fields = rtrim($fields, ',');

        $data = '';
        foreach ($options['data'] as $v) {
            $data .= '(';
            foreach ($keys as $vv) {
                if (isset($v[$vv])) {
                    $this->sethinkBind[] = $v[$vv];
                } else {
                    $this->sethinkBind[] = '';
                }
                $data .= '?,';
            }
            $data = rtrim($data, ',') . '),';
        }
        $data = rtrim($data, ',');

        $sql = str_replace(
            ['%TABLE%', '%FIELD%', '%DATA%', '%COMMENT%'],
            [
                $this->parseTable($options['table']),
                $fields,
                $data,
                ''
                //                $this->parseComment($options['comment']),
            ],
            $this->insertAllSql);

        return [
            'sql'         => $sql,
            'sethinkBind' => $this->sethinkBind
        ];
    }


    public function update($options)
    {
        if (empty($options['data'])) {
            return '';
        }

        $set = '';
        foreach ($options['data'] as $k => $v) {
            $set                 .= "`{$k}`=?,";
            $this->sethinkBind[] = $v;
        }
        $set = rtrim($set, ',');

        $sql = str_replace(
            ['%TABLE%', '%SET%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->parseTable($options['table']),
                $set,
                '',
                //                $this->parseJoin($options['join']),
                $this->parseWhere($options['where']),
                $this->parseOrder($options['order']),
                $this->parseLimit($options['limit']),
                '',
                //                $this->parseLock($options['lock']),
                '',
                //                $this->parseComment($options['comment']),
            ],
            $this->updateSql);

        return [
            'sql'         => $sql,
            'sethinkBind' => $this->sethinkBind
        ];
    }

    public function delete($options)
    {
        $sql = str_replace(
            ['%TABLE%', '%USING%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->parseTable($options['table']),
                '',
                //                !empty($options['using']) ? ' USING ' . $this->parseTable($options['using']) . ' ' : '',
                '',
                //                $this->parseJoin($options['join']),
                $this->parseWhere($options['where']),
                $this->parseOrder($options['order']),
                $this->parseLimit($options['limit']),
                '',
                //                $this->parseLock($options['lock']),
                '',
                //                $this->parseComment($options['comment']),
            ],
            $this->deleteSql);
        return [
            'sql'         => $sql,
            'sethinkBind' => $this->sethinkBind
        ];
    }


    protected function parseTable($tableName)
    {
        return "`$tableName`";
    }

    protected function parseDistinct($distinct)
    {
        return !empty($distinct) ? ' DISTINCT ' : '';
    }

    protected function parseOrder($order)
    {
        $orderStr = '';
        foreach ($order as $v) {
            if (is_array($v)) {
                foreach ($v as $kk => $vv) {
                    $orderStr .= "`{$kk}` " . strtoupper($vv) . ',';
                }
            } else {
                $orderStr .= "`{$v}` ASC,";
            }
        }
        $orderStr = rtrim($orderStr, ',');
        return empty($orderStr) ? '' : ' ORDER BY ' . $orderStr;
    }

    protected function parseGroup($group)
    {
        return empty($group) ? '' : " GROUP BY `{$group}`";
    }

    protected function parseHaving($having)
    {
        return empty($having) ? '' : ' HAVING ' . $having;
    }


    protected function parseField($fields)
    {
        $fieldsStr = '';
        if (is_array($fields) && count($fields) > 0) {
            foreach ($fields as $v) {
                $fieldsStr .= "`{$v}`,";
            }
            $fieldsStr = rtrim($fieldsStr, ',');
        } else {
            $fieldsStr .= '*';
        }
        return $fieldsStr;
    }

    protected function whereExp($k, $v)
    {
        $v[0] = strtoupper($v[0]);

        switch ($v[0]) {
            case '=':
            case '<>':
            case '>':
            case '>=':
            case '<':
            case '<=':
            case 'LIKE':
            case 'NOT LIKE':
                return $this->parseCompare($k, $v);
                break;
            case 'IN':
            case 'NOT IN':
                return $this->parseIn($k, $v);
                break;
        }

        return false;
    }

    protected function parseWhere($where)
    {
        $whereStr = '';
        foreach ($where as $k => $v) {
            if (is_array($v)) {
                if (count($v) == 3 && strtoupper($v[2]) == 'OR') {
                    $whereStr = rtrim($whereStr, " AND ") . ' OR ';
                }
                $whereStr .= $this->whereExp($k, $v);
            } else {
                $whereStr            .= "(`{$k}` = ?)";
                $this->sethinkBind[] = $v;
            }

            $whereStr .= ' AND ';
        }
        $whereStr = rtrim($whereStr, " AND ");
        return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
    }


    protected function parseCompare($k, $v)
    {
        $whereStr            = "(`{$k}` {$v[0]} ?)";
        $this->sethinkBind[] = $v[1];
        return $whereStr;
    }

    protected function parseIn($k, $v)
    {
        $whereStr = '';

        $value_tmp = '';
        foreach ($v[1] as $vv) {
            $this->sethinkBind[] = $vv;
            $value_tmp           .= "?,";
        }
        if (strlen($value_tmp) > 0) {
            $value_tmp = rtrim($value_tmp, ',');
            $value     = "($value_tmp)";
            $whereStr  .= "(`{$k}` {$v[0]} {$value})";
        }

        return $whereStr;
    }

    protected function parseLimit($limit)
    {
        return (!empty($limit) && false === strpos($limit, '(')) ? ' LIMIT ' . $limit . ' ' : '';
    }


    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        unset($this->sethinkBind);
    }


}
