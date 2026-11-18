<template>
    <MallLayout>
        <!-- 顶部个人信息 -->
        <div class="mine-header">
            <div class="mine-profile">
                <div class="mine-avatar">
                    <Icon name="el-icon-UserFilled" size="30" color="#ffffff" />
                </div>
                <div class="mine-info">
                    <div class="mine-name">商城访客</div>
                    <div class="mine-desc">欢迎光临，祝你购物愉快</div>
                </div>
            </div>
            <div class="mine-stats">
                <div class="stat-item" v-for="item in stats" :key="item.name" @click="onClick(item.name)">
                    <div class="stat-num">{{ item.value }}</div>
                    <div class="stat-name">{{ item.name }}</div>
                </div>
            </div>
        </div>

        <!-- 我的订单 -->
        <section class="mine-card">
            <div class="mine-card-head">
                <div class="mine-card-title">我的订单</div>
                <div class="mine-card-more" @click="onClick('全部订单')">
                    <span>全部订单</span>
                    <Icon name="el-icon-ArrowRight" size="12" color="#999999" />
                </div>
            </div>
            <div class="mine-order-grid">
                <div class="order-item" v-for="item in orders" :key="item.name" @click="onClick(item.name)">
                    <div class="order-icon">
                        <Icon :name="item.icon" size="22" color="#ff2442" />
                        <span v-if="item.badge" class="order-badge">{{ item.badge }}</span>
                    </div>
                    <span class="order-name">{{ item.name }}</span>
                </div>
            </div>
        </section>

        <!-- 常用功能 -->
        <section class="mine-card">
            <div class="mine-card-head">
                <div class="mine-card-title">常用功能</div>
            </div>
            <div class="mine-tools">
                <div class="tool-item" v-for="item in tools" :key="item.name" @click="onClick(item.name)">
                    <span class="tool-icon">{{ item.icon }}</span>
                    <span class="tool-name">{{ item.name }}</span>
                </div>
            </div>
        </section>

        <!-- 其他 -->
        <section class="mine-card">
            <div class="mine-list">
                <div class="list-item" v-for="item in settings" :key="item.name" @click="onClick(item.name)">
                    <span class="list-name">{{ item.name }}</span>
                    <Icon name="el-icon-ArrowRight" size="12" color="#c8c8c8" />
                </div>
            </div>
        </section>

        <div class="mine-tip">本页为静态演示数据</div>
    </MallLayout>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { onMounted } from 'vue'
import { setTitleFromRoute } from '/@/utils/common'
import MallLayout from './components/MallLayout.vue'

const stats = [
    { name: '积分', value: '1280' },
    { name: '优惠券', value: '3' },
    { name: '收藏', value: '12' },
]

const orders = [
    { name: '待付款', icon: 'el-icon-Wallet', badge: 1 },
    { name: '待发货', icon: 'el-icon-Box', badge: 0 },
    { name: '待收货', icon: 'el-icon-Van', badge: 0 },
    { name: '待评价', icon: 'el-icon-ChatDotRound', badge: 2 },
    { name: '退款售后', icon: 'el-icon-Service', badge: 0 },
]

const tools = [
    { name: '收货地址', icon: '📍' },
    { name: '优惠券', icon: '🎫' },
    { name: '我的收藏', icon: '⭐' },
    { name: '浏览记录', icon: '🕒' },
    { name: '客服中心', icon: '🎧' },
    { name: '帮助中心', icon: '❓' },
]

const settings = [{ name: '账户安全' }, { name: '通知设置' }, { name: '关于我们' }]

const onClick = (name: string) => {
    ElMessage.info(`${name}：演示页面建设中`)
}

onMounted(() => {
    setTitleFromRoute()
})
</script>

<style scoped lang="scss">
.mine-header {
    padding: 18px 16px 0;
    color: #ffffff;
    background: linear-gradient(135deg, #ff6a3d 0%, #ff2442 100%);

    .mine-profile {
        display: flex;
        align-items: center;
        gap: 12px;
        padding-bottom: 16px;
    }

    .mine-avatar {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        width: 56px;
        height: 56px;
        background: rgba(255, 255, 255, 0.24);
        border: 1px solid rgba(255, 255, 255, 0.45);
        border-radius: 50%;
    }

    .mine-name {
        font-size: 17px;
        font-weight: 600;
    }

    .mine-desc {
        margin-top: 4px;
        font-size: 12px;
        opacity: 0.9;
    }
}

.mine-stats {
    display: flex;
    padding: 10px 0 14px;
    background: rgba(255, 255, 255, 0.16);
    border-radius: 12px 12px 0 0;

    .stat-item {
        flex: 1;
        text-align: center;
        cursor: pointer;
    }

    .stat-num {
        font-size: 16px;
        font-weight: 600;
    }

    .stat-name {
        margin-top: 2px;
        font-size: 11px;
        opacity: 0.9;
    }
}

.mine-card {
    margin: 10px 10px 0;
    padding: 12px;
    background: #ffffff;
    border-radius: 10px;
}

.mine-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;

    .mine-card-title {
        font-size: 14px;
        font-weight: 600;
        color: #222222;
    }

    .mine-card-more {
        display: flex;
        align-items: center;
        gap: 1px;
        font-size: 12px;
        color: #999999;
        cursor: pointer;
    }
}

.mine-order-grid {
    display: flex;

    .order-item {
        display: flex;
        flex: 1;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        cursor: pointer;
    }

    .order-icon {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
    }

    .order-badge {
        position: absolute;
        top: -2px;
        right: -4px;
        min-width: 15px;
        height: 15px;
        padding: 0 4px;
        font-size: 10px;
        line-height: 15px;
        color: #ffffff;
        text-align: center;
        background: #ff2442;
        border-radius: 8px;
    }

    .order-name {
        font-size: 11px;
        color: #555555;
    }
}

.mine-tools {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    row-gap: 16px;

    .tool-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        cursor: pointer;
    }

    .tool-icon {
        font-size: 22px;
        line-height: 26px;
    }

    .tool-name {
        font-size: 11px;
        color: #555555;
    }
}

.mine-list {
    .list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 42px;
        cursor: pointer;

        & + .list-item {
            border-top: 1px solid #f4f4f4;
        }
    }

    .list-name {
        font-size: 13px;
        color: #333333;
    }
}

.mine-tip {
    padding: 16px 0 20px;
    font-size: 11px;
    color: #b8b8b8;
    text-align: center;
}
</style>
