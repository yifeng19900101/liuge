<template>
    <MallLayout fixed>
        <!-- 顶部搜索 -->
        <div class="category-header">
            <div class="category-search">
                <Icon name="el-icon-Search" size="15" color="#b0b0b0" />
                <span class="category-search-placeholder">搜索商品分类</span>
            </div>
        </div>

        <div class="category-body">
            <!-- 左侧一级分类 -->
            <aside class="category-side">
                <div
                    v-for="(item, idx) in categories"
                    :key="item.name"
                    class="side-item"
                    :class="{ 'is-active': state.activeIndex === idx }"
                    @click="state.activeIndex = idx"
                >
                    {{ item.name }}
                </div>
            </aside>

            <!-- 右侧二级分类 -->
            <div class="category-main">
                <div class="category-banner">
                    <div class="category-banner-title">{{ current.name }}</div>
                    <div class="category-banner-desc">精选好物 · 当日发货 · 七天无理由</div>
                </div>
                <div class="category-grid">
                    <div class="category-grid-item" v-for="item in current.subs" :key="item.name" @click="onClick(item.name)">
                        <div class="category-grid-icon" :style="{ background: item.background }">{{ item.icon }}</div>
                        <span class="category-grid-name">{{ item.name }}</span>
                    </div>
                </div>
                <div class="category-end">— 已展示全部分类 —</div>
            </div>
        </div>
    </MallLayout>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { computed, onMounted, reactive } from 'vue'
import { setTitleFromRoute } from '/@/utils/common'
import MallLayout from './components/MallLayout.vue'

const categories = [
    {
        name: '手机数码',
        subs: [
            { name: '手机通讯', icon: '📱', background: '#eef4ff' },
            { name: '智能手表', icon: '⌚', background: '#eaf3ff' },
            { name: '蓝牙耳机', icon: '🎧', background: '#e9f6fb' },
            { name: '平板电脑', icon: '💻', background: '#eef0ff' },
            { name: '充电配件', icon: '🔋', background: '#eefaf1' },
            { name: '摄影摄像', icon: '📷', background: '#f2f0ff' },
        ],
    },
    {
        name: '家用电器',
        subs: [
            { name: '厨房小电', icon: '🍳', background: '#fff4e8' },
            { name: '生活电器', icon: '🌀', background: '#eef7f9' },
            { name: '个护健康', icon: '🪒', background: '#ffeef4' },
            { name: '清洁电器', icon: '🧹', background: '#eefaf3' },
            { name: '空调冰箱', icon: '❄️', background: '#eaf4ff' },
            { name: '电视影音', icon: '📺', background: '#f3efff' },
        ],
    },
    {
        name: '服饰鞋包',
        subs: [
            { name: '当季新品', icon: '👗', background: '#ffeef4' },
            { name: '男装女装', icon: '👕', background: '#fff2e8' },
            { name: '运动鞋靴', icon: '👟', background: '#eef0ff' },
            { name: '箱包皮具', icon: '👜', background: '#fdf0e6' },
            { name: '内衣配饰', icon: '🧣', background: '#f5f0e8' },
            { name: '童装童鞋', icon: '🧦', background: '#eefaf1' },
        ],
    },
    {
        name: '美妆护肤',
        subs: [
            { name: '面部护理', icon: '🧴', background: '#ffeef4' },
            { name: '彩妆香水', icon: '💄', background: '#fff0f6' },
            { name: '洗护清洁', icon: '🧼', background: '#eef7f9' },
            { name: '身体护理', icon: '🛁', background: '#eef4ff' },
            { name: '美容工具', icon: '🪞', background: '#f3efff' },
            { name: '男士专区', icon: '🧔', background: '#eaf3ff' },
        ],
    },
    {
        name: '食品生鲜',
        subs: [
            { name: '茶饮冲调', icon: '🍵', background: '#eefaf1' },
            { name: '休闲零食', icon: '🍪', background: '#fff6e5' },
            { name: '粮油调味', icon: '🍚', background: '#f5f0e8' },
            { name: '新鲜果蔬', icon: '🍎', background: '#eef7f9' },
            { name: '酒水饮料', icon: '🥤', background: '#eef0ff' },
            { name: '礼盒专区', icon: '🎁', background: '#ffeef4' },
        ],
    },
    {
        name: '母婴玩具',
        subs: [
            { name: '奶粉辅食', icon: '🍼', background: '#fff6e5' },
            { name: '纸尿裤', icon: '🧷', background: '#eef4ff' },
            { name: '童车童床', icon: '🛒', background: '#eefaf1' },
            { name: '积木拼插', icon: '🧱', background: '#fef0e8' },
            { name: '毛绒玩偶', icon: '🧸', background: '#f3efff' },
            { name: '孕产用品', icon: '🤰', background: '#ffeef4' },
        ],
    },
    {
        name: '家居日用',
        subs: [
            { name: '收纳整理', icon: '🧺', background: '#eef7f9' },
            { name: '厨房用具', icon: '🍽️', background: '#fff4e8' },
            { name: '床品布艺', icon: '🛏️', background: '#eef0ff' },
            { name: '清洁用品', icon: '🧽', background: '#eefaf3' },
            { name: '香薰蜡烛', icon: '🕯️', background: '#f3efff' },
            { name: '家装软饰', icon: '🪴', background: '#eefaf1' },
        ],
    },
    {
        name: '运动户外',
        subs: [
            { name: '健身器材', icon: '🏋️', background: '#eef0ff' },
            { name: '球类运动', icon: '⚽', background: '#eef9ee' },
            { name: '户外露营', icon: '⛺', background: '#eefaf1' },
            { name: '骑行装备', icon: '🚴', background: '#eaf3ff' },
            { name: '泳池用品', icon: '🏊', background: '#e9f6fb' },
            { name: '运动服饰', icon: '🩳', background: '#fff2e8' },
        ],
    },
    {
        name: '图书文娱',
        subs: [
            { name: '文学小说', icon: '📚', background: '#f5f0e8' },
            { name: '童书绘本', icon: '📖', background: '#fff6e5' },
            { name: '文具用品', icon: '✏️', background: '#eef4ff' },
            { name: '乐器音响', icon: '🎸', background: '#f3efff' },
            { name: '手办模型', icon: '🧩', background: '#ffeef4' },
            { name: '游戏周边', icon: '🎮', background: '#eef0ff' },
        ],
    },
    {
        name: '全部分类',
        subs: [
            { name: '限时秒杀', icon: '⚡', background: '#fff0e8' },
            { name: '新品首发', icon: '🌟', background: '#fff6e5' },
            { name: '品牌专区', icon: '🏷️', background: '#eef4ff' },
            { name: '会员专享', icon: '💎', background: '#f3efff' },
            { name: '凑单专区', icon: '🧮', background: '#eefaf1' },
            { name: '清仓特惠', icon: '🎯', background: '#ffeef4' },
        ],
    },
]

const state = reactive({
    activeIndex: 0,
})

const current = computed(() => categories[state.activeIndex] || categories[0])

const onClick = (name: string) => {
    ElMessage.info(`${name}：分类商品页建设中`)
}

onMounted(() => {
    setTitleFromRoute()
})
</script>

<style scoped lang="scss">
.category-header {
    position: sticky;
    top: 0;
    z-index: 10;
    display: flex;
    flex-shrink: 0;
    align-items: center;
    padding: 10px;
    background: #ffffff;
    border-bottom: 1px solid #f0f0f0;
}

.category-search {
    display: flex;
    flex: 1;
    align-items: center;
    gap: 6px;
    height: 32px;
    padding: 0 12px;
    background: #f4f4f4;
    border-radius: 16px;
}

.category-search-placeholder {
    overflow: hidden;
    font-size: 13px;
    color: #b0b0b0;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.category-body {
    display: flex;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

.category-side {
    flex-shrink: 0;
    width: 92px;
    height: 100%;
    padding-bottom: 20px;
    overflow-y: auto;
    background: #f7f7f7;
    -webkit-overflow-scrolling: touch;

    .side-item {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 46px;
        font-size: 13px;
        color: #555555;
        text-align: center;
        cursor: pointer;

        &.is-active {
            position: relative;
            font-weight: 600;
            color: #ff2442;
            background: #ffffff;

            &::before {
                position: absolute;
                top: 50%;
                left: 0;
                width: 3px;
                height: 18px;
                content: '';
                background: #ff2442;
                border-radius: 0 2px 2px 0;
                transform: translateY(-50%);
            }
        }
    }
}

.category-main {
    flex: 1;
    height: 100%;
    padding: 10px;
    overflow-y: auto;
    background: #ffffff;
    -webkit-overflow-scrolling: touch;
}

.category-banner {
    padding: 16px 14px;
    color: #ffffff;
    background: linear-gradient(135deg, #ff6a3d 0%, #ff2442 100%);
    border-radius: 10px;

    .category-banner-title {
        font-size: 16px;
        font-weight: 600;
    }

    .category-banner-desc {
        margin-top: 6px;
        font-size: 11px;
        opacity: 0.9;
    }
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px 6px;
    padding: 16px 0 4px;
}

.category-grid-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    cursor: pointer;

    .category-grid-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        font-size: 22px;
        border-radius: 14px;
    }

    .category-grid-name {
        padding: 0 2px;
        font-size: 12px;
        color: #444444;
        text-align: center;
    }
}

.category-end {
    padding: 12px 0 20px;
    font-size: 11px;
    color: #b8b8b8;
    text-align: center;
}
</style>
