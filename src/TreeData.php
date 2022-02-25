<?php
/*//=================================
//
//	树形结构数据处理
//  更新时间: 2017-11-28
//
//===================================*/

class TreeData
{
    //数据
    protected $data = [];
    //主键名
    protected $idName = '';
    //父键名
    protected $pidName = '';

    //初始化数据
    function __construct(array $data, $idName, $pidName)
    {
        $this->idName = trim($idName);
        $this->pidName = trim($pidName);

        $this->data = [];
        foreach ($data as $r) {
            $this->data[$r[$idName]] = $r;
        }
    }

    //获取数据
    function get($id = NULL, $key = NULL)
    {
        if (isset($id)) {
            return isset($key) ? $this->data[$id][$key] : $this->data[$id];
        }

        return $this->data;
    }

    //获了全部id
    function getIds()
    {
        return array_column($this->data, $this->idName);
    }

    //获取节点在树中的层级
    function level($id)
    {
        return count($this->nodePath($id));
    }

    //计算出指定节点的路径
    function nodePath($id)
    {
        $path = [];
        while (isset($this->data[$id])) {
            $path[] = $id; //记录节点id
            $id = $this->data[$id][$this->pidName];
        }

        return array_reverse($path);
    }

    //获取孩子节点
    function children($id)
    {
        $ids = [];
        foreach ($this->data as $r) {
            if ($r[$this->pidName] == $id) {
                $ids[] = $r[$this->idName];
            }
        }

        return $ids;
    }

    //获取全部子节点
    function allChildren($id)
    {
        $ids = [];
        foreach ($this->data as $r) {
            if ($r[$this->pidName] == $id) {
                $ids[] = $r[$this->idName];
            }

            //递归查询全部子节点
            $_ids = $this->allChildren($r[$this->idName]);
            $ids = array_merge($ids, $_ids);
        }

        return $ids;
    }

}
