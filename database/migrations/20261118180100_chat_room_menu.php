<?php

use think\facade\Db;
use think\migration\Migrator;

/**
 * 后台「聊天室」菜单与权限节点
 */
class ChatRoomMenu extends Migrator
{
    public function up(): void
    {
        if (!$this->hasTable('admin_rule')) return;

        $now   = time();
        $dirId = (int)Db::name('admin_rule')->where('name', 'chat')->value('id');
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

        $menuId = (int)Db::name('admin_rule')->where('name', 'chat/room')->value('id');
        if (!$menuId) {
            $menuId = Db::name('admin_rule')->insertGetId([
                'pid'         => $dirId,
                'type'        => 'menu',
                'title'       => '聊天室',
                'name'        => 'chat/room',
                'path'        => 'chat/room',
                'icon'        => 'fa fa-comment',
                'menu_type'   => 'tab',
                'url'         => '',
                'component'   => '/src/views/backend/chat/room/index.vue',
                'keepalive'   => 1,
                'extend'      => 'none',
                'remark'      => '',
                'weigh'       => 0,
                'status'      => 1,
                'update_time' => $now,
                'create_time' => $now,
            ]);
        }

        $buttons = [
            ['index', '查看', 2],
            ['messages', '查看聊天记录', 1],
            ['send', '发送风控/报表', 0],
        ];
        foreach ($buttons as [$action, $title, $weigh]) {
            $name = 'chat/room/' . $action;
            if (Db::name('admin_rule')->where('name', $name)->value('id')) continue;
            Db::name('admin_rule')->insert([
                'pid'         => $menuId,
                'type'        => 'button',
                'title'       => $title,
                'name'        => $name,
                'path'        => '',
                'icon'        => '',
                'url'         => '',
                'component'   => '',
                'keepalive'   => 0,
                'extend'      => 'none',
                'remark'      => '',
                'weigh'       => $weigh,
                'status'      => 1,
                'update_time' => $now,
                'create_time' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('admin_rule')) return;

        Db::name('admin_rule')->whereIn('name', [
            'chat/room/index',
            'chat/room/messages',
            'chat/room/send',
            'chat/room',
        ])->delete();
    }
}
