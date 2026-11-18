<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <TableHeader
            :buttons="['refresh', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('chat.message.Content') + '/' + t('chat.message.Conversation ID') })"
        />

        <Table />
    </div>
</template>

<script setup lang="ts">
import { provide } from 'vue'
import { useI18n } from 'vue-i18n'
import baTableClass from '/@/utils/baTable'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import { baTableApi } from '/@/api/common'

defineOptions({
    name: 'chat/message',
})

const { t } = useI18n()

const baTable = new baTableClass(new baTableApi('/admin/chat.Message/'), {
    column: [
        { label: t('Id'), prop: 'id', align: 'center', operator: '=', operatorPlaceholder: t('Id'), width: 80 },
        {
            label: t('chat.message.Conversation ID'),
            prop: 'conversation_id',
            align: 'center',
            operator: '=',
            operatorPlaceholder: t('chat.message.Conversation ID'),
            width: 100,
        },
        { label: t('chat.message.Member'), prop: 'user.nickname', align: 'center', operator: false },
        { label: t('chat.message.Username'), prop: 'user.username', align: 'center', operator: false },
        {
            label: t('chat.message.Role'),
            prop: 'role',
            align: 'center',
            render: 'tag',
            custom: { user: 'info', bot: 'success' },
            replaceValue: { user: t('chat.message.User'), bot: t('chat.message.Bot') },
            operator: '=',
            width: 100,
        },
        {
            label: t('chat.message.Content'),
            prop: 'content',
            align: 'center',
            operator: 'LIKE',
            operatorPlaceholder: t('Fuzzy query'),
            showOverflowTooltip: true,
        },
        {
            label: t('Create time'),
            prop: 'create_time',
            align: 'center',
            render: 'datetime',
            sortable: 'custom',
            operator: 'RANGE',
            width: 160,
        },
    ],
    dblClickNotEditColumn: ['all'],
})

baTable.mount()
baTable.getData()

provide('baTable', baTable)
</script>

<style scoped lang="scss"></style>
