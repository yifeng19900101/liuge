<?php

namespace app\admin\library\traits;

use Throwable;

/**
 * 只读后台控制器
 * 提供与后台默认 CRUD 一致的查看能力，禁止新增、编辑、删除、排序
 */
trait ReadOnlyBackend
{
    /**
     * 查看
     * @throws Throwable
     */
    public function index(): void
    {
        if ($this->request->param('select')) {
            $this->select();
        }

        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $res = $this->model
            ->field($this->indexField)
            ->withJoin($this->withJoinTable, $this->withJoinType)
            ->alias($alias)
            ->where($where)
            ->order($order)
            ->paginate($limit);

        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 只读提示
     */
    protected function readOnlyError(): void
    {
        $this->error('该页面为只读，不支持修改数据');
    }

    public function add(): void
    {
        $this->readOnlyError();
    }

    public function edit(): void
    {
        $this->readOnlyError();
    }

    public function del(): void
    {
        $this->readOnlyError();
    }

    public function sortable(): void
    {
        $this->readOnlyError();
    }
}
