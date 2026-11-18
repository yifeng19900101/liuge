<?php

use think\facade\Db;
use think\migration\Migrator;

/**
 * 聊天系统改造
 * 1. 会话按「会员 + 日期」唯一，并冻结系数、倍数、风控金额
 * 2. 聊天记录关联会话
 * 3. 新增下单表，仅保存号码与下单金额
 */
class ChatUpgrade extends Migrator
{
    public function up(): void
    {
        $this->addConversationColumns();
        $this->backfillConversation();
        $this->indexConversation();
        $this->addMessageColumns();
        $this->backfillMessage();
        $this->createOrderTable();
    }

    /**
     * 会话表新增日期与风控快照字段
     */
    protected function addConversationColumns(): void
    {
        if (!$this->hasTable('chat_conversation')) return;

        $table   = $this->table('chat_conversation');
        $columns = [
            'session_date' => ['limit' => 10, 'default' => '', 'null' => false, 'comment' => '会话日期', 'after' => 'user_id'],
            'coefficient'  => ['precision' => 10, 'scale' => 2, 'default' => 0.96, 'null' => false, 'comment' => '系数（会话冻结）', 'after' => 'session_date'],
            'multiple'     => ['precision' => 10, 'scale' => 2, 'default' => 47, 'null' => false, 'comment' => '倍数（会话冻结）', 'after' => 'coefficient'],
            'risk_amount'  => ['precision' => 12, 'scale' => 2, 'default' => 1000, 'null' => false, 'comment' => '风控金额（会话冻结）', 'after' => 'multiple'],
        ];

        $changed = false;
        foreach ($columns as $name => $options) {
            if ($table->hasColumn($name)) continue;
            $type = $name === 'session_date' ? 'string' : 'decimal';
            $table->addColumn($name, $type, $options);
            $changed = true;
        }
        if ($changed) {
            $table->save();
        }
    }

    /**
     * 回填旧会话的日期与风控快照
     */
    protected function backfillConversation(): void
    {
        if (!$this->hasTable('chat_conversation')) return;

        $config = (array)config('liu', []);
        $rows   = Db::name('chat_conversation')->select()->toArray();
        foreach ($rows as $row) {
            $sessionDate = (string)($row['session_date'] ?? '');
            if ($sessionDate !== '') continue;

            $timestamp = (int)($row['create_time'] ?? 0) ?: time();
            Db::name('chat_conversation')->where('id', $row['id'])->update([
                'session_date' => date('Y-m-d', $timestamp),
                'coefficient'  => $row['coefficient'] ?: ($config['coefficient'] ?? 0.96),
                'multiple'     => $row['multiple'] ?: ($config['multiple'] ?? 47),
                'risk_amount'  => $row['risk_amount'] ?: ($config['risk_amount'] ?? 1000),
            ]);
        }
    }

    /**
     * 会话唯一索引改为「会员 + 日期」
     */
    protected function indexConversation(): void
    {
        if (!$this->hasTable('chat_conversation')) return;

        $table = $this->table('chat_conversation');
        if ($table->hasIndex('user_id')) {
            $table->removeIndexByName('user_id');
        }
        if (!$table->hasIndex('user_date')) {
            $table->addIndex(['user_id', 'session_date'], ['unique' => true, 'name' => 'user_date']);
        }
        $table->save();
    }

    /**
     * 聊天记录表新增会话ID
     */
    protected function addMessageColumns(): void
    {
        if (!$this->hasTable('chat_message')) return;

        $table = $this->table('chat_message');
        if (!$table->hasColumn('conversation_id')) {
            $table->addColumn('conversation_id', 'biginteger', ['signed' => false, 'default' => 0, 'null' => false, 'comment' => '会话ID', 'after' => 'user_id'])
                ->save();
        }
        $table = $this->table('chat_message');
        if (!$table->hasIndex('conversation_id_id')) {
            $table->addIndex(['conversation_id', 'id'], ['name' => 'conversation_id_id'])
                ->save();
        }
    }

    /**
     * 旧聊天记录按会员回填会话ID
     */
    protected function backfillMessage(): void
    {
        if (!$this->hasTable('chat_message')) return;

        $conversationIds = Db::name('chat_conversation')->column('id', 'user_id');
        if (!$conversationIds) return;

        $userIds = Db::name('chat_message')->where('conversation_id', 0)->group('user_id')->column('user_id');
        foreach ($userIds as $userId) {
            if (!isset($conversationIds[$userId])) continue;
            Db::name('chat_message')->where('user_id', $userId)->where('conversation_id', 0)->update([
                'conversation_id' => (int)$conversationIds[$userId],
            ]);
        }
    }

    /**
     * 下单表：仅保存号码与下单金额
     */
    protected function createOrderTable(): void
    {
        if ($this->hasTable('chat_order')) return;

        $table = $this->table('chat_order', [
            'id'          => false,
            'comment'     => '会员下单表',
            'row_format'  => 'DYNAMIC',
            'primary_key' => 'id',
            'collation'   => 'utf8mb4_unicode_ci',
        ]);
        $table->addColumn('id', 'biginteger', ['comment' => 'ID', 'signed' => false, 'identity' => true, 'null' => false])
            ->addColumn('user_id', 'integer', ['comment' => '会员ID', 'default' => 0, 'signed' => false, 'null' => false])
            ->addColumn('conversation_id', 'biginteger', ['comment' => '会话ID', 'default' => 0, 'signed' => false, 'null' => false])
            ->addColumn('items', 'text', ['null' => true, 'default' => null, 'comment' => '号码金额明细JSON，如{"4":50}'])
            ->addColumn('item_count', 'integer', ['comment' => '号码数量', 'default' => 0, 'signed' => false, 'null' => false])
            ->addColumn('total_amount', 'decimal', ['precision' => 12, 'scale' => 2, 'comment' => '下单总金额', 'default' => 0, 'null' => false])
            ->addColumn('create_time', 'biginteger', ['signed' => false, 'null' => true, 'default' => null, 'comment' => '创建时间'])
            ->addIndex(['user_id', 'id'], ['name' => 'user_id_id'])
            ->addIndex(['conversation_id', 'id'], ['name' => 'conversation_id_id'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('chat_order')) {
            $this->table('chat_order')->drop()->save();
        }

        if ($this->hasTable('chat_message')) {
            $table = $this->table('chat_message');
            if ($table->hasIndex('conversation_id_id')) {
                $table->removeIndexByName('conversation_id_id');
            }
            if ($table->hasColumn('conversation_id')) {
                $table->removeColumn('conversation_id');
            }
            $table->save();
        }

        if ($this->hasTable('chat_conversation')) {
            $table = $this->table('chat_conversation');
            if ($table->hasIndex('user_date')) {
                $table->removeIndexByName('user_date');
            }
            if (!$table->hasIndex('user_id')) {
                $table->addIndex(['user_id'], ['unique' => true, 'name' => 'user_id']);
            }
            foreach (['session_date', 'coefficient', 'multiple', 'risk_amount'] as $column) {
                if ($table->hasColumn($column)) {
                    $table->removeColumn($column);
                }
            }
            $table->save();
        }
    }
}
