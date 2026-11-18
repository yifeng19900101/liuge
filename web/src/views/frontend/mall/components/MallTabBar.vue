<template>
    <nav class="mall-tabbar">
        <div v-for="item in tabs" :key="item.name" class="tab-item" :class="{ 'is-active': isActive(item.name) }" @click="onTab(item.name)">
            <Icon :name="item.icon" size="21" :color="isActive(item.name) ? '#ff2442' : '#8a8a8a'" />
            <span>{{ item.text }}</span>
        </div>
    </nav>
</template>

<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'

const tabs = [
    { name: '/', text: '首页', icon: 'el-icon-House' },
    { name: 'mallCategory', text: '分类', icon: 'el-icon-Menu' },
    { name: 'mallCart', text: '购物车', icon: 'el-icon-ShoppingCart' },
    { name: 'mallMine', text: '我的', icon: 'el-icon-User' },
]

const route = useRoute()
const router = useRouter()

const isActive = (name: string | symbol | undefined) => route.name === name

const onTab = (name: string) => {
    if (route.name === name) return
    router.push({ name })
}
</script>

<style scoped lang="scss">
.mall-tabbar {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    height: 52px;
    padding-bottom: env(safe-area-inset-bottom);
    background: #ffffff;
    border-top: 1px solid #ececec;

    .tab-item {
        display: flex;
        flex: 1;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        height: 100%;
        font-size: 10px;
        color: #8a8a8a;
        cursor: pointer;
        user-select: none;

        &.is-active {
            color: #ff2442;
        }
    }
}
</style>
