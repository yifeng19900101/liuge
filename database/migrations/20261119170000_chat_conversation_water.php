<?php

use think\facade\Db;
use think\migration\Migrator;

/**
 * 会话冻结水率
 * 1. 会话表新增 water_rate（水率，4 位小数）
 * 2. coefficient（结算系数）精度提升到 4 位小数，避免由水率换算时丢失精度
 */
class ChatConversationWater extends Migrator
{
    public function up(): void
    {
        if (!$this->hasTable('chat_conversation')) return;

        $table = $this->table('chat_conversation');
        if (!$table->hasColumn('water_rate')) {
            $table->addColumn('water_rate', 'decimal', [
                'precision' => 5,
                'scale'     => 4,
                'default'   => 0.0400,
                'null'      => false,
                'comment'   => '水率（会话冻结）',
                'after'     => 'coefficient',
            ]);
        }
        $table->changeColumn('coefficient', 'decimal', [
            'precision' => 6,
            'scale'     => 4,
            'default'   => 0.9600,
            'null'      => false,
            'comment'   => '系数（会话冻结）',
        ]);
        $table->save();

        // 历史会话按 coefficient 反推水率
        foreach (Db::name('chat_conversation')->field(['id', 'coefficient'])->select()->toArray() as $row) {
            $waterRate = round(1 - (float)$row['coefficient'], 4);
            Db::name('chat_conversation')->where('id', $row['id'])->update([
                'water_rate' => $waterRate,
            ]);
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('chat_conversation')) return;

        $table = $this->table('chat_conversation');
        if ($table->hasColumn('water_rate')) {
            $table->removeColumn('water_rate');
        }
        $table->changeColumn('coefficient', 'decimal', [
            'precision' => 10,
            'scale'     => 2,
            'default'   => 0.96,
            'null'      => false,
            'comment'   => '系数（会话冻结）',
        ]);
        $table->save();
    }
}
