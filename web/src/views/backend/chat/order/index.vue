<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <TableHeader
            :buttons="['refresh', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('chat.order.Member') + '/' + t('chat.order.Conversation ID') })"
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
    name: 'chat/order',
})

const { t } = useI18n()

/** 号码金额明细 JSON 转文本 */
const formatItems = (value: string) => {
    if (!value) return ''
    try {
        const items = JSON.parse(value) as Record<string, number>
        return Object.keys(items)
            .sort((a, b) => Number(a) - Number(b))
            .map((code) => code.padStart(2, '0') + '×' + items[code])
            .join('、')
    } catch {
        return value
    }
}

const baTable = new baTableClass(new baTableApi('/admin/chat.Order/'), {
    column: [
        { label: t('Id'), prop: 'id', align: 'center', operator: '=', operatorPlaceholder: t('Id'), width: 80 },
        { label: t('chat.order.Member'), prop: 'user.nickname', align: 'center', operator: false },
        { label: t('chat.order.Username'), prop: 'user.username', align: 'center', operator: false },
        {
            label: t('chat.order.Conversation ID'),
            prop: 'conversation_id',
            align: 'center',
            operator: '=',
            operatorPlaceholder: t('chat.order.Conversation ID'),
            width: 100,
        },
        {
            label: t('chat.order.Items'),
            prop: 'items',
            align: 'center',
            operator: false,
            showOverflowTooltip: true,
            formatter: (row: TableRow, column: TableColumn, cellValue: string) => formatItems(cellValue),
        },
        { label: t('chat.order.Item count'), prop: 'item_count', align: 'center', operator: false, width: 100 },
        { label: t('chat.order.Total amount'), prop: 'total_amount', align: 'center', operator: false, width: 120 },
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
