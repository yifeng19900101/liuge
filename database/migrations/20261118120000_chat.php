<?php

use think\migration\Migrator;

class Chat extends Migrator
{
    public function up(): void
    {
        $this->chatConversation();
        $this->chatMessage();
    }

    public function chatConversation(): void
    {
        if (!$this->hasTable('chat_conversation')) {
            $table = $this->table('chat_conversation', [
                'id'          => false,
                'comment'     => '会员聊天会话表',
                'row_format'  => 'DYNAMIC',
                'primary_key' => 'id',
                'collation'   => 'utf8mb4_unicode_ci',
            ]);
            $table->addColumn('id', 'integer', ['comment' => 'ID', 'signed' => false, 'identity' => true, 'null' => false])
                ->addColumn('user_id', 'integer', ['comment' => '会员ID', 'default' => 0, 'signed' => false, 'null' => false])
                ->addColumn('last_message', 'string', ['limit' => 255, 'default' => '', 'comment' => '最后一条消息', 'null' => false])
                ->addColumn('last_time', 'integer', ['limit' => 10, 'signed' => false, 'default' => 0, 'comment' => '最后消息时间', 'null' => false])
                ->addColumn('create_time', 'biginteger', ['signed' => false, 'null' => true, 'default' => null, 'comment' => '创建时间'])
                ->addColumn('update_time', 'biginteger', ['signed' => false, 'null' => true, 'default' => null, 'comment' => '更新时间'])
                ->addIndex(['user_id'], ['unique' => true])
                ->create();
        }
    }

    public function chatMessage(): void
    {
        if (!$this->hasTable('chat_message')) {
            $table = $this->table('chat_message', [
                'id'          => false,
                'comment'     => '会员聊天消息表',
                'row_format'  => 'DYNAMIC',
                'primary_key' => 'id',
                'collation'   => 'utf8mb4_unicode_ci',
            ]);
            $table->addColumn('id', 'biginteger', ['comment' => 'ID', 'signed' => false, 'identity' => true, 'null' => false])
                ->addColumn('user_id', 'integer', ['comment' => '会员ID', 'default' => 0, 'signed' => false, 'null' => false])
                ->addColumn('role', 'enum', ['values' => 'user,bot', 'default' => 'user', 'comment' => '角色:user=用户,bot=机器人', 'null' => false])
                ->addColumn('content', 'text', ['null' => true, 'default' => null, 'comment' => '消息内容'])
                ->addColumn('create_time', 'biginteger', ['signed' => false, 'null' => true, 'default' => null, 'comment' => '创建时间'])
                ->addIndex(['user_id', 'id'], ['name' => 'user_id_id'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('chat_message')) {
            $this->table('chat_message')->drop()->save();
        }
        if ($this->hasTable('chat_conversation')) {
            $this->table('chat_conversation')->drop()->save();
        }
    }
}
