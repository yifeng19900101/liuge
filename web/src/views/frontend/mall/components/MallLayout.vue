<template>
    <div class="mall-wrapper">
        <div class="mall-page">
            <div class="mall-scroll ba-scroll-style" :class="{ 'is-fixed': fixed }">
                <slot />
            </div>
            <slot name="footer" />
            <MallTabBar />
        </div>
    </div>
</template>

<script setup lang="ts">
import MallTabBar from './MallTabBar.vue'

defineProps({
    // 内容区内部自行滚动时（如分类页双栏）置为 true
    fixed: {
        type: Boolean,
        default: false,
    },
})
</script>

<style scoped lang="scss">
.mall-wrapper {
    display: flex;
    justify-content: center;
    width: 100%;
    height: 100vh;
    height: 100dvh;
    background: #e9e9e9;
    overflow: hidden;
}

.mall-page {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100%;
    color: #222222;
    background: #f4f4f4;
    overflow: hidden;
}

.mall-scroll {
    flex: 1;
    min-height: 0;
    padding-bottom: 10px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;

    &.is-fixed {
        display: flex;
        flex-direction: column;
        padding-bottom: 0;
        overflow: hidden;
    }
}

@media screen and (min-width: 768px) {
    .mall-page {
        max-width: 480px;
        box-shadow: 0 0 24px rgba(0, 0, 0, 0.12);
    }
}
</style>
