<?php

use think\migration\Migrator;

/**
 * 会话风控锁定
 * 1. 会话增加风控状态、风控时间、风控操作人
 * 2. 聊天记录角色增加 admin（管理员操作留痕），并记录管理员ID
 */
class ChatRiskLock extends Migrator
{
    public function up(): void
    {
        $this->addConversationColumns();
        $this->addMessageColumns();
    }

    /**
     * 会话风控字段
     */
    protected function addConversationColumns(): void
    {
        if (!$this->hasTable('chat_conversation')) return;

        $table   = $this->table('chat_conversation');
        $columns = [
            'is_risk'       => ['type' => 'boolean', 'options' => ['default' => 0, 'null' => false, 'comment' => '是否已风控', 'after' => 'risk_amount']],
            'risk_time'     => ['type' => 'biginteger', 'options' => ['signed' => false, 'null' => true, 'default' => null, 'comment' => '风控时间', 'after' => 'is_risk']],
            'risk_admin_id' => ['type' => 'integer', 'options' => ['signed' => false, 'default' => 0, 'null' => false, 'comment' => '风控操作管理员ID', 'after' => 'risk_time']],
        ];

        $changed = false;
        foreach ($columns as $name => $item) {
            if ($table->hasColumn($name)) continue;
            $table->addColumn($name, $item['type'], $item['options']);
            $changed = true;
        }
        if ($changed) {
            $table->save();
        }
    }

    /**
     * 聊天记录管理员字段
     */
    protected function addMessageColumns(): void
    {
        if (!$this->hasTable('chat_message')) return;

        $table = $this->table('chat_message');
        $changed = false;
        if (!$table->hasColumn('admin_id')) {
            $table->addColumn('admin_id', 'integer', ['signed' => false, 'default' => 0, 'null' => false, 'comment' => '管理员ID', 'after' => 'role']);
            $changed = true;
        }
        $table->changeColumn('role', 'enum', [
            'values'  => 'user,bot,admin',
            'default' => 'user',
            'null'    => false,
            'comment' => '角色:user=用户,bot=机器人,admin=管理员',
        ]);
        $changed = true;

        if ($changed) {
            $table->save();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('chat_message')) {
            $table = $this->table('chat_message');
            if ($table->hasColumn('admin_id')) {
                $table->removeColumn('admin_id');
            }
            $table->changeColumn('role', 'enum', [
                'values'  => 'user,bot',
                'default' => 'user',
                'null'    => false,
                'comment' => '角色:user=用户,bot=机器人',
            ]);
            $table->save();
        }

        if ($this->hasTable('chat_conversation')) {
            $table = $this->table('chat_conversation');
            foreach (['is_risk', 'risk_time', 'risk_admin_id'] as $column) {
                if ($table->hasColumn($column)) {
                    $table->removeColumn($column);
                }
            }
            $table->save();
        }
    }
}
