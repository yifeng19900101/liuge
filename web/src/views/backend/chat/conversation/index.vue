<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <TableHeader
            :buttons="['refresh', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="
                t('Quick search placeholder', { fields: t('chat.conversation.Member') + '/' + t('chat.conversation.Conversation ID') })
            "
        />

        <Table />

        <!-- 手机样式聊天窗口 -->
        <ChatPhone v-model="state.chatVisible" :row="state.chatRow" @sent="baTable.getData()" />
    </div>
</template>

<script setup lang="ts">
import { provide, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import baTableClass from '/@/utils/baTable'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import ChatPhone from './chatPhone.vue'
import { baTableApi } from '/@/api/common'

defineOptions({
    name: 'chat/conversation',
})

const { t } = useI18n()

const state = reactive({
    chatVisible: false,
    chatRow: null as TableRow | null,
})

/** 打开手机样式聊天窗口 */
const openChat = (row: TableRow) => {
    state.chatRow = row
    state.chatVisible = true
}

const baTable = new baTableClass(new baTableApi('/admin/chat.Conversation/'), {
    column: [
        { label: t('Id'), prop: 'id', align: 'center', operator: '=', operatorPlaceholder: t('Id'), width: 80 },
        { label: t('chat.conversation.Member'), prop: 'user.nickname', align: 'center', operator: false },
        { label: t('chat.conversation.Username'), prop: 'user.username', align: 'center', operator: false },
        {
            label: t('chat.conversation.Session date'),
            prop: 'session_date',
            align: 'center',
            operator: 'LIKE',
            operatorPlaceholder: t('Fuzzy query'),
            width: 120,
        },
        { label: t('chat.conversation.Coefficient'), prop: 'coefficient', align: 'center', operator: false, width: 100 },
        { label: t('chat.conversation.Multiple'), prop: 'multiple', align: 'center', operator: false, width: 100 },
        { label: t('chat.conversation.Risk amount'), prop: 'risk_amount', align: 'center', operator: false, width: 110 },
        {
            label: t('chat.conversation.Risk status'),
            prop: 'is_risk',
            align: 'center',
            render: 'tag',
            custom: { 1: 'danger', 0: 'info' },
            replaceValue: { 1: t('chat.conversation.Risked'), 0: t('chat.conversation.Not risked') },
            operator: '=',
            width: 110,
        },
        {
            label: t('chat.conversation.Risk time'),
            prop: 'risk_time',
            align: 'center',
            render: 'datetime',
            operator: false,
            width: 160,
        },
        { label: t('chat.conversation.Last message'), prop: 'last_message', align: 'center', operator: false, showOverflowTooltip: true },
        {
            label: t('chat.conversation.Last time'),
            prop: 'last_time',
            align: 'center',
            render: 'datetime',
            sortable: 'custom',
            operator: false,
            width: 160,
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
        {
            label: t('Operate'),
            align: 'center',
            width: 90,
            render: 'buttons',
            operator: false,
            buttons: [
                {
                    render: 'basicButton',
                    name: 'chat',
                    text: t('chat.conversation.Chat'),
                    type: 'primary',
                    icon: 'fa fa-comments',
                    click: (row: TableRow) => openChat(row),
                },
            ],
        },
    ],
    dblClickNotEditColumn: ['all'],
})

baTable.mount()
baTable.getData()

provide('baTable', baTable)
</script>

<style scoped lang="scss"></style>
