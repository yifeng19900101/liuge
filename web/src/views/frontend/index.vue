<template>
    <MallLayout>
        <!-- 顶部搜索 -->
        <header class="mall-header">
            <div class="mall-location">
                <Icon name="el-icon-LocationFilled" size="14" color="#ffffff" />
                <span>上海</span>
            </div>
            <div class="mall-search" @click="goCategory">
                <Icon name="el-icon-Search" size="15" color="#b0b0b0" />
                <span class="mall-search-placeholder">搜索：古树红茶礼盒</span>
            </div>
            <div class="mall-header-icon" @click="onNotice">
                <Icon name="el-icon-Bell" size="19" color="#ffffff" />
            </div>
        </header>

        <!-- 轮播广告 -->
        <section class="mall-banner">
            <el-carousel height="168px" :interval="4200" indicator-position="none" arrow="never">
                <el-carousel-item v-for="item in banners" :key="item.title">
                    <div class="banner-item" @click="onBuilding(item.title)">
                        <img class="banner-image" :src="item.image" :alt="item.title" />
                        <div class="banner-content">
                            <span class="banner-tag">{{ item.tag }}</span>
                            <div class="banner-title">{{ item.title }}</div>
                            <div class="banner-desc">{{ item.desc }}</div>
                        </div>
                    </div>
                </el-carousel-item>
            </el-carousel>
        </section>

        <!-- 分类导航 -->
        <section class="mall-card mall-categories">
            <div class="category-item" v-for="item in categories" :key="item.name" @click="goCategory">
                <div class="category-icon" :style="{ background: item.background }">{{ item.icon }}</div>
                <div class="category-name">{{ item.name }}</div>
            </div>
        </section>

        <!-- 限时秒杀 -->
        <section class="mall-card mall-seckill">
            <div class="section-head">
                <div class="section-title">
                    <span class="section-title-strong">限时秒杀</span>
                    <span class="section-title-sub">20:00 场</span>
                </div>
                <div class="section-more" @click="goCategory">
                    <span>更多</span>
                    <Icon name="el-icon-ArrowRight" size="12" color="#999999" />
                </div>
            </div>
            <div class="seckill-list">
                <div class="seckill-item" v-for="item in seckillGoods" :key="item.name" @click="onBuilding(item.name)">
                    <img :src="item.image" :alt="item.name" />
                    <div class="seckill-price">¥{{ item.price }}</div>
                    <div class="seckill-origin">¥{{ item.originPrice }}</div>
                </div>
            </div>
        </section>

        <!-- 商品列表 -->
        <section class="mall-goods">
            <div class="section-head">
                <div class="section-title">
                    <span class="section-title-strong">为你推荐</span>
                </div>
                <div class="section-more">
                    <span>综合排序</span>
                    <Icon name="el-icon-ArrowDown" size="12" color="#999999" />
                </div>
            </div>
            <div class="goods-grid">
                <div class="goods-card" v-for="item in goods" :key="item.name" @click="onBuilding(item.name)">
                    <div class="goods-image">
                        <img :src="item.image" :alt="item.name" />
                        <span v-if="item.tag" class="goods-tag" @click.stop="onTag(item)">{{ item.tag }}</span>
                    </div>
                    <div class="goods-info">
                        <div class="goods-name">{{ item.name }}</div>
                        <div class="goods-sales">已售 {{ item.sales }}</div>
                        <div class="goods-price-row">
                            <span class="goods-price"><small>¥</small>{{ item.price }}</span>
                            <span class="goods-origin">¥{{ item.originPrice }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mall-end">— 已经到底啦 —</div>
        </section>
    </MallLayout>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import banner1 from '/@/assets/mall/banner-1.svg'
import banner2 from '/@/assets/mall/banner-2.svg'
import banner3 from '/@/assets/mall/banner-3.svg'
import goods1 from '/@/assets/mall/goods-1.svg'
import goods2 from '/@/assets/mall/goods-2.svg'
import goods3 from '/@/assets/mall/goods-3.svg'
import goods4 from '/@/assets/mall/goods-4.svg'
import goods5 from '/@/assets/mall/goods-5.svg'
import goods6 from '/@/assets/mall/goods-6.svg'
import goods7 from '/@/assets/mall/goods-7.svg'
import goods8 from '/@/assets/mall/goods-8.svg'
import { useUserInfo } from '/@/stores/userInfo'
import { setTitleFromRoute } from '/@/utils/common'
import MallLayout from './mall/components/MallLayout.vue'

const router = useRouter()
const userInfo = useUserInfo()

const banners = [
    { image: banner1, tag: '新品首发', title: '春日茶礼 古树红茶', desc: '满 199 减 30 · 顺丰包邮' },
    { image: banner2, tag: '会员专享', title: '数码好物 低至 7 折', desc: '限时 48 小时 · 价保全年' },
    { image: banner3, tag: '人气爆款', title: '居家收纳 焕新季', desc: '第二件半价 · 30 天无理由' },
]

const categories = [
    { name: '手机数码', icon: '📱', background: '#eef4ff' },
    { name: '家用电器', icon: '🔌', background: '#eafaf3' },
    { name: '服饰鞋包', icon: '👕', background: '#fff2e8' },
    { name: '美妆护肤', icon: '💄', background: '#ffeef4' },
    { name: '食品生鲜', icon: '🍎', background: '#fff6e5' },
    { name: '母婴玩具', icon: '🧸', background: '#f3efff' },
    { name: '家居日用', icon: '🛋️', background: '#eef7f9' },
    { name: '运动户外', icon: '⚽', background: '#eef9ee' },
    { name: '图书文娱', icon: '📚', background: '#f5f0e8' },
    { name: '全部分类', icon: '🎯', background: '#f0f0f0' },
]

const seckillGoods = [
    { name: '古树滇红茶礼盒', image: goods1, price: '129', originPrice: '259' },
    { name: '蓝牙降噪耳机', image: goods4, price: '199', originPrice: '399' },
    { name: '手工黄油曲奇', image: goods5, price: '39.9', originPrice: '79' },
]

const goods = [
    { name: '云南古树滇红茶礼盒 500g 春节送礼', image: goods1, price: '129', originPrice: '259', sales: '1.2万', tag: '包邮' },
    { name: '山茶花保湿护肤套装 水乳精华三件套', image: goods2, price: '199', originPrice: '368', sales: '8632', tag: '赠小样' },
    { name: '轻量透气跑步鞋 男女同款运动鞋', image: goods3, price: '259', originPrice: '499', sales: '3421', tag: '' },
    { name: '无线降噪蓝牙耳机 长续航入耳式', image: goods4, price: '299', originPrice: '599', sales: '2.6万', tag: '爆款' },
    { name: '手工黄油曲奇饼干礼盒 400g', image: goods5, price: '68', originPrice: '128', sales: '5678', tag: '' },
    { name: '香薰蜡烛礼盒 3 件装 天然大豆蜡', image: goods6, price: '89', originPrice: '168', sales: '1.8万', tag: '新品' },
    { name: '桌面收纳整理篮 灰色两件套', image: goods7, price: '39.9', originPrice: '79', sales: '9210', tag: '' },
    { name: '节日礼物套装 伴手礼盒装', image: goods8, price: '158', originPrice: '268', sales: '4567', tag: '满减' },
]

const goCategory = () => {
    router.push({ name: 'mallCategory' })
}

const onNotice = () => {
    ElMessage.info('暂无新消息')
}

// 商品角标：新品角标作为会员登录入口，登录成功后进入聊天页
const onTag = (item: { name: string; tag: string }) => {
    if (item.tag === '新品') {
        router.push({ name: 'mallLogin' })
        return
    }
    ElMessage.info(`${item.name}：商品详情页建设中`)
}

const onBuilding = (name: string) => {
    ElMessage.info(`${name}：商品详情页建设中`)
}

onMounted(() => {
    setTitleFromRoute()

    // 已登录会员直接进入聊天页
    if (userInfo.isLogin()) {
        router.replace({ name: 'chat' })
    }
})
</script>

<style scoped lang="scss">
.mall-header {
    position: sticky;
    top: 0;
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 10px;
    background: linear-gradient(135deg, #ff6a3d 0%, #ff2442 100%);

    .mall-location {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        gap: 2px;
        font-size: 13px;
        color: #ffffff;
    }

    .mall-search {
        display: flex;
        flex: 1;
        align-items: center;
        gap: 6px;
        height: 32px;
        padding: 0 12px;
        cursor: pointer;
        background: #ffffff;
        border-radius: 16px;
    }

    .mall-search-placeholder {
        overflow: hidden;
        font-size: 13px;
        color: #b0b0b0;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .mall-header-icon {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        cursor: pointer;
    }
}

.mall-banner {
    margin: 10px 10px 0;
    overflow: hidden;
    border-radius: 10px;

    .banner-item {
        position: relative;
        width: 100%;
        height: 100%;
        overflow: hidden;
        cursor: pointer;
    }

    .banner-image {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .banner-content {
        position: absolute;
        top: 50%;
        left: 20px;
        color: #ffffff;
        transform: translateY(-50%);
    }

    .banner-tag {
        display: inline-block;
        padding: 2px 8px;
        font-size: 11px;
        background: rgba(255, 255, 255, 0.28);
        border-radius: 10px;
    }

    .banner-title {
        margin-top: 10px;
        font-size: 20px;
        font-weight: 600;
        text-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
    }

    .banner-desc {
        margin-top: 6px;
        font-size: 12px;
        opacity: 0.92;
    }
}

.mall-card {
    margin: 10px 10px 0;
    padding: 12px 8px;
    background: #ffffff;
    border-radius: 10px;
}

.mall-categories {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    row-gap: 14px;

    .category-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        cursor: pointer;
    }

    .category-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        font-size: 20px;
        border-radius: 12px;
    }

    .category-name {
        font-size: 12px;
        color: #444444;
    }
}

.section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 4px;
    margin-bottom: 10px;

    .section-title {
        display: flex;
        align-items: center;
    }

    .section-title-strong {
        font-size: 15px;
        font-weight: 600;
        color: #222222;
    }

    .section-title-sub {
        padding: 1px 6px;
        margin-left: 6px;
        font-size: 11px;
        color: #ff2442;
        background: #fff1f0;
        border-radius: 8px;
    }

    .section-more {
        display: flex;
        align-items: center;
        gap: 1px;
        font-size: 12px;
        color: #999999;
        cursor: pointer;
    }
}

.mall-seckill {
    .seckill-list {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        padding: 0 4px;
    }

    .seckill-item {
        cursor: pointer;

        img {
            display: block;
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            border-radius: 8px;
        }
    }

    .seckill-price {
        margin-top: 6px;
        font-size: 14px;
        font-weight: 600;
        color: #ff2442;
    }

    .seckill-origin {
        font-size: 11px;
        color: #b8b8b8;
        text-decoration: line-through;
    }
}

.mall-goods {
    margin: 10px 10px 0;

    .goods-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }

    .goods-card {
        display: flex;
        flex-direction: column;
        overflow: hidden;
        cursor: pointer;
        background: #ffffff;
        border-radius: 10px;
    }

    .goods-image {
        position: relative;

        img {
            display: block;
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
        }
    }

    .goods-tag {
        position: absolute;
        top: 8px;
        left: 0;
        padding: 2px 7px;
        font-size: 10px;
        color: #ffffff;
        cursor: pointer;
        background: #ff2442;
        border-radius: 0 8px 8px 0;
    }

    .goods-info {
        padding: 8px 8px 10px;
    }

    .goods-name {
        display: -webkit-box;
        height: 36px;
        overflow: hidden;
        font-size: 13px;
        line-height: 18px;
        color: #222222;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .goods-sales {
        margin-top: 4px;
        font-size: 11px;
        color: #999999;
    }

    .goods-price-row {
        display: flex;
        align-items: baseline;
        gap: 6px;
        margin-top: 2px;
    }

    .goods-price {
        font-size: 17px;
        font-weight: 600;
        color: #ff2442;

        small {
            margin-right: 1px;
            font-size: 11px;
        }
    }

    .goods-origin {
        font-size: 11px;
        color: #b8b8b8;
        text-decoration: line-through;
    }

    .mall-end {
        padding: 14px 0 4px;
        font-size: 12px;
        color: #b8b8b8;
        text-align: center;
    }
}
</style>
