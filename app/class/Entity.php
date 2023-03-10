<?php

abstract class CEntity
{

    protected $info;
    protected $model;
    protected $database = 'default';

    function __construct($data = false)
    {
        $this->model = M($this->entity, $this->database);

        if (!empty($data)) {
            if (is_numeric($data)) {
                $this->getById($data);
            } elseif (is_object($data)) {
                $this->info = $data;
            } elseif (is_array($data)) {
                $this->getBy($data);
            }
        }

    }

    function getById($id)
    {
        $this->getBy('id', $id);
    }

    function getBy($field, $value = false)
    {

        $where = [];
        if (is_array($field)) {
            foreach ($field as $k => $v) {
                $where[$k] = $v;
            }
        } else {
            $where[$field] = $value;
        }

        $this->info = M($this->entity, $this->database)->get();
        foreach ($where as $k => $v) {
            if (is_array($v) && !empty($v['value'])) {
                $this->info = $this->info->where($k, $v['value'], $v['sign']);
            } else {
                $this->info = $this->info->where($k, $v);
            }
        }

        $this->info = $this->info->fetch();

    }

    function get($key)
    {
        return $this->info->{$key};
    }

    function __get($key)
    {
        return isset($this->info->{$key}) ? $this->info->{$key} : false;
    }

    function create($data)
    {
        $this->info = M($this->entity, $this->database)->factory();
        return $this->set($data);
    }

    function set($key, $val = false)
    {
        if (is_array($key)) {
            $this->info->setValues($key);
        } else {
            $this->info->setValue($key, $val);
        }

        return $this->info->save();
    }

    function getMany($field = [], $value = false, $order = false, $dir = 'ASC', $callback = false, $options = [])
    {

        $where = [];
        if (is_array($field)) {
            foreach ($field as $k => $v) {
                $where[$k] = $v;
            }
        } else {
            $where[$field] = $value;
        }


        $result = $this->model->get();
        foreach ($where as $k => $v) {
            if (is_array($v) && (isset($v['value']) || isset($v['sign']))) {
                $result = $result->where($k, $v['value'], $v['sign']);
            } else {
                $result = $result->where($k, $v);
            }

        }

        if ($callback) {
            $result = $callback($result);
        }

        if (!empty($order)) {
            $result = $result->orderBy($order, $dir);
        }

        $result = $result->fetchAll(!empty($options['die']));

        $class = get_class($this);

        $objects = [];
        foreach ($result as $item) {
            $objects[] = new $class($item);
        }

        return $objects;
    }

    function delete()
    {
        $this->info->delete();
    }

    function getInfo()
    {
        return $this->info;
    }

    function exist()
    {
        return !empty($this->info) ? true : false;
    }

    function error($msg)
    {
        return [
            'status'  => 'error',
            'message' => $msg
        ];
    }

    function success($msg)
    {
        return [
            'status'  => 'success',
            'message' => $msg
        ];
    }

    function reset()
    {
        $this->info = false;
    }

    function datatable($opts,$callback = false,$countField = 'id') {

        $items = M($this->entity,$this->database)->get();

        $ct = $items->copy()->count($countField);

        $orderField = $opts['columns'][$opts['order'][0]['column']]['data'];
        $orderDir = $opts['order'][0]['dir'];

        if(!empty($opts['cond'])) {
            foreach($opts['cond'] as $k => $v) {
                if (is_array($v) && (isset($v['value']) || isset($v['sign']))) {
                    $items = $items->where($k, $v['value'], $v['sign']);
                } else {
                    $items = $items->where($k, $v);
                }
            }
        }

        if($callback) {
            $items = $callback($items);
        }

        $filtered = $items->copy()->count($countField);

        if(!empty($orderField)) {
            $items = $items->orderBy($orderField,strtoupper($orderDir));
        }
        else {
            $items = $items->orderBy('id','DESC');
        }

        if(!empty($opts['start']))
            $items->offset($opts['start']);

        if(!empty($opts['length']))
            $items->limit($opts['length']);

        $items = $items->fetchAll();

        $return = [];

        $return['draw'] = $opts['draw'] ?: 1;
        $return['recordsTotal'] = $ct;
        $return['recordsFiltered'] = $filtered;
        $return['data'] = $items;

        return $return;
    }
}
