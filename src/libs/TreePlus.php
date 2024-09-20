<?php

namespace wing\libs;

class TreePlus
{
    private string $idKey = 'id';
    private string $pidKey = 'parent_id';
    private string $childKey = 'children';

    private array $flatData = [];
    private array $treeData = [];

    private static TreePlus $instance;
    public function __construct(string $idKey, string $pidKey, string $childKey)
    {
        $this->idKey = $idKey;
        $this->pidKey = $pidKey;
        $this->childKey = $childKey;
    }

    public static function instance(array $data, $options = []): static
    {
        if(!self::$instance){
            self::$instance = new static(
                $options['idKey'] ?? 'id',
                $options['pidKey'] ?? 'parent_id',
                $options['childKey'] ?? 'children'
            );
            self::$instance->setFlatData($data);
        }
        return self::$instance;
    }

    public function setFlatData(array $data): static
    {
        $this->flatData = $data;
        return $this;
    }

    public function setTreeData(array $data): static
    {
        $this->treeData = $data;
        return $this;
    }

    public function getTreeArray(array $data = []): array
    {
        if(!empty($data)) {
            return $this->toTree($data);
        }
        if(empty($this->treeData)){
            $this->treeData = $this->toTree();
        }
        return $this->treeData;
    }

    // 从平面数据生成树状数据
    public function toTree(array $data = []): array
    {
        if(empty($data)) {
            $data = $this->flatData;
        }
        $tree = [];
        $items = [];
        foreach ($data as $item) {
            $items[$item[$this->idKey]] = $item;
        }
        foreach ($data as $item) {
            $parentId = $item[$this->pidKey];
            if ($parentId) {
                if (isset($items[$parentId])) {
                    $items[$parentId][$this->childKey][] = &$items[$item[$this->idKey]];
                }
            } else {
                $tree[] = &$items[$item[$this->idKey]];
            }
        }
        return $tree;
    }

    // 从树状结构中查找某个子树
    public function findSubTree(array $tree, $id): array
    {
        $result = [];
        foreach ($tree as $item) {
            if ($item[$this->idKey] == $id) {
                $result = $item;
                break;
            } elseif (isset($item[$this->childKey])) {
                $result = $this->findSubTree($item[$this->childKey], $id);
                if ($result) {
                    break;
                }
            }
        }
        return $result;
    }

    // 从平坦数组中查找某个节点的所有子节点id
    public function findSubIdsFromFlat(array $data, $id): array
    {
        $result = [];
        foreach ($data as $item) {
            if ($item[$this->pidKey] == $id) {
                $result[] = $item[$this->idKey];
                $result = array_merge($result, $this->findSubIdsFromFlat($data, $item[$this->idKey]));
            }
        }
        return $result;
    }
}