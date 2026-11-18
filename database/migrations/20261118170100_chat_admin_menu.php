<?php

use think\facade\Db;
use think\migration\Migrator;

/**
 * 聊天管理后台菜单与权限节点
 */
class ChatAdminMenu extends Migrator
{
    public function up(): void
    {
        if (!$this->hasTable('admin_rule')) return;

        $now     = time();
        $dirId   = $this->ruleId('chat');
        if (!$dirId) {
            $dirId = Db::name('admin_rule')->insertGetId([
                'pid'         => 0,
                'type'        => 'menu_dir',
                'title'       => '聊天管理',
                'name'        => 'chat',
                'path'        => 'chat',
                'icon'        => 'fa fa-comments',
                'url'         => '',
                'component'   => '',
                'keepalive'   => 0,
                'extend'      => 'none',
                'remark'      => '',
                'weigh'       => 79,
                'status'      => 1,
                'update_time' => $now,
                'create_time' => $now,
            ]);
        }

        $menus = [
            ['conversation', '聊天会话', 'fa fa-commenting-o', 3],
            ['order', '下单记录', 'fa fa-list-alt', 2],
            ['message', '聊天记录', 'fa fa-history', 1],
        ];
        foreach ($menus as [$name, $title, $icon, $weigh]) {
            $menuName = 'chat/' . $name;
            $menuId   = $this->ruleId($menuName);
            if (!$menuId) {
                $menuId = Db::name('admin_rule')->insertGetId([
                    'pid'         => $dirId,
                    'type'        => 'menu',
                    'title'       => $title,
                    'name'        => $menuName,
                    'path'        => $menuName,
                    'icon'        => $icon,
                    'menu_type'   => 'tab',
                    'url'         => '',
                    'component'   => '/src/views/backend/chat/' . $name . '/index.vue',
                    'keepalive'   => 1,
                    'extend'      => 'none',
                    'remark'      => '',
                    'weigh'       => $weigh,
                    'status'      => 1,
                    'update_time' => $now,
                    'create_time' => $now,
                ]);
            }

            $buttonName = $menuName . '/index';
            if (!$this->ruleId($buttonName)) {
                Db::name('admin_rule')->insert([
                    'pid'         => $menuId,
                    'type'        => 'button',
                    'title'       => '查看',
                    'name'        => $buttonName,
                    'path'        => '',
                    'icon'        => '',
                    'url'         => '',
                    'component'   => '',
                    'keepalive'   => 0,
                    'extend'      => 'none',
                    'remark'      => '',
                    'weigh'       => 0,
                    'status'      => 1,
                    'update_time' => $now,
                    'create_time' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('admin_rule')) return;

        $names = ['chat/conversation/index', 'chat/order/index', 'chat/message/index', 'chat/conversation', 'chat/order', 'chat/message', 'chat'];
        Db::name('admin_rule')->whereIn('name', $names)->delete();
    }

    /**
     * 按规则名查询ID
     */
    protected function ruleId(string $name): int
    {
        return (int)Db::name('admin_rule')->where('name', $name)->value('id');
    }
}
